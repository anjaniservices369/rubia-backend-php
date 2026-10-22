<?php

namespace app\modules\geo\services;

use app\models\User;
use Yii;
use yii\db\Query;
use yii\helpers\Json;

/**
 * Designs the map of ONE service module in ONE city from the dashboard: its pincodes, service area, pricing and
 * franchise. Every method takes the module (see ServiceModules): the driver service, taxi, auto and bike each have
 * their own map of the same city and changing one never touches another.
 *
 *   PINCODES   taxi modules: rows of `pincode` (pincode + vehicle type) for that module's vehicle types;
 *              driver service: rows of `service_map_pincode`. A pincode decides which city a ride belongs to, and so
 *              the pricing, commission and franchise that apply. It can be added, removed or moved to another city
 *              for ONE module without affecting the others.
 *   AREA       `service_map` (module, city): the outline customers must be inside. Built from the pincodes
 *              (PincodeAreas) or kept as drawn. The driver service falls back to the city's own boundary.
 *   PRICING    driver service: `city_rate_card`; taxi modules: `ride_commision` rows whose vehicle_id is the module.
 *              A new map can start from a copy of another city's pricing in the SAME module.
 *   FRANCHISE  user accounts with role "city franchise": city_id = the city, vehicle_id = the module. They only see
 *              that module's data of that city (User::canManage, BookingSearch::managersearch, dashboard menu).
 *
 * Every change is written to `map_log` with the module. Methods return [ok => bool, message => string].
 */
class MapDesigner
{
    const STATUS_ACTIVE = 1;
    const STATUS_DELETE = 2;

    // [doc:start] parsePincodes - Pincodes can be typed with commas, spaces or new lines.
    /** Pincodes can be typed with commas, spaces or new lines. */
    public static function parsePincodes($input)
    {
        preg_match_all('/\b[0-9]{6}\b/', (string) $input, $m);

        return array_values(array_unique($m[0]));
    }
    // [doc:end] parsePincodes

    // ------------------------------------------------------------------ pincodes

    // [doc:start] addPincodes - Add Pincodes
    public static function addPincodes($module, $cityId, $input, array $vehicleIds, $adminId)
    {
        $codes = self::parsePincodes($input);
        if (!$codes) {
            return self::fail('Type at least one 6-digit pincode.');
        }
        $db = Yii::$app->db;
        $added = $already = $elsewhere = [];

        if (ServiceModules::isTaxi($module)) {
            $vehicles = ServiceModules::vehicles($module);
            $vehicleIds = array_values(array_intersect(array_map('intval', $vehicleIds), array_keys($vehicles)));
            if (!$vehicleIds) {
                return self::fail('Choose at least one vehicle type for the pincode.');
            }
            $own = array_keys($vehicles);
            foreach ($codes as $code) {
                // "belongs to another city" is checked inside THIS module only
                $other = (new Query())->select('city_id')->from('pincode')
                    ->where(['name' => $code, 'status' => self::STATUS_ACTIVE, 'vehical_id' => $own])
                    ->andWhere(['<>', 'city_id', (int) $cityId])->scalar();
                if ($other) {
                    $elsewhere[] = $code . ' (' . self::cityName($other) . ')';
                    continue;
                }
                $touched = false;
                foreach ($vehicleIds as $vid) {
                    $exists = (new Query())->from('pincode')->where(['name' => $code, 'city_id' => (int) $cityId, 'vehical_id' => $vid, 'status' => self::STATUS_ACTIVE])->exists();
                    if ($exists) {
                        continue;
                    }
                    $db->createCommand()->insert('pincode', [
                        'name' => $code, 'city_id' => (int) $cityId, 'vehical_id' => $vid, 'status' => self::STATUS_ACTIVE,
                        'created_on' => date('Y-m-d H:i:s'), 'updated_on' => date('Y-m-d H:i:s'), 'create_user_id' => $adminId,
                    ])->execute();
                    $touched = true;
                }
                $touched ? $added[] = $code : $already[] = $code;
            }
        } else {
            foreach ($codes as $code) {
                $other = (new Query())->select('city_id')->from('service_map_pincode')
                    ->where(['module' => (int) $module, 'pincode' => $code, 'status' => self::STATUS_ACTIVE])
                    ->andWhere(['<>', 'city_id', (int) $cityId])->scalar();
                if ($other) {
                    $elsewhere[] = $code . ' (' . self::cityName($other) . ')';
                    continue;
                }
                $exists = (new Query())->from('service_map_pincode')
                    ->where(['module' => (int) $module, 'pincode' => $code, 'city_id' => (int) $cityId, 'status' => self::STATUS_ACTIVE])->exists();
                if ($exists) {
                    $already[] = $code;
                    continue;
                }
                $db->createCommand()->insert('service_map_pincode', [
                    'module' => (int) $module, 'city_id' => (int) $cityId, 'pincode' => $code, 'status' => self::STATUS_ACTIVE,
                    'created_on' => date('Y-m-d H:i:s'),
                ])->execute();
                $added[] = $code;
            }
        }

        if ($added) {
            self::log($module, $cityId, 'pincode-add', 'Added ' . implode(', ', $added), $adminId);
        }
        $parts = [];
        if ($added) {
            $parts[] = 'Added: ' . implode(', ', $added) . '.';
        }
        if ($already) {
            $parts[] = 'Already in this map: ' . implode(', ', $already) . '.';
        }
        if ($elsewhere) {
            $parts[] = 'NOT added, ' . ServiceModules::title($module) . ' already serves them from another city (move them from there first): '
                . implode('; ', $elsewhere) . '.';
        }

        return ['ok' => (bool) $added || (bool) $already, 'message' => implode(' ', $parts)];
    }
    // [doc:end] addPincodes

    // [doc:start] removePincode - Remove Pincode
    public static function removePincode($module, $cityId, $pincode, $adminId)
    {
        $n = self::updatePincodeRows($module, (int) $cityId, (string) $pincode, ['status' => self::STATUS_DELETE], $adminId);
        if (!$n) {
            return self::fail('That pincode is not in this map.');
        }
        self::log($module, $cityId, 'pincode-remove', 'Removed ' . $pincode, $adminId);

        return self::ok('Removed ' . $pincode . ' from the ' . ServiceModules::title($module) . ' map of this city. Rides picked up there are no longer served by it.');
    }
    // [doc:end] removePincode

    // [doc:start] movePincode - Move Pincode
    public static function movePincode($module, $fromCityId, $pincode, $toCityId, $adminId)
    {
        $toCityId = (int) $toCityId;
        if ($toCityId === (int) $fromCityId) {
            return self::fail('Choose a different city.');
        }
        if (!(new Query())->from('city')->where(['id' => $toCityId])->exists()) {
            return self::fail('That city does not exist.');
        }
        $n = self::updatePincodeRows($module, (int) $fromCityId, (string) $pincode, ['city_id' => $toCityId], $adminId);
        if (!$n) {
            return self::fail('That pincode is not in this map.');
        }
        $to = self::cityName($toCityId);
        self::log($module, $fromCityId, 'pincode-move', 'Moved ' . $pincode . ' to ' . $to, $adminId);
        self::log($module, $toCityId, 'pincode-move', 'Received ' . $pincode . ' from ' . self::cityName($fromCityId), $adminId);

        return self::ok('Moved ' . $pincode . ' to ' . $to . ' for the ' . ServiceModules::title($module) . '. Its pricing, commission and franchise there are now those of ' . $to . '.');
    }
    // [doc:end] movePincode

    private static function updatePincodeRows($module, $cityId, $pincode, array $set, $adminId)
    {
        $db = Yii::$app->db;
        if (ServiceModules::isTaxi($module)) {
            return $db->createCommand()->update('pincode',
                $set + ['updated_on' => date('Y-m-d H:i:s'), 'update_user_id' => $adminId],
                ['name' => $pincode, 'city_id' => $cityId, 'vehical_id' => ServiceModules::pincodeVehicleIds($module), 'status' => [0, self::STATUS_ACTIVE]])->execute();
        }

        return $db->createCommand()->update('service_map_pincode', $set,
            ['module' => (int) $module, 'pincode' => $pincode, 'city_id' => $cityId, 'status' => self::STATUS_ACTIVE])->execute();
    }

    // [doc:start] pincodeNames - Distinct active pincodes of a module's map (used to draw its area).
    /** Distinct active pincodes of a module's map (used to draw its area). */
    public static function pincodeNames($module, $cityId)
    {
        if (ServiceModules::isTaxi($module)) {
            return (new Query())->select('name')->distinct()->from('pincode')
                ->where(['city_id' => (int) $cityId, 'status' => self::STATUS_ACTIVE, 'vehical_id' => ServiceModules::pincodeVehicleIds($module)])
                ->orderBy('name')->column();
        }

        return (new Query())->select('pincode')->distinct()->from('service_map_pincode')
            ->where(['module' => (int) $module, 'city_id' => (int) $cityId, 'status' => self::STATUS_ACTIVE])->orderBy('pincode')->column();
    }
    // [doc:end] pincodeNames

    // [doc:start] cityOfPincode - The city a ride's pickup pincode belongs to for a module, or null when the module does not serve it.
    /**
     * The city a ride's pickup pincode belongs to for a module, or null when the module does not serve it.
     */
    public static function cityOfPincode($module, $pincode)
    {
        if (ServiceModules::isTaxi($module)) {
            $id = (new Query())->select('city_id')->from('pincode')
                ->where(['name' => (string) $pincode, 'status' => self::STATUS_ACTIVE, 'vehical_id' => ServiceModules::pincodeVehicleIds($module)])->scalar();
        } else {
            $id = (new Query())->select('city_id')->from('service_map_pincode')
                ->where(['module' => (int) $module, 'pincode' => (string) $pincode, 'status' => self::STATUS_ACTIVE])->scalar();
        }

        return $id ? (int) $id : null;
    }
    // [doc:end] cityOfPincode

    // ------------------------------------------------------------------ area

    // [doc:start] saveBoundary - Save Boundary
    public static function saveBoundary($module, $cityId, array $hull)
    {
        if (count($hull) < 3) {
            return false;
        }
        $points = array_map(function ($p) {
            return [round($p[0], 7), round($p[1], 7)];
        }, $hull);
        // stored exactly like the hand-drawn boundaries: a JSON string that holds a JSON list of points
        $value = Json::encode(json_encode($points));
        $db = Yii::$app->db;
        $now = date('Y-m-d H:i:s');
        $exists = (new Query())->from('service_map')->where(['module' => (int) $module, 'city_id' => (int) $cityId])->exists();
        if ($exists) {
            $db->createCommand()->update('service_map', ['boundary' => $value, 'updated_on' => $now],
                ['module' => (int) $module, 'city_id' => (int) $cityId])->execute();
        } else {
            $db->createCommand()->insert('service_map', ['module' => (int) $module, 'city_id' => (int) $cityId,
                'boundary' => $value, 'created_on' => $now, 'updated_on' => $now])->execute();
        }
        if ((int) $module === ServiceModules::DRIVER) {
            // the driver service has always read city.boundary: keep it in step
            $db->createCommand()->update('city', ['boundary' => $value], ['id' => (int) $cityId])->execute();
        }

        return true;
    }
    // [doc:end] saveBoundary

    // ------------------------------------------------------------------ pricing

    // [doc:start] copyPricing - Copy Pricing
    public static function copyPricing($module, $toCityId, $fromCityId, $adminId)
    {
        $toCityId = (int) $toCityId;
        $fromCityId = (int) $fromCityId;
        if ($toCityId === $fromCityId) {
            return self::fail('Choose a different city to copy from.');
        }
        $db = Yii::$app->db;
        $taxi = ServiceModules::isTaxi($module);
        $table = $taxi ? 'ride_commision' : 'city_rate_card';
        $where = function ($city) use ($taxi, $module) {
            return $taxi
                ? ['city_id' => $city, 'vehicle_id' => (int) $module, 'status' => self::STATUS_ACTIVE]
                : ['city_id' => $city, 'status' => self::STATUS_ACTIVE];
        };
        if ((new Query())->from($table)->where($where($toCityId))->exists()) {
            return self::fail(ServiceModules::title($module) . ' already has pricing in this city. Edit it from the links on this page instead of copying over it.');
        }
        $rows = (new Query())->from($table)->where($where($fromCityId))->all();
        if (!$rows) {
            return self::fail('The city you chose has no ' . ServiceModules::title($module) . ' pricing to copy.');
        }
        $tx = $db->beginTransaction();
        try {
            foreach ($rows as $r) {
                unset($r['id']);
                $r['city_id'] = $toCityId;
                $r['created_on'] = $r['updated_on'] = date('Y-m-d H:i:s');
                $r['create_user_id'] = $adminId;
                $r['update_user_id'] = $adminId;
                $db->createCommand()->insert($table, $r)->execute();
            }
            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            Yii::error('copy pricing failed: ' . $e->getMessage(), 'api');

            return self::fail('Could not copy the pricing. Nothing was changed.');
        }
        self::log($module, $toCityId, 'pricing-copy', 'Copied ' . count($rows) . ' pricing row(s) from ' . self::cityName($fromCityId), $adminId);

        return self::ok('Copied ' . count($rows) . ' pricing row(s) from ' . self::cityName($fromCityId) . '. Edit them to suit this map.');
    }
    // [doc:end] copyPricing

    // ------------------------------------------------------------------ franchise

    // [doc:start] assignFranchise - Assign Franchise
    public static function assignFranchise($module, $cityId, $userId, $adminId)
    {
        $u = User::findOne((int) $userId);
        if (!$u || $u->user_role !== User::ROLE_FRANCHISE) {
            return self::fail('Choose a franchise account.');
        }
        $u->city_id = (int) $cityId;
        $u->vehicle_id = (int) $module;
        $u->save(false, ['city_id', 'vehicle_id']);
        self::log($module, $cityId, 'franchise-assign', 'Franchise ' . self::person($u) . ' now runs this map', $adminId);

        return self::ok(self::person($u) . ' now runs the ' . ServiceModules::title($module) . ' of ' . self::cityName($cityId) . ' and sees only that.');
    }
    // [doc:end] assignFranchise

    // [doc:start] removeFranchise - Remove Franchise
    public static function removeFranchise($module, $cityId, $userId, $adminId)
    {
        $u = User::findOne((int) $userId);
        if (!$u || $u->user_role !== User::ROLE_FRANCHISE || (int) $u->city_id !== (int) $cityId || (int) $u->vehicle_id !== (int) $module) {
            return self::fail('That franchise does not run this map.');
        }
        $u->city_id = null;
        $u->vehicle_id = null;
        $u->save(false, ['city_id', 'vehicle_id']);
        self::log($module, $cityId, 'franchise-remove', 'Franchise ' . self::person($u) . ' no longer runs this map', $adminId);

        return self::ok(self::person($u) . ' no longer runs this map.');
    }
    // [doc:end] removeFranchise

    // ------------------------------------------------------------------ what the page shows

    // [doc:start] snapshot - Snapshot
    public static function snapshot($module, $cityId)
    {
        $module = (int) $module;
        $cityId = (int) $cityId;
        $taxi = ServiceModules::isTaxi($module);

        $byCode = [];
        if ($taxi) {
            $vehicles = ServiceModules::vehicles($module);
            $rows = (new Query())->from('pincode')->where(['city_id' => $cityId, 'status' => [0, self::STATUS_ACTIVE], 'vehical_id' => ServiceModules::pincodeVehicleIds($module)])
                ->orderBy(['name' => SORT_ASC])->all();
            foreach ($rows as $r) {
                $byCode[$r['name']]['vehicles'][(int) $r['vehical_id']] = $vehicles[$r['vehical_id']] ?? ('#' . $r['vehical_id']);
            }
        } else {
            $vehicles = [];
            foreach (self::pincodeNames($module, $cityId) as $code) {
                $byCode[$code]['vehicles'] = [];
            }
        }

        $pricing = $taxi
            ? (new Query())->from('ride_commision')->where(['city_id' => $cityId, 'vehicle_id' => $module, 'status' => self::STATUS_ACTIVE])->all()
            : (new Query())->from('city_rate_card')->where(['city_id' => $cityId, 'status' => self::STATUS_ACTIVE])->orderBy(['trip_type' => SORT_ASC, 'ride_type' => SORT_ASC])->all();
        $pricingTable = $taxi ? 'ride_commision' : 'city_rate_card';

        $sources = (new Query())->select(['c.id', 'c.name'])->from(['c' => 'city'])
            ->innerJoin(['p' => $pricingTable], 'p.city_id = c.id AND p.status = 1' . ($taxi ? ' AND p.vehicle_id = ' . $module : ''))
            ->where(['<>', 'c.id', $cityId])->distinct()->orderBy('c.name')->all();

        return [
            'module' => $module,
            'taxi' => $taxi,
            'vehicles' => $vehicles,
            'pincodes' => $byCode,
            'pricing' => $pricing,
            'franchises' => User::find()->where(['user_role' => User::ROLE_FRANCHISE, 'city_id' => $cityId, 'vehicle_id' => $module])->all(),
            'availableFranchises' => User::find()->where(['user_role' => User::ROLE_FRANCHISE, 'status' => User::STATUS_ACTIVE])
                ->andWhere(['or', ['city_id' => null], ['<>', 'city_id', $cityId], ['<>', 'vehicle_id', $module], ['vehicle_id' => null]])->all(),
            'cities' => (new Query())->select(['id', 'name'])->from('city')->where(['status' => 1])->andWhere(['<>', 'id', $cityId])->orderBy('name')->all(),
            'pricingSources' => $sources,
            'log' => (new Query())->from('map_log')->where(['city_id' => $cityId, 'module' => $module])->orderBy(['id' => SORT_DESC])->limit(15)->all(),
        ];
    }
    // [doc:end] snapshot

    private static function cityName($id)
    {
        return (string) ((new Query())->select('name')->from('city')->where(['id' => (int) $id])->scalar() ?: ('#' . $id));
    }

    private static function person(User $u)
    {
        $n = trim($u->first_name . ' ' . $u->last_name);

        return ($n !== '' ? $n : $u->username) . ' (' . $u->contact_no . ')';
    }

    private static function log($module, $cityId, $action, $detail, $adminId)
    {
        try {
            Yii::$app->db->createCommand()->insert('map_log', [
                'city_id' => (int) $cityId, 'module' => (int) $module, 'action' => $action,
                'detail' => mb_substr($detail, 0, 500), 'admin_id' => $adminId, 'created_on' => date('Y-m-d H:i:s'),
            ])->execute();
        } catch (\Throwable $e) {
            Yii::error('map log failed: ' . $e->getMessage(), 'api');
        }
    }

    private static function ok($m)
    {
        return ['ok' => true, 'message' => $m];
    }

    private static function fail($m)
    {
        return ['ok' => false, 'message' => $m];
    }
}
