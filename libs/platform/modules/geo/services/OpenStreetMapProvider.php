<?php

namespace app\modules\geo\services;

/**
 * OpenStreetMap's free address search (Nominatim). No key. It finds addresses only (it gives no road distances for fares), is less exact for Indian addresses
 * than Google or Mapbox, and its usage policy allows about one request a second: fine for the occasional "city from address" lookup, not for bookings.
 * NOMINATIM_URL can point to another server (your own, or a test).
 */
class OpenStreetMapProvider extends MapProvider
{
    // [doc:start] id - Id
    public function id()
    {
        return 'openstreetmap';
    }
    // [doc:end] id

    // [doc:start] label - Label
    public function label()
    {
        return 'OpenStreetMap (free, addresses only)';
    }
    // [doc:end] label

    // [doc:start] isConfigured - Is Configured
    public function isConfigured()
    {
        return true;
    }
    // [doc:end] isConfigured

    // [doc:start] canRoute - Can Route
    public function canRoute()
    {
        return false;
    }
    // [doc:end] canRoute

    // [doc:start] geocode - Geocode
    public function geocode($address)
    {
        [, $rows] = $this->getJson((getenv('NOMINATIM_URL') ?: 'https://nominatim.openstreetmap.org/search') . '?' . http_build_query([
            'q' => $address, 'format' => 'jsonv2', 'addressdetails' => 1, 'countrycodes' => 'in', 'limit' => 1,
        ]));
        if (!is_array($rows)) {
            return ['ok' => false, 'unusable' => true, 'message' => 'OpenStreetMap did not answer.'];
        }
        if (!$rows || !isset($rows[0]['lat'], $rows[0]['lon'])) {
            return ['ok' => false, 'message' => 'OpenStreetMap could not find this address.'];
        }
        $a = $rows[0]['address'] ?? [];
        $locality = $a['city'] ?? $a['town'] ?? $a['village'] ?? $a['municipality'] ?? $a['state_district'] ?? $a['county'] ?? '';

        return [
            'ok' => true, 'lat' => (float) $rows[0]['lat'], 'lng' => (float) $rows[0]['lon'], 'formatted' => $rows[0]['display_name'] ?? '',
            'locality' => $locality, 'pincode' => $a['postcode'] ?? '',
        ];
    }
    // [doc:end] geocode

    // [doc:start] route - Route
    public function route($lat1, $lng1, $lat2, $lng2)
    {
        throw new DistanceUnavailableException('NOT_SUPPORTED');
    }
    // [doc:end] route
}
