<?php

namespace app\modules\payments\services;

use app\models\User;
use app\modules\admin\models\WebSetting;
use yii\db\Query;

/**
 * What a new partner (driver) must finish before getting ride offers, in the order the app shows it:
 * profile, profile photo, driving licence, Aadhaar, PAN, emergency contacts, bank details, vehicle types, police verification,
 * the joining fee (when the dashboard setting driver_joining_fee is above 0) and the approval by the company.
 *
 * Nothing is blocked until an administrator turns on the dashboard setting "Enforce driver onboarding". Then a driver can go
 * online, and gets ride offers, only when every step is done. Drivers who joined before this existed stay as they are until the
 * setting is switched on, so switch it on only after the existing drivers have been approved.
 */
class DriverOnboarding
{
    // [doc:start] enforced - Enforced
    public static function enforced()
    {
        return (string) (new WebSetting())->getSettingBykey('enforce_driver_onboarding') === '1';
    }
    // [doc:end] enforced

    private static function has($table, $driverId)
    {
        return (new Query())->from($table)->where(['driver_id' => (int) $driverId])->exists();
    }

    // [doc:start] steps - Steps
    /**
     * @return array list of ['key' => string, 'label' => string, 'done' => bool]
     */
    public static function steps(User $u)
    {
        $id = (int) $u->id;
        $fee = PaymentOrders::joiningFee();
        // a step is done only when every mandatory part of it is filled in (see DriverProfile::missing)
        $left = \app\modules\admin\models\DriverProfile::missing($id);
        $personal = array_values(array_diff($left['personal'] ?? [], ['profile photo']));
        $steps = [
            ['profile', 'Profile', !$personal, $personal],
            ['profile_photo', 'Profile photo', trim((string) $u->profile_image) !== '', ['profile photo']],
            ['driving_license', 'Driving licence', !($left['license'] ?? []), $left['license'] ?? []],
            ['aadhar', 'Aadhaar card', !($left['aadhar'] ?? []), $left['aadhar'] ?? []],
            ['pan', 'PAN card', !($left['pan'] ?? []), $left['pan'] ?? []],
            ['emergency_contacts', 'Emergency contacts', !($left['contacts'] ?? []), $left['contacts'] ?? []],
            ['bank', 'Bank details', !($left['bank'] ?? []), $left['bank'] ?? []],
            ['vehicle_types', 'Vehicle types', !($left['vehicles'] ?? []), $left['vehicles'] ?? []],
            ['police_verification', 'Police verification', (int) $u->is_done_police_verification === 1, []],
        ];
        if ($fee > 0) {
            $steps[] = ['joining_fee', 'Joining fee (₹' . rtrim(rtrim(number_format($fee, 2, '.', ''), '0'), '.') . ')', (int) $u->is_paid_joining_fee === 1, []];
        }
        $steps[] = ['approval', 'Approval by the company', (int) $u->profile_verified === 1, []];

        return array_map(function ($s) {
            return ['key' => $s[0], 'label' => $s[1], 'done' => (bool) $s[2], 'missing' => $s[3]];
        }, $steps);
    }
    // [doc:end] steps

    // [doc:start] complete - Complete
    public static function complete(User $u)
    {
        foreach (self::steps($u) as $s) {
            if (!$s['done']) {
                return false;
            }
        }

        return true;
    }
    // [doc:end] complete

    // [doc:start] mayWork - May Work
    public static function mayWork(User $u)
    {
        return !self::enforced() || self::complete($u);
    }
    // [doc:end] mayWork

    // [doc:start] dispatchFilter - Dispatch Filter
    /**
     * @return array|null a Yii where-condition, or null while enforcement is off
     */
    public static function dispatchFilter()
    {
        if (!self::enforced()) {
            return null;
        }
        $filter = ['profile_verified' => 1];
        if (PaymentOrders::joiningFee() > 0) {
            $filter['is_paid_joining_fee'] = 1;
        }

        return $filter;
    }
    // [doc:end] dispatchFilter
}
