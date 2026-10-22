<?php

namespace app\modules\geo\services;

/**
 * A maps service the system can ask two questions: "where is this address?" (geocode) and "how far and how long by road?" (route).
 *
 * Google, Mapbox and OpenStreetMap are built in (see MapProviders). To use another service later (HERE, Ola Maps, TomTom ...): write a class that extends this
 * one, answer the two questions in the shapes described below, put its key box in the "Maps and routes" integration (Integrations) and add it to MapProviders::all().
 * Nothing else in the system changes: bookings, fares and the city-from-address lookup go through MapProviders.
 */
abstract class MapProvider
{
    /** @return string short code used in the "order" setting, for example google */
    abstract public function id();

    /** @return string name shown in the dashboard */
    abstract public function label();

    /** @return bool whether the key this service needs is set (a service without a key is skipped) */
    abstract public function isConfigured();

    // [doc:start] canRoute - Can Route
    /** @return bool whether it can give road distances (some free services only find addresses) */
    public function canRoute()
    {
        return true;
    }
    // [doc:end] canRoute

    /**
     * Where is this address?
     *
     * @return array ['ok' => true, 'lat', 'lng', 'formatted', 'locality' (town), 'pincode']
     *               or ['ok' => false, 'message', 'unusable' => true when the service itself cannot be used (key refused, no answer)]
     */
    abstract public function geocode($address);

    /**
     * Road distance and time between two points.
     *
     * @return array ['status' => 'OK', 'distance' => text, 'time' => text, 'meters', 'km', 'distvalue']
     * @throws DistanceUnavailableException with reason REQUEST_DENIED / OVER_QUERY_LIMIT / INVALID_RESPONSE / NETWORK_ERROR when this service cannot answer
     *                                      (the next service is tried), or NO_ROUTE when there is no road (final)
     */
    abstract public function route($lat1, $lng1, $lat2, $lng2);

    /** @return array [http status, decoded JSON or null] */
    protected function getJson($url, array $headers = [])
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_HTTPHEADER => $headers, CURLOPT_USERAGENT => 'RubiaServices/1.0']);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$code, $body ? json_decode($body, true) : null];
    }

    /** the meters and seconds as the text the apps show */
    protected function answer($meters, $seconds)
    {
        $meters = (int) $meters;
        $seconds = (int) $seconds;
        $time = $seconds >= 3600 ? floor($seconds / 3600) . ' hour ' . round(($seconds % 3600) / 60) . ' mins' : max(1, (int) round($seconds / 60)) . ' mins';

        return [
            'status' => 'OK', 'distance' => number_format($meters / 1000, 1) . ' km', 'time' => $time,
            'meters' => $meters, 'km' => $meters / 1000, 'distvalue' => $meters,
        ];
    }
}
