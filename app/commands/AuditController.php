<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Query;

/**
 * Integrity report for user accounts and the data that belongs to them.
 *
 *   php yii audit/users               read-only report
 *   php yii audit/users --fix-safe    also applies the fixes that cannot change anybody's access
 *
 * --fix-safe only: sets the role of accounts that have none but a role-bearing username suffix
 * (@driver -> Driver, @taxi -> taxi), and turns the text "null" / empty e-mail into a real NULL.
 * It never deletes rows and never changes status, passwords or roles that are already set.
 */
class AuditController extends Controller
{
    /** @var bool */
    public $fixSafe = false;

    // [doc:start] options - Options
    public function options($actionID)
    {
        return array_merge(parent::options($actionID), ['fixSafe']);
    }
    // [doc:end] options

    // [doc:start] optionAliases - Option Aliases
    public function optionAliases()
    {
        return array_merge(parent::optionAliases(), ['fix-safe' => 'fixSafe']);
    }
    // [doc:end] optionAliases

    /** rows that point at users which no longer exist: label => [table, column] */
    private static $references = [
        'ride_request.user_id' => ['ride_request', 'user_id'],
        'ride_request.driver_id' => ['ride_request', 'driver_id'],
        'booking.user_id' => ['booking', 'user_id'],
        'booking.driver_id' => ['booking', 'driver_id'],
        'taxi_rating.user_id' => ['taxi_rating', 'user_id'],
        'taxi_rating.taxi_driver_id' => ['taxi_rating', 'taxi_driver_id'],
        'user_booking_ratings.user_id' => ['user_booking_ratings', 'user_id'],
        'driver_booking_ratings.user_id' => ['driver_booking_ratings', 'user_id'],
        'user_wallet.user_id' => ['user_wallet', 'user_id'],
        'driver_wallet.driver_id' => ['driver_wallet', 'driver_id'],
        'ride_earnings.driver_id' => ['ride_earnings', 'driver_id'],
        'taxi_payout.driver_id' => ['taxi_payout', 'driver_id'],
        'reject_log.taxi_driver_id' => ['reject_log', 'taxi_driver_id'],
        'driver_details.user_id' => ['driver_details', 'user_id'],
        'auth.user_id' => ['auth', 'user_id'],
        'auth_session.create_user_id' => ['auth_session', 'create_user_id'],
        'fcm_notification.user_id' => ['fcm_notification', 'user_id'],
    ];

    // [doc:start] actionUsers - Users
    public function actionUsers()
    {
        $db = Yii::$app->db;
        $problems = 0;

        $this->section('Accounts by role and status');
        $rows = $db->createCommand("SELECT COALESCE(NULLIF(user_role,''),'(no role)') r, status, COUNT(*) n FROM user GROUP BY 1,2 ORDER BY 1,2")->queryAll();
        foreach ($rows as $r) {
            $this->line(sprintf('%-18s status %-3s %6d', $r['r'], $r['status'], $r['n']));
        }

        $this->section('Accounts');
        $problems += $this->check(
            'accounts without a role',
            "SELECT id, username FROM user WHERE user_role IS NULL OR user_role = ''"
        );
        $problems += $this->check(
            'same phone number twice for the same role',
            "SELECT contact_no, user_role, GROUP_CONCAT(id) ids FROM user WHERE contact_no IS NOT NULL AND contact_no <> 0 GROUP BY contact_no, user_role HAVING COUNT(*) > 1"
        );
        $problems += $this->check(
            'same username twice',
            "SELECT username, GROUP_CONCAT(id) ids FROM user GROUP BY username HAVING COUNT(*) > 1"
        );
        $problems += $this->check(
            'same e-mail on several accounts (real addresses only)',
            "SELECT email, GROUP_CONCAT(id) ids FROM user WHERE email IS NOT NULL AND email NOT IN ('', 'null') GROUP BY email HAVING COUNT(*) > 1"
        );
        $problems += $this->check(
            'e-mail stored as the text "null" or empty',
            "SELECT id, email FROM user WHERE email = 'null' OR email = ''"
        );
        $problems += $this->check(
            'role is not one the system knows',
            "SELECT id, user_role FROM user WHERE user_role IS NOT NULL AND user_role <> '' AND user_role NOT IN ('" . implode("','", \app\models\User::roles()) . "')"
        );
        $problems += $this->check(
            'administration accounts without a password (cannot sign in)',
            "SELECT id, username, user_role FROM user WHERE user_role IN ('admin','Subadmin','Manager','city franchise') AND (password_hash IS NULL OR password_hash = '')"
        );
        $problems += $this->check(
            'taxi drivers without driver details (never offered a ride)',
            "SELECT u.id, u.username FROM user u LEFT JOIN driver_details d ON d.user_id = u.id WHERE u.user_role = 'taxi' AND d.id IS NULL"
        );
        $problems += $this->check(
            'online but not active',
            "SELECT id, username FROM user WHERE online_status = 1 AND status <> 10"
        );
        $problems += $this->check(
            'position out of range',
            "SELECT id, lat, lng FROM user WHERE lat <> '' AND lng <> '' AND (ABS(CAST(lat AS DECIMAL(10,6))) > 90 OR ABS(CAST(lng AS DECIMAL(10,6))) > 180)"
        );

        $this->section('Rows that point at users that no longer exist');
        foreach (self::$references as $label => [$table, $column]) {
            if ($db->schema->getTableSchema($table, true) === null) {
                continue;
            }
            $sql = "SELECT COUNT(*) FROM `$table` t LEFT JOIN user u ON u.id = t.`$column` "
                . "WHERE t.`$column` IS NOT NULL AND t.`$column` <> 0 AND u.id IS NULL";
            $n = (int) $db->createCommand($sql)->queryScalar();
            $this->line(sprintf('%-34s %s', $label, $n ? "$n orphaned" : 'ok'));
            $problems += $n ? 1 : 0;
        }

        if ($this->fixSafe) {
            $this->section('Applying safe fixes');
            $a = $db->createCommand("UPDATE user SET user_role = 'Driver' WHERE (user_role IS NULL OR user_role = '') AND username LIKE '%@driver'")->execute();
            $b = $db->createCommand("UPDATE user SET user_role = 'taxi' WHERE (user_role IS NULL OR user_role = '') AND username LIKE '%@taxi'")->execute();
            $c = $db->createCommand("UPDATE user SET email = NULL WHERE email = 'null' OR email = ''")->execute();
            $this->line("roles set: " . ($a + $b) . "   e-mails normalised: $c");
        } else {
            $this->line("\nRun with --fix-safe to apply the role and e-mail clean-up.");
        }

        $this->line("\nFindings that need a decision: $problems");

        return ExitCode::OK;
    }
    // [doc:end] actionUsers

    private function section($title)
    {
        $this->stdout("\n== $title\n");
    }

    private function line($text)
    {
        $this->stdout($text . "\n");
    }

    /**
     * Runs a query that selects offending rows; prints a count and a few examples.
     *
     * @return int 1 when something was found
     */
    private function check($label, $sql)
    {
        $rows = Yii::$app->db->createCommand($sql)->queryAll();
        if (!$rows) {
            $this->line(sprintf('%-62s ok', $label));
            return 0;
        }
        $this->line(sprintf('%-62s %d found', $label, count($rows)));
        foreach (array_slice($rows, 0, 3) as $row) {
            $this->line('      ' . json_encode($row));
        }

        return 1;
    }
}