<?php

namespace app\components;

use Yii;

/**
 * Account deletion for the customer and driver apps (and the public "delete my account" page required by Google Play).
 *
 * Deleting an account signs the person out everywhere, blocks the login and erases their personal details (name, email, phone number,
 * photo, date of birth, emergency contacts, saved places). Ride and payment records stay, without the personal link, because the
 * accounts and tax rules require them. A person with a ride in progress cannot delete until the ride is finished.
 */
class AccountDeletion
{
    const STATUS_PENDING = 0;
    const STATUS_DONE = 1;

    const OPEN_RIDE_STATUSES = '2,4,5,6';

    /** the reason the account cannot be deleted right now, or '' when it can */
    public static function blocker($userId, $role)
    {
        $col = $role === 'Driver' ? 'driver_id' : 'user_id';
        $open = (int) Yii::$app->db->createCommand(
            "SELECT COUNT(*) FROM booking WHERE $col = :u AND status IN (" . self::OPEN_RIDE_STATUSES . ')',
            [':u' => $userId]
        )->queryScalar();

        return $open > 0 ? 'A ride is in progress. Finish it first, then delete the account.' : '';
    }

    /** erases the personal data of the user and logs the request; returns [ok, message] */
    public static function deleteUser($userId, $role, $source = 'app', $reason = '')
    {
        $db = Yii::$app->db;
        $user = $db->createCommand('SELECT id, first_name, last_name, contact_no, user_role FROM user WHERE id = :u', [':u' => $userId])->queryOne();
        if (!$user || $user['user_role'] !== $role) {
            return [false, 'Account not found.'];
        }
        $why = self::blocker($userId, $role);
        if ($why !== '') {
            return [false, $why];
        }
        $now = date('Y-m-d H:i:s');
        $tx = $db->beginTransaction();
        try {
            $db->createCommand()->insert('account_deletion_request', [
                'user_id' => $userId, 'role' => $role, 'contact_no' => (string) $user['contact_no'],
                'name' => trim($user['first_name'] . ' ' . $user['last_name']), 'reason' => mb_substr((string) $reason, 0, 500),
                'source' => $source, 'status' => self::STATUS_DONE, 'requested_on' => $now, 'completed_on' => $now,
            ])->execute();
            // the login is blocked and the personal details are blanked; the row stays so old rides still point at something
            $set = ['status' => 2, 'first_name' => 'Deleted', 'last_name' => 'User', 'email' => 'deleted-' . $userId . '@deleted.invalid',
                'username' => 'deleted-' . $userId, 'contact_no' => null];
            foreach (['date_of_birth', 'gender', 'profile_file', 'device_token', 'fcm_token'] as $c) {
                if ($db->getTableSchema('user')->getColumn($c)) {
                    $set[$c] = null;
                }
            }
            foreach (array_keys($set) as $c) {
                if (!$db->getTableSchema('user')->getColumn($c)) {
                    unset($set[$c]);
                }
            }
            $db->createCommand()->update('user', $set, ['id' => $userId])->execute();
            foreach (['auth_session' => 'create_user_id', 'favourite_location' => 'user_id', 'user_emergency_contacts' => 'user_id', 'driver_emergency_contacts' => 'driver_id'] as $t => $c) {
                if ($db->getTableSchema($t) && $db->getTableSchema($t)->getColumn($c)) {
                    $db->createCommand()->delete($t, [$c => $userId])->execute();
                }
            }
            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            Yii::error($e->getMessage(), 'account-deletion');

            return [false, 'The account could not be deleted. Please try again or contact support.'];
        }

        return [true, 'Your account and personal data have been deleted.'];
    }

    /**
     * Request from the public page: the phone number is checked against an account; the request is recorded. Because nobody is signed in
     * here, the account is not deleted at once - support confirms it with the person (call back on the number) and processes it within 30 days.
     */
    public static function logWebRequest($contact, $name, $role, $reason)
    {
        $user = Yii::$app->db->createCommand('SELECT id FROM user WHERE contact_no = :c AND user_role = :r', [':c' => $contact, ':r' => $role])->queryScalar();
        Yii::$app->db->createCommand()->insert('account_deletion_request', [
            'user_id' => $user ?: null, 'role' => $role, 'contact_no' => $contact, 'name' => mb_substr($name, 0, 120),
            'reason' => mb_substr($reason, 0, 500), 'source' => 'web', 'status' => self::STATUS_PENDING, 'requested_on' => date('Y-m-d H:i:s'),
        ])->execute();
    }
}
