<?php

namespace app\commands;

use app\modules\geo\services\ServiceModules;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Query;

/**
 * Planning the move of a service to its own database (see ARCHITECTURE_TARGET.md).
 *
 *   php yii split/plan --service=1        what the driver service owns, how many rows, and what is still shared
 *   php yii split/copy --service=1 --target=NAME [--apply]
 *                                         copy the service into a NEW database NAME and reconcile counts and money
 *
 * plan is read-only: it copies and changes nothing. copy only ever WRITES TO THE TARGET database (it refuses a target that
 * already holds a table of the service) and only reads the source; without --apply it just lists what it would do. It answers "is this service ready to be cut out?" - every table it
 * owns is either tagged with the service (`module` column) or owned by it outright, and nothing else points at it.
 */
class SplitController extends Controller
{
    public $service = 1;
    public $target = '';
    public $apply = false;

    /** Tables each service owns outright (the whole table moves with it). */
    private static $owned = [
        1 => ['booking', 'booking_logs', 'accepted_bookings', 'driver_requests', 'driver_cancel_duplicate', 'cancel_ride_charges',
            'driver_referral_earnings', 'driver_subscription', 'driver_subscription_logs', 'plans', 'driver_booking_ratings',
            'user_booking_ratings', 'city_rate_card', 'vehicle_types', 'vehicle_verification', 'driver_vehicle_types',
            'driver_aadhar_card', 'driver_pan_cards', 'driving_license', 'driver_bank_details', 'driver_emergency_contacts'],
        2 => ['ride_request', 'taxi_driver_request', 'ride_statuses', 'ride_completion_log', 'reject_log', 'taxi_rating', 'ride_commision', 'pincode', 'vehicals'],
        3 => ['ride_request', 'taxi_driver_request', 'ride_statuses', 'ride_completion_log', 'reject_log', 'taxi_rating', 'ride_commision', 'pincode', 'vehicals'],
        4 => ['ride_request', 'taxi_driver_request', 'ride_statuses', 'ride_completion_log', 'reject_log', 'taxi_rating', 'ride_commision', 'pincode', 'vehicals'],
    ];

    /** Tables shared by every service today that carry a `module` column, so they split by that column. */
    private static $byModule = [
        'driver_wallet', 'user_wallet', 'coupon', 'coupons_applied', 'user_referral_earnings', 'driver_referral_earnings',
        'driver_earnings', 'ride_earnings', 'taxi_payout', 'service_map', 'service_map_pincode', 'service_invoice',
        'service_invoice_counter', 'service_billing_profile', 'map_log',
    ];

    // [doc:start] options - Options
    public function options($actionID)
    {
        return array_merge(parent::options($actionID), ['service', 'target', 'apply']);
    }
    // [doc:end] options

    // [doc:start] actionPlan - Plan
    public function actionPlan()
    {
        $module = (int) $this->service;
        if (!ServiceModules::exists($module)) {
            $this->stderr("Give a service: --service=1 (driver), 2 (taxi), 3 (auto), 4 (bike)\n");

            return ExitCode::USAGE;
        }
        $db = Yii::$app->db;
        $this->stdout('Service: ' . ServiceModules::title($module) . " (module $module)\n\n");

        $this->stdout("Tables that move with it (whole table):\n");
        $total = 0;
        foreach (self::$owned[$module] as $t) {
            if (!$db->getTableSchema($t)) {
                $this->stdout(sprintf("  %-28s (not in this database)\n", $t));
                continue;
            }
            $n = (int) (new Query())->from($t)->count();
            $total += $n;
            $shared = ($module !== 1 && in_array($t, ['ride_request', 'taxi_driver_request', 'ride_statuses', 'ride_completion_log', 'reject_log', 'taxi_rating', 'ride_commision', 'pincode', 'vehicals'], true))
                ? '  (shared with the other taxi services: split by vehicle type)' : '';
            $this->stdout(sprintf("  %-28s %8d rows%s\n", $t, $n, $shared));
        }

        $this->stdout("\nTables split by the `module` column:\n");
        foreach (self::$byModule as $t) {
            $schema = $db->getTableSchema($t);
            if (!$schema) {
                continue;
            }
            if (!isset($schema->columns['module'])) {
                $this->stdout(sprintf("  %-28s NO module column\n", $t));
                continue;
            }
            $mine = (int) (new Query())->from($t)->where(['module' => $module])->count();
            $untagged = (int) (new Query())->from($t)->where(['module' => null])->count();
            $total += $mine;
            $this->stdout(sprintf("  %-28s %8d rows of this service%s\n", $t, $mine, $untagged ? "  ($untagged untagged - decide where they go)" : ''));
        }

        $this->stdout("\nStays outside (shared identity and platform): user, auth, auth_session, web_setting, city, states, notification,\n");
        $this->stdout("fcm_notification, media, page, banner*, services. The service refers to people by user id only.\n");
        $this->stdout("\nRows that would move: $total\n");
        $this->stdout("Nothing was changed.\n");

        return ExitCode::OK;
    }
    // [doc:end] actionPlan

    /** Partner accounts: the user rows of the service's own partners move with it (a replica, the user app keeps customers). */
    private static $partnerTables = ['driver_details'];

    // [doc:start] actionCopy - Copy
    public function actionCopy()
    {
        $module = (int) $this->service;
        if (!ServiceModules::exists($module) || !preg_match('/^[A-Za-z0-9_]{3,64}$/', (string) $this->target)) {
            $this->stderr("Usage: split/copy --service=1 --target=new_database_name [--apply]\n");

            return ExitCode::USAGE;
        }
        $db = Yii::$app->db;
        $source = (string) $db->createCommand('select database()')->queryScalar();
        $target = (string) $this->target;
        if ($target === $source) {
            $this->stderr("The target must be a different database than the source ($source).\n");

            return ExitCode::USAGE;
        }

        // [table, where-sql or null]
        $jobs = [];
        $taxi = ServiceModules::isTaxi($module);
        if ($taxi) {
            // taxi, auto and bike share the ride tables today: a service takes the rows of its own vehicle types
            $veh = '(SELECT `id` FROM `vehicals` WHERE `vehicle_type` = ' . $module . ')';
            $rides = '(SELECT `id` FROM `ride_request` WHERE `vehical_type` IN ' . $veh . ')';
            $jobs = [
                ['vehicals', '`vehicle_type` = ' . $module],
                ['ride_request', '`vehical_type` IN ' . $veh],
                ['taxi_driver_request', '`ride_id` IN ' . $rides],
                ['ride_statuses', '`ride_id` IN ' . $rides],
                ['ride_completion_log', '`ride_id` IN ' . $rides],
                ['reject_log', '`ride_id` IN ' . $rides],
                ['taxi_rating', '`ride_id` IN ' . $rides],
                ['ride_commision', '`vehicle_id` = ' . $module],
                ['pincode', '`vehical_id` = ' . $module],
                // a partner belongs to the service of the vehicle type they signed up with (legacy ids 2/3 are the module)
                ['driver_details', 'IFNULL((SELECT v.`vehicle_type` FROM `vehicals` v WHERE v.`id` = `driver_details`.`vehical_id`), `driver_details`.`vehical_id`) = ' . $module],
            ];
        } else {
            foreach (self::$owned[$module] as $t) {
                $jobs[] = [$t, null];
            }
            foreach (self::$partnerTables as $t) {
                if ($db->getTableSchema($t)) {
                    $jobs[] = [$t, null];
                }
            }
        }
        $owned = array_map(function ($j) {
            return $j[0];
        }, $jobs);
        foreach (self::$byModule as $t) {
            $schema = $db->getTableSchema($t);
            if ($schema && isset($schema->columns['module']) && $t !== 'user_wallet' && !in_array($t, $owned, true)) {
                $jobs[] = [$t, '`module` = ' . $module];
            }
        }
        $jobs = array_values(array_filter($jobs, function ($j) use ($db) {
            return $db->getTableSchema($j[0]) !== null;
        }));
        // partner accounts: the users of the service's partner role (customers stay with the user app)
        if ($db->getTableSchema('user')) {
            $jobs[] = ['user', $taxi
                ? "`user_role` = 'taxi' AND `id` IN (SELECT dd.`user_id` FROM `driver_details` dd WHERE IFNULL((SELECT v.`vehicle_type` FROM `vehicals` v WHERE v.`id` = dd.`vehical_id`), dd.`vehical_id`) = $module)"
                : "`user_role` = 'Driver'"];
        }

        $exists = $db->createCommand('SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name = :n', [':n' => $target])->queryScalar();
        if ($exists) {
            $have = [];
            foreach ($jobs as $j) {
                if ($db->createCommand('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :s AND table_name = :t', [':s' => $target, ':t' => $j[0]])->queryScalar()) {
                    $have[] = $j[0];
                }
            }
            if ($have) {
                $this->stderr("Target $target already has: " . implode(', ', $have) . ". Refusing to touch it; give a new name.\n");

                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        $this->stdout(($this->apply ? 'COPY' : 'DRY RUN') . ': ' . ServiceModules::title($module) . " from `$source` into `$target`\n");
        $untagged = [];
        foreach (self::$byModule as $t) {
            $schema = $db->getTableSchema($t);
            if ($schema && isset($schema->columns['module'])) {
                $n = (int) (new Query())->from($t)->where(['module' => null])->count();
                if ($n) {
                    $untagged[$t] = $n;
                }
            }
        }
        if (!$this->apply) {
            foreach ($jobs as $j) {
                $n = (int) $db->createCommand('SELECT COUNT(*) FROM `' . $j[0] . '`' . ($j[1] ? ' WHERE ' . $j[1] : ''))->queryScalar();
                $this->stdout(sprintf("  %-28s %8d rows%s\n", $j[0], $n, $j[1] ? '  (' . $j[1] . ')' : ''));
            }
            $this->stdout("Not copied (stay in `$source`; decide where they go): user_wallet (customer wallet is off), untagged rows: " . ($untagged ? json_encode($untagged) : 'none') . "\n");
            $this->stdout("Add --apply to copy. Nothing was changed.\n");

            return ExitCode::OK;
        }

        $db->createCommand("CREATE DATABASE IF NOT EXISTS `$target` CHARACTER SET utf8mb4")->execute();
        $db->createCommand('SET FOREIGN_KEY_CHECKS=0')->execute();
        $bad = 0;
        foreach ($jobs as $j) {
            list($t, $where) = $j;
            $db->createCommand("CREATE TABLE `$target`.`$t` LIKE `$source`.`$t`")->execute();
            $db->createCommand("INSERT INTO `$target`.`$t` SELECT * FROM `$source`.`$t`" . ($where ? " WHERE $where" : ''))->execute();
            // reconcile: row count and the sum of every money-like column
            $cols = [];
            foreach ($db->getTableSchema($t)->columns as $c) {
                if (in_array($c->type, ['decimal', 'float', 'double'], true)) {
                    $cols[] = $c->name;
                }
            }
            $sel = 'COUNT(*) AS n' . implode('', array_map(function ($c) {
                return ", ROUND(SUM(`$c`), 2) AS `s_$c`";
            }, $cols));
            $a = $db->createCommand("SELECT $sel FROM `$source`.`$t`" . ($where ? " WHERE $where" : ''))->queryOne();
            $b = $db->createCommand("SELECT $sel FROM `$target`.`$t`")->queryOne();
            $ok = $a == $b;
            $bad += $ok ? 0 : 1;
            $this->stdout(sprintf("  %-28s %8d rows  %s%s\n", $t, $b['n'], $ok ? 'OK' : 'MISMATCH', $cols ? '  (' . count($cols) . ' money column(s) summed)' : ''));
        }
        $db->createCommand('SET FOREIGN_KEY_CHECKS=1')->execute();
        $this->stdout($bad ? "\n$bad table(s) do not reconcile - do not cut over.\n" : "\nAll tables reconcile (row counts and money sums equal).\n");
        $this->stdout('Not copied: user_wallet; untagged rows: ' . ($untagged ? json_encode($untagged) : 'none') . ". The source `$source` is unchanged.\n");

        return $bad ? ExitCode::UNSPECIFIED_ERROR : ExitCode::OK;
    }
    // [doc:end] actionCopy

    /** Platform tables every service needs a replica of (settings, places, vehicle catalogue, schema log). */
    private static $reference = ['city', 'states', 'web_setting', 'services', 'vehicals', 'pincode', 'pincode_area', 'ride_commision',
        'banner', 'banner_categorys', 'page', 'db_migration_log', 'migration'];

    /** Platform tables that hold per-person rows: only the partners of the service move (column = the person). */
    private static $partnerScoped = ['auth' => 'user_id', 'auth_session' => 'create_user_id', 'notification' => 'user_id',
        'fcm_notification' => 'user_id', 'media' => 'owner_user_id'];

    // [doc:start] actionComplete - Makes the new service database complete after split/copy: every other table of the schema is created (empty), the.
    /**
     * Makes the new service database complete after split/copy: every other table of the schema is created (empty), the
     * reference tables are replicated and the partner-scoped platform tables get the rows of the service's partners.
     * Customers are NOT copied: the service asks the user app for them (CustomerClient).
     */
    public function actionComplete()
    {
        if (!preg_match('/^[A-Za-z0-9_]{3,64}$/', (string) $this->target)) {
            $this->stderr("Usage: split/complete --target=existing_service_database\n");

            return ExitCode::USAGE;
        }
        $db = Yii::$app->db;
        $source = (string) $db->createCommand('select database()')->queryScalar();
        $target = (string) $this->target;
        if ($target === $source || !$db->createCommand('SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name = :n', [':n' => $target])->queryScalar()) {
            $this->stderr("Target `$target` must be an existing database different from `$source`.\n");

            return ExitCode::USAGE;
        }
        $db->createCommand('SET FOREIGN_KEY_CHECKS=0')->execute();
        $tables = $db->createCommand("SELECT table_name FROM information_schema.tables WHERE table_schema = :s AND table_type = 'BASE TABLE'", [':s' => $source])->queryColumn();
        $made = 0;
        foreach ($tables as $t) {
            $has = $db->createCommand('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :s AND table_name = :t', [':s' => $target, ':t' => $t])->queryScalar();
            if (!$has) {
                $db->createCommand("CREATE TABLE `$target`.`$t` LIKE `$source`.`$t`")->execute();
                $made++;
                if (in_array($t, self::$reference, true) && !((int) $this->service === 0 && in_array($t, ['vehicals', 'pincode', 'pincode_area', 'ride_commision'], true))) {
                    $db->createCommand("INSERT INTO `$target`.`$t` SELECT * FROM `$source`.`$t`")->execute();
                } elseif (isset(self::$partnerScoped[$t])) {
                    $col = self::$partnerScoped[$t];
                    $db->createCommand("INSERT INTO `$target`.`$t` SELECT * FROM `$source`.`$t` WHERE `$col` IN (SELECT `id` FROM `$target`.`user`)")->execute();
                }
                $n = (int) $db->createCommand("SELECT COUNT(*) FROM `$target`.`$t`")->queryScalar();
                $this->stdout(sprintf("  %-28s %8d rows  %s\n", $t, $n, $n ? '' : '(structure only)'));
            }
        }
        // staff of the service's own dashboard: the owner accounts (no service) and staff bound to this service
        $staff = $db->createCommand("INSERT IGNORE INTO `$target`.`user` SELECT * FROM `$source`.`user` WHERE `user_role` IN ('admin', 'Subadmin', 'Manager', 'city franchise')"
            . ' AND (`vehicle_id` IS NULL OR `vehicle_id` = ' . (int) $this->service . ')')->execute();
        $this->stdout("  staff accounts for the service dashboard: $staff
");
        // new accounts made in this service never reuse an id of the user app or another service: its own id range
        // (the UUID stays the identity everywhere)
        $start = (int) $this->service > 0 ? (int) $this->service * 100000000 : 0;
        if ($start > 0) {
            $db->createCommand("ALTER TABLE `$target`.`user` AUTO_INCREMENT = $start")->execute();
        }
        $db->createCommand("DROP TRIGGER IF EXISTS `$target`.`user_uuid_bi`")->execute();
        $db->createCommand("CREATE TRIGGER `$target`.`user_uuid_bi` BEFORE INSERT ON `$target`.`user` FOR EACH ROW SET NEW.`uuid` = IFNULL(NEW.`uuid`, UUID())")->execute();
        $this->stdout("  new accounts of the service get ids from $start (the uuid is the shared identity)
");
        $db->createCommand('SET FOREIGN_KEY_CHECKS=1')->execute();
        $this->stdout("$made table(s) added to `$target`. Customers are not copied; the service asks the user app (gateway).\n");

        return ExitCode::OK;
    }
    // [doc:end] actionComplete

    // [doc:start] actionCustomer - The customer (user) app on its own database: the customers and everything that is theirs. Run split/complete.
    /**
     * The customer (user) app on its own database: the customers and everything that is theirs. Run split/complete
     * --service=0 afterwards for the rest of the schema, the reference tables and the staff accounts of its dashboard.
     */
    public function actionCustomer()
    {
        if (!preg_match('/^[A-Za-z0-9_]{3,64}$/', (string) $this->target)) {
            $this->stderr("Usage: split/customer --target=new_database_name [--apply]\n");

            return ExitCode::USAGE;
        }
        $db = Yii::$app->db;
        $source = (string) $db->createCommand('select database()')->queryScalar();
        $target = (string) $this->target;
        if ($target === $source) {
            $this->stderr("The target must be a different database than the source ($source).\n");

            return ExitCode::USAGE;
        }
        $users = "`user_role` = 'User'";
        $mine = "SELECT `id` FROM `user` WHERE $users";
        $jobs = [
            ['user', $users],
            ['auth', "`user_id` IN ($mine)"],
            ['auth_session', "`create_user_id` IN ($mine)"],
            ['notification', "`user_id` IN ($mine)"],
            ['fcm_notification', "`user_id` IN ($mine)"],
            ['favourite_location', "`user_id` IN ($mine)"],
            ['user_emergency_contacts', "`user_id` IN ($mine)"],
            ['user_wallet', "`user_id` IN ($mine)"],
            ['media', "`owner_user_id` IN ($mine)"],
        ];
        $jobs = array_values(array_filter($jobs, function ($j) use ($db) {
            return $db->getTableSchema($j[0]) !== null;
        }));
        $exists = (int) $db->createCommand('SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name = :n', [':n' => $target])->queryScalar();
        if ($exists) {
            foreach ($jobs as $j) {
                if ($db->createCommand('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :s AND table_name = :t', [':s' => $target, ':t' => $j[0]])->queryScalar()) {
                    $this->stderr("Target $target already has {$j[0]}. Refusing to touch it; give a new name.\n");

                    return ExitCode::UNSPECIFIED_ERROR;
                }
            }
        }
        $this->stdout(($this->apply ? 'COPY' : 'DRY RUN') . ": the customer app from `$source` into `$target`\n");
        if (!$this->apply) {
            foreach ($jobs as $j) {
                $n = (int) $db->createCommand('SELECT COUNT(*) FROM `' . $j[0] . '` WHERE ' . $j[1])->queryScalar();
                $this->stdout(sprintf("  %-28s %8d rows\n", $j[0], $n));
            }
            $this->stdout("Add --apply to copy. Nothing was changed.\n");

            return ExitCode::OK;
        }
        $db->createCommand("CREATE DATABASE IF NOT EXISTS `$target` CHARACTER SET utf8mb4")->execute();
        $db->createCommand('SET FOREIGN_KEY_CHECKS=0')->execute();
        $bad = 0;
        foreach ($jobs as $j) {
            list($t, $where) = $j;
            $db->createCommand("CREATE TABLE `$target`.`$t` LIKE `$source`.`$t`")->execute();
            $db->createCommand("INSERT INTO `$target`.`$t` SELECT * FROM `$source`.`$t` WHERE $where")->execute();
            $a = (int) $db->createCommand("SELECT COUNT(*) FROM `$source`.`$t` WHERE $where")->queryScalar();
            $b = (int) $db->createCommand("SELECT COUNT(*) FROM `$target`.`$t`")->queryScalar();
            $bad += $a === $b ? 0 : 1;
            $this->stdout(sprintf("  %-28s %8d rows  %s\n", $t, $b, $a === $b ? 'OK' : 'MISMATCH'));
        }
        $db->createCommand('SET FOREIGN_KEY_CHECKS=1')->execute();
        $this->stdout($bad ? "\n$bad table(s) do not reconcile.\n" : "\nAll tables reconcile. Run split/complete --target=$target --service=0 next. The source is unchanged.\n");

        return $bad ? ExitCode::UNSPECIFIED_ERROR : ExitCode::OK;
    }
    // [doc:end] actionCustomer
}
