<?php

namespace app\modules\geo\services;

use app\modules\admin\models\Integrations;

/**
 * Ola Maps (Krutrim Maps). One API key (Integrations > Maps and routes > Ola Maps API key) serves both the Geocoding and the Directions API.
 * OLA_MAPS_API_BASE can point to another address (tests).
 *
 * Directions: POST {base}/routing/v1/directions?origin=lat,lng&destination=lat,lng&api_key=KEY  -> routes[0].legs[0].distance (metres) and .duration (seconds)
 * Geocode:    GET  {base}/places/v1/geocode?address=...&api_key=KEY -> geocodingResults[0] (formatted_address, geometry.location, address_components)
 *
 * The answers are read defensively (a number or a {value: number} object for distance and duration) because the provider's reply
 * is not under this system's control.
 */
class OlaMapProvider extends MapProvider
{
    // [doc:start] id - Id
    public function id()
    {
        return 'ola';
    }
    // [doc:end] id

    // [doc:start] label - Label
    public function label()
    {
        return 'Ola Maps';
    }
    // [doc:end] label

    private function key()
    {
        return Integrations::value('ola_maps_api_key');
    }

    private function base()
    {
        return rtrim(getenv('OLA_MAPS_API_BASE') ?: 'https://api.olamaps.io', '/');
    }

    // [doc:start] isConfigured - Is Configured
    public function isConfigured()
    {
        return $this->key() !== '';
    }
    // [doc:end] isConfigured

    /** a plain number, or an object such as {"value": 1234}, as a number */
    private static function number($v)
    {
        if (is_array($v)) {
            $v = $v['value'] ?? ($v['meters'] ?? ($v['seconds'] ?? null));
        }

        return is_numeric($v) ? (float) $v : null;
    }

    /** [http status, decoded JSON] of a POST with the key in the query string */
    private function postJson($url)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => '', CURLOPT_HTTPHEADER => ['Content-Length: 0', 'X-Request-Id: ' . bin2hex(random_bytes(8))], CURLOPT_USERAGENT => 'RubiaServices/1.0']);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$code, $body ? json_decode($body, true) : null];
    }

    // [doc:start] geocode - Geocode
    public function geocode($address)
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'unusable' => true, 'message' => 'The Ola Maps API key is not set.'];
        }
        [$code, $json] = $this->getJson($this->base() . '/places/v1/geocode?' . http_build_query(['address' => $address, 'api_key' => $this->key()]));
        if ($code === 401 || $code === 403) {
            return ['ok' => false, 'unusable' => true, 'message' => 'Ola Maps refused the API key.'];
        }
        if (!is_array($json)) {
            return ['ok' => false, 'unusable' => true, 'message' => 'Ola Maps did not answer.'];
        }
        $r = $json['geocodingResults'][0] ?? null;
        $loc = $r['geometry']['location'] ?? null;
        if ($r === null || !isset($loc['lat'], $loc['lng'])) {
            return ['ok' => false, 'message' => 'Ola Maps could not find this address.'];
        }
        $locality = '';
        $pincode = '';
        foreach (($r['address_components'] ?? []) as $c) {
            $types = (array) ($c['types'] ?? []);
            $name = $c['long_name'] ?? ($c['short_name'] ?? '');
            if ($locality === '' && (in_array('locality', $types, true) || in_array('sublocality', $types, true))) {
                $locality = $name;
            }
            if ($pincode === '' && in_array('postal_code', $types, true)) {
                $pincode = $name;
            }
        }

        return ['ok' => true, 'lat' => (float) $loc['lat'], 'lng' => (float) $loc['lng'], 'formatted' => $r['formatted_address'] ?? '', 'locality' => $locality, 'pincode' => $pincode];
    }
    // [doc:end] geocode

    // [doc:start] route - Route
    public function route($lat1, $lng1, $lat2, $lng2)
    {
        if (!$this->isConfigured()) {
            throw new DistanceUnavailableException('NO_API_KEY');
        }
        [$code, $json] = $this->postJson($this->base() . '/routing/v1/directions?' . http_build_query([
            'origin' => (float) $lat1 . ',' . (float) $lng1, 'destination' => (float) $lat2 . ',' . (float) $lng2,
            'alternatives' => 'false', 'steps' => 'false', 'overview' => 'false', 'api_key' => $this->key(),
        ]));
        if ($code === 401 || $code === 403) {
            throw new DistanceUnavailableException('REQUEST_DENIED');
        }
        if ($code === 429) {
            throw new DistanceUnavailableException('OVER_QUERY_LIMIT');
        }
        if (!is_array($json)) {
            throw new DistanceUnavailableException('INVALID_RESPONSE');
        }
        $status = strtoupper((string) ($json['status'] ?? ''));
        if ($status === 'ZERO_RESULTS' || $status === 'NOT_FOUND') {
            throw new DistanceUnavailableException('NO_ROUTE');
        }
        $route = $json['routes'][0] ?? null;
        $leg = $route['legs'][0] ?? null;
        $meters = self::number($leg['distance'] ?? ($leg['distanceMeters'] ?? ($route['distance'] ?? null)));
        $seconds = self::number($leg['duration'] ?? ($route['duration'] ?? null));
        if ($meters === null) {
            throw new DistanceUnavailableException('INVALID_RESPONSE');
        }

        return $this->answer(round($meters), $seconds ?? 0);
    }
    // [doc:end] route
}
