<?php

namespace app\modules\geo\services;

use app\models\User;
use yii\db\Query;
use yii\helpers\Json;

/**
 * The city of a person is not typed in: it is found from the written address. The address is turned into a point (latitude and longitude)
 * by the maps services in use (Integrations > Maps and routes: Google, Mapbox, OpenStreetMap, in the order set there), and the city is the active city whose boundary contains that point
 * (the same boundaries the booking uses). If the point lies in no boundary, the city whose name equals the town Google reports is used.
 *
 * The profile pages (Drivers-profile and Users-profile) call applyTo() when they save an address.
 */
class AddressCity
{
    // [doc:start] locate - Locate
    /**
     * @return array ['ok' => true, 'lat', 'lng', 'formatted', 'locality', 'pincode', 'source'] or ['ok' => false, 'message']
     */
    public static function locate($address)
    {
        $address = trim((string) $address);
        if ($address === '') {
            return ['ok' => false, 'message' => 'There is no address.'];
        }

        return MapProviders::geocode($address);
    }
    // [doc:end] locate

    // [doc:start] inside - Inside
    public static function inside($lat, $lng, array $polygon)
    {
        $n = count($polygon);
        $in = false;
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            if (($polygon[$i][1] > $lng) !== ($polygon[$j][1] > $lng)
                && $lat < ($polygon[$j][0] - $polygon[$i][0]) * ($lng - $polygon[$i][1]) / ($polygon[$j][1] - $polygon[$i][1]) + $polygon[$i][0]) {
                $in = !$in;
            }
        }

        return $in;
    }
    // [doc:end] inside

    // [doc:start] cityAt - City At
    public static function cityAt($lat, $lng, $locality = '')
    {
        $cities = (new Query())->select(['id', 'name', 'boundary'])->from('city')->where(['status' => 1])->all();
        foreach ($cities as $c) {
            if (empty($c['boundary'])) {
                continue;
            }
            try {
                $b = Json::decode($c['boundary']);
                $polygon = is_string($b) ? json_decode($b, true) : $b;
            } catch (\Throwable $e) {
                continue;
            }
            if (is_array($polygon) && count($polygon) > 2 && self::inside($lat, $lng, $polygon)) {
                return [(int) $c['id'], trim($c['name'])];
            }
        }
        if ($locality !== '') {
            foreach ($cities as $c) {
                if (strcasecmp(trim($c['name']), trim($locality)) === 0) {
                    return [(int) $c['id'], trim($c['name'])];
                }
            }
        }

        return null;
    }
    // [doc:end] cityAt

    // [doc:start] applyTo - Does not save the user: the caller saves. When the address is empty the point and the city are cleared. When the address cannot be.
    /**
     * Does not save the user: the caller saves. When the address is empty the point and the city are cleared. When the address cannot be
     * located the old point and city are cleared too (they would belong to another address) and the reason is returned.
     *
     * @return string '' when the city was found, else why not
     */
    public static function applyTo(User $u)
    {
        $address = trim((string) $u->address);
        $u->lat = null;
        $u->lng = null;
        $u->city_id = null;
        $u->city_name = null;
        if ($address === '') {
            return '';
        }
        $p = self::locate($address);
        if (!$p['ok']) {
            return $p['message'];
        }
        $u->lat = (string) $p['lat'];
        $u->lng = (string) $p['lng'];
        $city = self::cityAt($p['lat'], $p['lng'], $p['locality']);
        if ($city === null) {
            return 'The address is at ' . ($p['formatted'] ?: 'an unknown place') . ', which is outside every city of the service.';
        }
        [$u->city_id, $u->city_name] = $city;

        return '';
    }
    // [doc:end] applyTo
}
