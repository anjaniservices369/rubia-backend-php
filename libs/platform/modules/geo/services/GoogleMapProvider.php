<?php

namespace app\modules\geo\services;

/**
 * Google Maps. Server keys: Integrations > Maps and routes (then GOOGLE_MAPS_SERVER_KEY, config/secrets.php, the `map_key` setting); every key is tried,
 * a key Google refuses is skipped for the next one. The key's project needs the Geocoding API (addresses) and the Routes API (distances) switched on.
 */
class GoogleMapProvider extends MapProvider
{
    // [doc:start] id - Id
    public function id()
    {
        return 'google';
    }
    // [doc:end] id

    // [doc:start] label - Label
    public function label()
    {
        return 'Google Maps';
    }
    // [doc:end] label

    // [doc:start] isConfigured - Is Configured
    public function isConfigured()
    {
        return GoogleKeys::serverKeys() !== [];
    }
    // [doc:end] isConfigured

    // [doc:start] geocode - Geocode
    public function geocode($address)
    {
        $keys = GoogleKeys::serverKeys();
        if (!$keys) {
            return ['ok' => false, 'unusable' => true, 'message' => 'The Google Maps server key is not set.'];
        }
        $base = getenv('GEOCODE_API_BASE') ?: 'https://maps.googleapis.com/maps/api/geocode/json';
        $json = null;
        foreach ($keys as $key) {
            [, $json] = $this->getJson($base . '?' . http_build_query(['address' => $address, 'components' => 'country:IN', 'key' => $key]));
            // a key whose project does not have the Geocoding API is skipped for the next key
            if (is_array($json) && ($json['status'] ?? '') !== 'REQUEST_DENIED') {
                break;
            }
        }
        if (!is_array($json) || !isset($json['status'])) {
            return ['ok' => false, 'unusable' => true, 'message' => 'Google did not answer.'];
        }
        if ($json['status'] === 'REQUEST_DENIED') {
            return ['ok' => false, 'unusable' => true, 'message' => 'Google refused the key: switch on the Geocoding API for its project in Google Cloud.'];
        }
        if ($json['status'] === 'ZERO_RESULTS') {
            return ['ok' => false, 'message' => 'Google could not find this address.'];
        }
        if ($json['status'] !== 'OK' || empty($json['results'][0]['geometry']['location'])) {
            return ['ok' => false, 'unusable' => true, 'message' => 'Google answered ' . $json['status'] . '.'];
        }
        $r = $json['results'][0];
        $locality = '';
        $fallback = '';
        $pincode = '';
        foreach ($r['address_components'] ?? [] as $c) {
            $types = $c['types'] ?? [];
            if ($locality === '' && in_array('locality', $types, true)) {
                $locality = $c['long_name'];
            }
            if ($fallback === '' && in_array('administrative_area_level_2', $types, true)) {
                $fallback = $c['long_name'];
            }
            if (in_array('postal_code', $types, true)) {
                $pincode = $c['long_name'];
            }
        }

        return [
            'ok' => true, 'lat' => (float) $r['geometry']['location']['lat'], 'lng' => (float) $r['geometry']['location']['lng'],
            'formatted' => $r['formatted_address'] ?? '', 'locality' => $locality !== '' ? $locality : $fallback, 'pincode' => $pincode,
        ];
    }
    // [doc:end] geocode

    // [doc:start] route - Route
    public function route($lat1, $lng1, $lat2, $lng2)
    {
        return (new DrivingDistance())->googleRoute($lat1, $lng1, $lat2, $lng2);
    }
    // [doc:end] route
}
