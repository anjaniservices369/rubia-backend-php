<?php

namespace app\modules\geo\services;

use app\modules\admin\models\Integrations;

/**
 * Mapbox. One access token (Integrations > Maps and routes > Mapbox access token) serves both the Geocoding and the Directions API.
 * MAPBOX_API_BASE can point to another address (tests).
 */
class MapboxMapProvider extends MapProvider
{
    // [doc:start] id - Id
    public function id()
    {
        return 'mapbox';
    }
    // [doc:end] id

    // [doc:start] label - Label
    public function label()
    {
        return 'Mapbox';
    }
    // [doc:end] label

    private function token()
    {
        return Integrations::value('mapbox_access_token');
    }

    private function base()
    {
        return rtrim(getenv('MAPBOX_API_BASE') ?: 'https://api.mapbox.com', '/');
    }

    // [doc:start] isConfigured - Is Configured
    public function isConfigured()
    {
        return $this->token() !== '';
    }
    // [doc:end] isConfigured

    // [doc:start] geocode - Geocode
    public function geocode($address)
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'unusable' => true, 'message' => 'The Mapbox access token is not set.'];
        }
        [$code, $json] = $this->getJson($this->base() . '/geocoding/v5/mapbox.places/' . rawurlencode($address) . '.json?' . http_build_query([
            'access_token' => $this->token(), 'country' => 'in', 'limit' => 1,
        ]));
        if ($code === 401 || $code === 403) {
            return ['ok' => false, 'unusable' => true, 'message' => 'Mapbox refused the access token.'];
        }
        if (!is_array($json)) {
            return ['ok' => false, 'unusable' => true, 'message' => 'Mapbox did not answer.'];
        }
        $f = $json['features'][0] ?? null;
        if ($f === null || empty($f['center'])) {
            return ['ok' => false, 'message' => 'Mapbox could not find this address.'];
        }
        $locality = '';
        $pincode = '';
        foreach (array_merge([$f], $f['context'] ?? []) as $c) {
            $id = (string) ($c['id'] ?? '');
            if ($locality === '' && (strpos($id, 'place.') === 0 || strpos($id, 'locality.') === 0)) {
                $locality = $c['text'] ?? '';
            }
            if ($pincode === '' && strpos($id, 'postcode.') === 0) {
                $pincode = $c['text'] ?? '';
            }
        }

        return ['ok' => true, 'lat' => (float) $f['center'][1], 'lng' => (float) $f['center'][0], 'formatted' => $f['place_name'] ?? '', 'locality' => $locality, 'pincode' => $pincode];
    }
    // [doc:end] geocode

    // [doc:start] route - Route
    public function route($lat1, $lng1, $lat2, $lng2)
    {
        if (!$this->isConfigured()) {
            throw new DistanceUnavailableException('NO_API_KEY');
        }
        [$code, $json] = $this->getJson($this->base() . '/directions/v5/mapbox/driving/' . (float) $lng1 . ',' . (float) $lat1 . ';' . (float) $lng2 . ',' . (float) $lat2 . '?' . http_build_query([
            'access_token' => $this->token(), 'overview' => 'false',
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
        if (($json['code'] ?? '') === 'NoRoute' || ($json['code'] ?? '') === 'NoSegment') {
            throw new DistanceUnavailableException('NO_ROUTE');
        }
        $r = $json['routes'][0] ?? null;
        if (($json['code'] ?? '') !== 'Ok' || $r === null || !isset($r['distance'])) {
            throw new DistanceUnavailableException('INVALID_RESPONSE');
        }

        return $this->answer(round($r['distance']), $r['duration'] ?? 0);
    }
    // [doc:end] route
}
