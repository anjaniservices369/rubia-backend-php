<?php

namespace app\modules\geo\services;

use Yii;
use yii\db\Query;

/**
 * The service modules of the platform. Each one has its own dashboard menu, its own maps, its own pricing,
 * its own franchises and its own billing.
 *
 *   1 DRIVER   hire a driver for N hours        (booking, driver_requests, driver_earnings, city_rate_card)
 *   2 TAXI     car taxi                          (ride_request, ride_earnings, taxi_payout, ride_commision)
 *   3 AUTO     auto rickshaw                     (same tables, told apart by the ride's vehicle type)
 *   4 BIKE     bike taxi                         (same tables, told apart by the ride's vehicle type)
 *
 * The numbers are the ones already used by the dashboard menus (\app\modules\geo\services\ServiceModules::DRIVER/TAXI/AUTO/BIKE), by
 * `ride_commision.vehicle_id` and by `user.vehicle_id` (the module a staff or franchise account works in).
 * A row of `vehicals` (id 4 Bike, 5 Auto, 6 Taxi) belongs to a module through `vehicals.vehicle_type`.
 */
class ServiceModules
{
    const DRIVER = 1;
    const TAXI = 2;
    const AUTO = 3;
    const BIKE = 4;

    // [doc:start] all - All
    /** @return array module id => [key, title, service, tripTable] */
    public static function all()
    {
        return [
            self::DRIVER => ['key' => 'haltingdriver', 'title' => 'Halting drivers', 'service' => 'haltingdriver', 'icon' => 'fas fa-user-tie'],
            self::TAXI => ['key' => 'cartaxi', 'title' => 'Car taxi', 'service' => 'cartaxi', 'icon' => 'fas fa-taxi'],
            self::AUTO => ['key' => 'autotaxi', 'title' => 'Auto taxi', 'service' => 'autotaxi', 'icon' => 'fas fa-shuttle-van'],
            self::BIKE => ['key' => 'biketaxi', 'title' => 'Bike taxi', 'service' => 'biketaxi', 'icon' => 'fas fa-motorcycle'],
        ];
    }
    // [doc:end] all

    // [doc:start] titles - id => title of every service, for dropdowns.
    /** id => title of every service, for dropdowns. */
    public static function titles()
    {
        $out = [];
        foreach (self::all() as $id => $m) {
            $out[$id] = $m['title'];
        }

        return $out;
    }
    // [doc:end] titles

    // [doc:start] exists - Exists
    public static function exists($module)
    {
        return isset(self::all()[(int) $module]);
    }
    // [doc:end] exists

    // [doc:start] title - Title
    public static function title($module)
    {
        return self::all()[(int) $module]['title'] ?? ('Module ' . (int) $module);
    }
    // [doc:end] title

    // [doc:start] isTaxi - True for the three taxi modules, false for the driver service.
    /** True for the three taxi modules, false for the driver service. */
    public static function isTaxi($module)
    {
        return in_array((int) $module, [self::TAXI, self::AUTO, self::BIKE], true);
    }
    // [doc:end] isTaxi

    // [doc:start] ofVehicle - Module of a vehicle type row (`vehicals.id`).
    /** Module of a vehicle type row (`vehicals.id`). */
    public static function ofVehicle($vehicalId)
    {
        static $cache = [];
        $vehicalId = (int) $vehicalId;
        if (!isset($cache[$vehicalId])) {
            $cache[$vehicalId] = (int) (new Query())->select('vehicle_type')->from('vehicals')->where(['id' => $vehicalId])->scalar();
        }

        return $cache[$vehicalId];
    }
    // [doc:end] ofVehicle

    // [doc:start] pincodeVehicleIds - The values `pincode.vehical_id` can hold for a taxi module: its vehicle type ids and, for rows written before the.
    /**
     * The values `pincode.vehical_id` can hold for a taxi module: its vehicle type ids and, for rows written before the
     * vehicle catalogue was renumbered (car 2, auto 3, bike 4), the module number itself.
     */
    public static function pincodeVehicleIds($module)
    {
        $ids = array_keys(self::vehicles($module));
        if (self::isTaxi($module)) {
            $ids[] = (int) $module;
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }
    // [doc:end] pincodeVehicleIds

    // [doc:start] vehicleIds - ids of the sellable vehicle types (`vehicals.id`) of a taxi module.
    /** ids of the sellable vehicle types (`vehicals.id`) of a taxi module. */
    public static function vehicleIds($module)
    {
        return array_map('intval', (new Query())->select('id')->from('vehicals')
            ->where(['vehicle_type' => (int) $module, 'status' => 1])->column());
    }
    // [doc:end] vehicleIds

    // [doc:start] vehicles - id => title of the sellable vehicle types of a taxi module.
    /** id => title of the sellable vehicle types of a taxi module. */
    public static function vehicles($module)
    {
        return (new Query())->select('title')->indexBy('id')->from('vehicals')
            ->where(['vehicle_type' => (int) $module, 'status' => 1])->column();
    }
    // [doc:end] vehicles

    // [doc:start] boundary - The saved service area of a module in a city, as a list of [lat, lng], or null when none was saved.
    /**
     * The saved service area of a module in a city, as a list of [lat, lng], or null when none was saved.
     * The driver service falls back to the city's own boundary (how it always worked).
     */
    public static function boundary($module, $cityId)
    {
        $raw = (new Query())->select('boundary')->from('service_map')
            ->where(['module' => (int) $module, 'city_id' => (int) $cityId])->scalar();
        if (!$raw && (int) $module === self::DRIVER) {
            $raw = (new Query())->select('boundary')->from('city')->where(['id' => (int) $cityId])->scalar();
        }

        return self::decode($raw);
    }
    // [doc:end] boundary

    // [doc:start] decode - Decode
    public static function decode($raw)
    {
        if (empty($raw)) {
            return null;
        }
        $once = json_decode($raw, true);
        $list = is_string($once) ? json_decode($once, true) : $once;

        return (is_array($list) && count($list) >= 3) ? $list : null;
    }
    // [doc:end] decode

    // [doc:start] inside - Whether a point is inside a polygon of [lat, lng] pairs (ray casting).
    /** Whether a point is inside a polygon of [lat, lng] pairs (ray casting). */
    public static function inside($lat, $lng, array $polygon)
    {
        $in = false;
        $n = count($polygon);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $yi = $polygon[$i][0];
            $xi = $polygon[$i][1];
            $yj = $polygon[$j][0];
            $xj = $polygon[$j][1];
            if ((($yi > $lat) !== ($yj > $lat)) && ($lng < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 1e-12) + $xi)) {
                $in = !$in;
            }
        }

        return $in;
    }
    // [doc:end] inside

    // [doc:start] requested - The service a request is about: the `module` request parameter if it names a real service, else the service.
    /**
     * The service a request is about: the `module` request parameter if it names a real service, else the service
     * the API address is bound to (/api/bike ...), else $default.
     */
    public static function requested($default = self::DRIVER)
    {
        $m = (int) Yii::$app->request->get('module', Yii::$app->request->post('module', 0));
        if ($m && self::exists($m)) {
            return $m;
        }
        $bound = ServiceScope::module();

        return $bound ?: (int) $default;
    }
    // [doc:end] requested

    // [doc:start] setting - A web setting of one service: `<key>_<module>` when it exists, else the single legacy `<key>`.
    /**
     * A web setting of one service: `<key>_<module>` when it exists, else the single legacy `<key>`.
     */
    public static function setting($key, $module)
    {
        $settings = new \app\modules\admin\models\WebSetting();
        $v = $settings->getSettingBykey($key . '_' . (int) $module);
        if ($v === null || $v === '') {
            $v = $settings->getSettingBykey($key);
        }

        return $v;
    }
    // [doc:end] setting
}
