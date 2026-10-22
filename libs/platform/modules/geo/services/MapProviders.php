<?php

namespace app\modules\geo\services;

use app\modules\admin\models\Integrations;

/**
 * The maps services in use and the order they are asked in (Integrations > Maps and routes > "Order of the map services", for example
 * "google,mapbox,openstreetmap"). A service without its key, or one that refuses or does not answer, is skipped for the next one, so a new
 * key or a new service never stops bookings that another service can still serve.
 *
 * Asking: geocode() for addresses, route() for road distance and time. To add a service, extend MapProvider and list it in all().
 */
class MapProviders
{
    const DEFAULT_ORDER = 'google,mapbox,openstreetmap';

    // [doc:start] all - All
    /**
     * @return MapProvider[] code => provider
     */
    public static function all()
    {
        $list = [new GoogleMapProvider(), new MapboxMapProvider(), new OpenStreetMapProvider()];
        $out = [];
        foreach ($list as $p) {
            $out[$p->id()] = $p;
        }

        return $out;
    }
    // [doc:end] all

    // [doc:start] ordered - Ordered
    /**
     * @param bool $routing only the services that give road distances
     * @return MapProvider[]
     */
    public static function ordered($routing = false)
    {
        $all = self::all();
        $codes = array_filter(array_map('trim', explode(',', strtolower(Integrations::value('maps_provider_order') ?: self::DEFAULT_ORDER))));
        $out = [];
        foreach ($codes as $code) {
            if (isset($all[$code]) && !isset($out[$code]) && $all[$code]->isConfigured() && (!$routing || $all[$code]->canRoute())) {
                $out[$code] = $all[$code];
            }
        }

        return array_values($out);
    }
    // [doc:end] ordered

    // [doc:start] geocode - Geocode
    /**
     * @return array ['ok' => true, 'lat', 'lng', 'formatted', 'locality', 'pincode', 'source'] or ['ok' => false, 'message']
     */
    public static function geocode($address)
    {
        $messages = [];
        foreach (self::ordered() as $p) {
            $r = $p->geocode($address);
            if ($r['ok']) {
                $r['source'] = $p->id();

                return $r;
            }
            $messages[] = $r['message'];
        }

        return ['ok' => false, 'message' => $messages ? implode(' ', $messages) : 'No maps service is set up (Integrations > Maps and routes).'];
    }
    // [doc:end] geocode

    // [doc:start] route - Route
    /**
     * @return array see MapProvider::route
     * @throws DistanceUnavailableException when no service can answer
     */
    public static function route($lat1, $lng1, $lat2, $lng2)
    {
        $services = self::ordered(true);
        if (!$services) {
            throw new DistanceUnavailableException('NO_API_KEY');
        }
        $last = 'NO_PROVIDER';
        foreach ($services as $p) {
            try {
                return $p->route($lat1, $lng1, $lat2, $lng2);
            } catch (DistanceUnavailableException $e) {
                $last = $e->reason;
                if (!in_array($e->reason, ['REQUEST_DENIED', 'OVER_QUERY_LIMIT', 'INVALID_RESPONSE', 'NETWORK_ERROR', 'NO_API_KEY', 'NOT_SUPPORTED'], true)) {
                    throw $e; // no road between the points: another service will not find one
                }
            }
        }

        throw new DistanceUnavailableException($last);
    }
    // [doc:end] route
}
