<?php

namespace app\modules\geo\services;

use Yii;
use yii\base\Component;
use app\modules\admin\models\WebSetting;

/**
 * Distance and travel time between two points.
 *
 *  - getDrivingDistanceGoogleMap(): road distance and time from the Google Distance Matrix API.
 *  - getDrivingDistance():          straight-line distance, used to add up the segments of a recorded path.
 *
 * Google keys (server side): the dashboard (Integrations > Google Maps), GOOGLE_MAPS_SERVER_KEY, config/secrets.php ['googleMapsKey'], the `map_key` web setting;
 * a key Google refuses is skipped for the next one (GoogleKeys::serverKeys). The key's project needs the "Routes API" enabled in Google Cloud
 * (or, for older projects, the Directions / Distance Matrix API).
 */
class DrivingDistance extends Component
{
    const ENDPOINT = 'https://maps.googleapis.com/maps/api/distancematrix/json';

    // [doc:start] getDrivingDistance - Straight-line distance (great circle). Never calls Google.
    /**
     * Straight-line distance (great circle). Never calls Google.
     *
     * @return array ['status', 'error', 'meters', 'km']
     */
    public function getDrivingDistance($lat1, $long1, $lat2, $long2)
    {
        $pi80 = M_PI / 180;
        $lat1 *= $pi80;
        $long1 *= $pi80;
        $lat2 *= $pi80;
        $long2 *= $pi80;

        $r = 6372.797; // mean radius of the Earth in km
        $dlat = $lat2 - $lat1;
        $dlon = $long2 - $long1;
        $a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlon / 2) * sin($dlon / 2);
        $km = $r * 2 * atan2(sqrt($a), sqrt(1 - $a));

        return ['status' => 'OK', 'error' => 'OK', 'meters' => $km * 1000, 'km' => $km];
    }
    // [doc:end] getDrivingDistance

    // [doc:start] getDrivingDistanceGoogleMap - Road distance and duration. The name is kept for the callers; the answer comes from the first maps service that can give it.
    /**
     * Road distance and duration. The name is kept for the callers; the answer comes from the first maps service that can give it
     * (Google, Mapbox ... see MapProviders), a service that is refused or does not answer is skipped for the next.
     *
     * @return array ['status', 'distance' => text, 'time' => text, 'meters', 'km', 'distvalue']
     * @throws DistanceUnavailableException when no service can answer (key denied, no route, network, ...)
     */
    public function getDrivingDistanceGoogleMap($lat1, $long1, $lat2, $long2)
    {
        return MapProviders::route($lat1, $long1, $lat2, $long2);
    }
    // [doc:end] getDrivingDistanceGoogleMap

    // [doc:start] googleRoute - Google Route
    /**
     * @throws DistanceUnavailableException
     */
    public function googleRoute($lat1, $long1, $lat2, $long2)
    {
        $keys = GoogleKeys::serverKeys();
        if (!$keys) {
            Yii::error('Distance: no Google server key configured', 'geo');
            throw new DistanceUnavailableException('NO_API_KEY');
        }

        // the Routes API (the current one) first; the older Directions and Distance Matrix APIs only work for projects that already had them
        $providers = Yii::$app->params['geo']['providers'] ?? ['routes', 'directions', 'distancematrix'];
        $lastReason = 'NO_PROVIDER';
        foreach ($keys as $key) {
            foreach ($providers as $provider) {
                try {
                    if ($provider === 'routes') {
                        return $this->viaRoutes($key, $lat1, $long1, $lat2, $long2);
                    }

                    return $provider === 'distancematrix'
                        ? $this->viaDistanceMatrix($key, $lat1, $long1, $lat2, $long2)
                        : $this->viaDirections($key, $lat1, $long1, $lat2, $long2);
                } catch (DistanceUnavailableException $e) {
                    $lastReason = $e->reason;
                    // A missing route or a network problem will not be different with another API or key: stop here.
                    if (!in_array($e->reason, ['REQUEST_DENIED', 'OVER_QUERY_LIMIT', 'INVALID_RESPONSE'], true)) {
                        throw $e;
                    }
                }
            }
        }

        throw new DistanceUnavailableException($lastReason);
    }
    // [doc:end] googleRoute

    private function viaRoutes($key, $lat1, $long1, $lat2, $long2)
    {
        $body = json_encode([
            'origin' => ['location' => ['latLng' => ['latitude' => (float) $lat1, 'longitude' => (float) $long1]]],
            'destination' => ['location' => ['latLng' => ['latitude' => (float) $lat2, 'longitude' => (float) $long2]]],
            'travelMode' => 'DRIVE',
        ]);
        $ch = curl_init(getenv('ROUTES_API_URL') ?: 'https://routes.googleapis.com/directions/v2:computeRoutes');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 20, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Goog-Api-Key: ' . $key, 'X-Goog-FieldMask: routes.distanceMeters,routes.duration'],
        ]);
        $out = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = $out === false ? curl_error($ch) : null;
        curl_close($ch);
        if ($out === false) {
            Yii::error('Routes request failed: ' . $curlError, 'geo');
            throw new DistanceUnavailableException('NETWORK_ERROR');
        }
        $reply = json_decode($out, true);
        if (!is_array($reply)) {
            throw new DistanceUnavailableException('INVALID_RESPONSE');
        }
        if ($http === 403 || $http === 401 || ($reply['error']['status'] ?? '') === 'PERMISSION_DENIED') {
            Yii::warning('Routes returned ' . $http . ': ' . ($reply['error']['message'] ?? ''), 'geo');
            throw new DistanceUnavailableException('REQUEST_DENIED');
        }
        if ($http === 429) {
            throw new DistanceUnavailableException('OVER_QUERY_LIMIT');
        }
        if ($http !== 200) {
            Yii::warning('Routes returned ' . $http . ': ' . ($reply['error']['message'] ?? ''), 'geo');
            throw new DistanceUnavailableException('INVALID_RESPONSE');
        }
        $route = $reply['routes'][0] ?? null;
        if ($route === null || !isset($route['distanceMeters'])) {
            throw new DistanceUnavailableException('NO_ROUTE');
        }
        $meters = (int) $route['distanceMeters'];
        $seconds = (int) rtrim((string) ($route['duration'] ?? '0s'), 's');

        return $this->result(number_format($meters / 1000, 1) . ' km', $seconds >= 3600 ? floor($seconds / 3600) . ' hour ' . round(($seconds % 3600) / 60) . ' mins' : max(1, round($seconds / 60)) . ' mins', $meters);
    }

    private function viaDirections($key, $lat1, $long1, $lat2, $long2)
    {
        $reply = $this->request('https://maps.googleapis.com/maps/api/directions/json', [
            'origin' => $lat1 . ',' . $long1,
            'destination' => $lat2 . ',' . $long2,
            'mode' => 'driving',
            'key' => $key,
        ], 'Directions');

        $leg = $reply['routes'][0]['legs'][0] ?? null;
        if ($leg === null || !isset($leg['distance']['value'])) {
            throw new DistanceUnavailableException('NO_ROUTE');
        }

        return $this->result($leg['distance']['text'], $leg['duration']['text'] ?? '', (int) $leg['distance']['value']);
    }

    private function viaDistanceMatrix($key, $lat1, $long1, $lat2, $long2)
    {
        $reply = $this->request('https://maps.googleapis.com/maps/api/distancematrix/json', [
            'origins' => $lat1 . ',' . $long1,
            'destinations' => $lat2 . ',' . $long2,
            'mode' => 'driving',
            'key' => $key,
        ], 'Distance Matrix');

        $element = $reply['rows'][0]['elements'][0] ?? null;
        if ($element === null || ($element['status'] ?? '') !== 'OK') {
            throw new DistanceUnavailableException($element['status'] ?? 'NO_RESULT');
        }

        return $this->result($element['distance']['text'], $element['duration']['text'] ?? '', (int) $element['distance']['value']);
    }

    private function result($distanceText, $timeText, $meters)
    {
        return [
            'status' => 'OK',
            'distance' => $distanceText,
            'time' => $timeText,
            'meters' => $meters,
            'km' => $meters / 1000,
            'distvalue' => $meters,
        ];
    }

    /**
     * One HTTPS call to a Google JSON API; returns the decoded reply when its status is OK.
     */
    private function request($endpoint, array $query, $label)
    {
        $ch = curl_init($endpoint . '?' . http_build_query($query));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
        ]);
        $body = curl_exec($ch);
        $curlError = $body === false ? curl_error($ch) : null;
        curl_close($ch);

        if ($body === false) {
            Yii::error($label . ' request failed: ' . $curlError, 'geo');
            throw new DistanceUnavailableException('NETWORK_ERROR');
        }

        $reply = json_decode($body, true);
        $status = is_array($reply) ? ($reply['status'] ?? 'INVALID_RESPONSE') : 'INVALID_RESPONSE';
        if ($status !== 'OK') {
            // e.g. REQUEST_DENIED: the key may not use this API
            Yii::warning($label . ' returned ' . $status . ': ' . ($reply['error_message'] ?? ''), 'geo');
            throw new DistanceUnavailableException($status === 'ZERO_RESULTS' ? 'NO_ROUTE' : $status);
        }

        return $reply;
    }

    private function apiKey()
    {
        // one place decides which key the server uses: dashboard, environment, older config file, older map_key setting
        return \app\modules\geo\services\GoogleKeys::serverKey();
    }
}

/**
 * Thrown when a distance cannot be computed. The message is the Google status or a short code; callers show a
 * neutral text to the user and the details are written to the "geo" log category.
 */
class DistanceUnavailableException extends \RuntimeException
{
    /** @var string Google status or short code, for logs and support (not shown to riders) */
    public $reason;

    // [doc:start] __construct - Construct
    public function __construct($reason)
    {
        $this->reason = $reason;
        parent::__construct('We could not calculate the distance right now. Please try again in a few minutes.');
    }
    // [doc:end] __construct
}
