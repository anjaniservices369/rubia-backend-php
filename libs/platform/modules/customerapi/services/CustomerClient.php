<?php

namespace app\modules\customerapi\services;

use app\models\User;
use Yii;
use yii\db\Query;

/**
 * What a service asks the user-app system, every time it needs it.
 *
 *   profile($id)                  name and phone of a customer (for an invoice, a dashboard row, a driver's screen)
 *   notify($id, $title, $body)    a push and an in-app message to the customer, through the user app's notifications
 *   authenticate($authCode)       who a customer login code belongs to
 *
 * Two ways to answer, chosen by configuration (params['customerApi']['mode'] or env CUSTOMER_API_MODE):
 *   local   the customer records are in this database (today's single app); answered from it directly
 *   remote  the user app is its own system; answered by HTTPS calls to its gateway (CUSTOMER_API_URL), signed with
 *           CUSTOMER_API_SECRET_<SERVICE> (see ServiceSignature). The service never keeps a copy.
 * Callers do not care which: the same three methods, the same arrays.
 */
class CustomerClient
{
    // [doc:start] isRemote - Is Remote
    public static function isRemote()
    {
        return self::mode() === 'remote';
    }
    // [doc:end] isRemote

    private static function mode()
    {
        $m = getenv('CUSTOMER_API_MODE');
        if ($m === false || $m === '') {
            $m = Yii::$app->params['customerApi']['mode'] ?? 'local';
        }

        return $m === 'remote' ? 'remote' : 'local';
    }

    /** The service key this installation calls the gateway as (driver, taxi, auto, bike). */
    private static function serviceKey()
    {
        $k = getenv('CUSTOMER_API_SERVICE');
        if ($k === false || $k === '') {
            $k = Yii::$app->params['customerApi']['service'] ?? 'haltingdriver';
        }

        return strtolower((string) $k);
    }

    // [doc:start] profile - Profile
    /**
     * @return array|null [id, uuid, name, phone, email, dob, gender, ...] or null when there is no such customer
     */
    public static function profile($userId)
    {
        $userId = (int) $userId;
        if ($userId <= 0) {
            return null;
        }
        if (!self::isRemote()) {
            $r = (new Query())->select(['id', 'uuid', 'username', 'first_name', 'last_name', 'contact_no', 'email', 'date_of_birth', 'gender', 'profile_image'])->from('user')->where(['id' => $userId])->one();

            return $r ? ['id' => (int) $r['id'], 'uuid' => (string) $r['uuid'], 'name' => trim($r['first_name'] . ' ' . $r['last_name']), 'phone' => (string) $r['contact_no'],
                'username' => (string) $r['username'], 'first_name' => (string) $r['first_name'], 'last_name' => (string) $r['last_name'],
                'image' => (string) $r['profile_image'],
                'email' => (string) $r['email'], 'dob' => (string) $r['date_of_birth'], 'gender' => (string) $r['gender']] : null;
        }
        $res = self::call('GET', '/api/customer/profile', ['id' => $userId]);

        return ($res && ($res['status'] ?? '') === 'OK') ? $res['details'] : null;
    }
    // [doc:end] profile

    private static $profiles = [];

    // [doc:start] userJson - The customer in the shape the partner apps expect (the User::asJson fields a partner may see), asked from the.
    /**
     * The customer in the shape the partner apps expect (the User::asJson fields a partner may see), asked from the
     * user-app system. Empty strings when the customer is unknown.
     */
    public static function userJson($userId)
    {
        $p = self::profileCached($userId);

        return [
            'id' => (int) $p['id'], 'uuid' => $p['uuid'] ?? '', 'username' => $p['username'] ?? '', 'first_name' => $p['first_name'] ?? ($p['name'] ?? ''),
            'last_name' => $p['last_name'] ?? '', 'email' => $p['email'] ?? '', 'contact_no' => $p['phone'] ?? '',
            'date_of_birth' => $p['dob'] ?? '', 'gender' => $p['gender'] ?? '', 'selfie' => $p['image'] ?? '', 'user_role' => 'User',
        ];
    }
    // [doc:end] userJson

    // [doc:start] profileCached - Profile, asked once per request for a customer; never null (empty strings when unknown).
    /**
     * Profile, asked once per request for a customer; never null (empty strings when unknown).
     */
    public static function profileCached($userId)
    {
        $userId = (int) $userId;
        if (!isset(self::$profiles[$userId])) {
            self::$profiles[$userId] = self::profile($userId)
                ?: ['id' => $userId, 'name' => '', 'phone' => '', 'email' => '', 'dob' => '', 'gender' => '', 'username' => '', 'first_name' => '', 'last_name' => '', 'image' => ''];
        }

        return self::$profiles[$userId];
    }
    // [doc:end] profileCached

    // [doc:start] notify - Notify
    /**
     * @return bool whether the customer was handed the notification
     */
    public static function notify($userId, $title, $body, $refId = '')
    {
        if (!self::isRemote()) {
            return self::localNotify((int) $userId, $title, $body, $refId) !== false;
        }
        $res = self::call('POST', '/api/customer/notify', ['user_id' => (int) $userId, 'title' => $title, 'body' => $body, 'ref' => (string) $refId]);

        return $res && ($res['status'] ?? '') === 'OK';
    }
    // [doc:end] notify

    // [doc:start] reportRating - Tells the customer app the average rating partners gave a customer (it keeps it on the customer's record; a service.
    /**
     * Tells the customer app the average rating partners gave a customer (it keeps it on the customer's record; a service
     * never writes a customer row).
     */
    public static function reportRating($userId, $average, $count = 0)
    {
        if (!self::isRemote()) {
            if (!(new \yii\db\Query())->from('user')->where(['id' => (int) $userId])->exists()) {
                return false;
            }
            \Yii::$app->db->createCommand()->update('user', ['average_rating' => round((float) $average, 2)], ['id' => (int) $userId])->execute();

            return true;
        }
        $res = self::call('POST', '/api/customer/rating', ['user_id' => (int) $userId, 'average' => (float) $average, 'count' => (int) $count]);

        return $res && ($res['status'] ?? '') === 'OK';
    }
    // [doc:end] reportRating

    // [doc:start] walletBalance - The customer's balance in their ONE wallet (it belongs to the customer app, not to any service).
    /**
     * The customer's balance in their ONE wallet (it belongs to the customer app, not to any service).
     *
     * @return array ['ok' => bool, 'balance' => float, 'error' => string|null]
     */
    public static function walletBalance($userId)
    {
        if (!self::isRemote()) {
            return class_exists('\\app\\modules\\customerapi\\services\\WalletLedger') ? \app\modules\customerapi\services\WalletLedger::balance($userId) : self::walletUnavailable();
        }

        return self::walletCall('GET', '/api/customer/wallet-balance', ['user_id' => (int) $userId]);
    }
    // [doc:end] walletBalance

    // [doc:start] walletDebit - Pays from the customer's wallet for something bought in a service. $ref identifies the purchase (a ride id): the same.
    /**
     * Pays from the customer's wallet for something bought in a service. $ref identifies the purchase (a ride id): the same
     * ref twice charges once.
     */
    public static function walletDebit($userId, $amount, $module, $reason, $ref = '')
    {
        if (!self::isRemote()) {
            return class_exists('\\app\\modules\\customerapi\\services\\WalletLedger') ? \app\modules\customerapi\services\WalletLedger::debit($userId, $amount, $module, $reason, $ref) : self::walletUnavailable();
        }

        return self::walletCall('POST', '/api/customer/wallet-debit', ['user_id' => (int) $userId, 'amount' => (float) $amount, 'module' => (int) $module, 'reason' => (string) $reason, 'ref' => (string) $ref]);
    }
    // [doc:end] walletDebit

    // [doc:start] walletCredit - Wallet Credit
    public static function walletCredit($userId, $amount, $module, $reason, $ref = '')
    {
        if (!self::isRemote()) {
            return class_exists('\\app\\modules\\customerapi\\services\\WalletLedger') ? \app\modules\customerapi\services\WalletLedger::credit($userId, $amount, $module, $reason, $ref) : self::walletUnavailable();
        }

        return self::walletCall('POST', '/api/customer/wallet-credit', ['user_id' => (int) $userId, 'amount' => (float) $amount, 'module' => (int) $module, 'reason' => (string) $reason, 'ref' => (string) $ref]);
    }
    // [doc:end] walletCredit

    private static function walletUnavailable()
    {
        return ['ok' => false, 'balance' => 0.0, 'error' => 'Wallet is not available'];
    }

    private static function walletCall($method, $path, array $data)
    {
        $res = self::call($method, $path, $data);
        if (!is_array($res) || ($res['status'] ?? '') !== 'OK' || !isset($res['details']) || !is_array($res['details'])) {
            return ['ok' => false, 'balance' => 0.0, 'error' => (is_array($res) && isset($res['error'])) ? (string) $res['error'] : 'The customer wallet did not answer'];
        }

        return $res['details'] + ['ok' => false, 'balance' => 0.0, 'error' => null];
    }

    // [doc:start] authenticate - Authenticate
    /**
     * @return array|null [id, name, phone] of the customer the code belongs to
     */
    public static function authenticate($authCode)
    {
        if (!self::isRemote()) {
            $uid = (new \app\modules\auth\services\AuthSettings())->getAuthSession($authCode);

            return $uid ? self::profile($uid) : null;
        }
        $res = self::call('POST', '/api/customer/authenticate', ['auth_code' => (string) $authCode]);

        return ($res && ($res['status'] ?? '') === 'OK') ? $res['details'] : null;
    }
    // [doc:end] authenticate

    // [doc:start] localNotify - Used by the gateway itself: the user app's own delivery of a notification.
    /** Used by the gateway itself: the user app's own delivery of a notification. */
    public static function localNotify($userId, $title, $body, $refId = '')
    {
        $push = Yii::$app->has('push') ? Yii::$app->get('push') : new \app\modules\messaging\services\PushService();

        return $push->deliverToCustomer($refId, $userId, $title, $body);
    }
    // [doc:end] localNotify

    private static function call($method, $path, array $data)
    {
        $url = getenv('CUSTOMER_API_URL') ?: (Yii::$app->params['customerApi']['url'] ?? '');
        $service = self::serviceKey();
        $secret = ServiceSignature::secret($service);
        if ($url === '' || $secret === '') {
            Yii::error('customer gateway is not configured (CUSTOMER_API_URL / CUSTOMER_API_SECRET_' . strtoupper($service) . ')', 'api');

            return null;
        }
        $body = $method === 'POST' ? json_encode($data) : '';
        $query = $method === 'GET' && $data ? '?' . http_build_query($data) : '';
        $headers = ServiceSignature::sign($service, $secret, $method, $path, $body);
        $h = ['Content-Type: application/json'];
        foreach ($headers as $k => $v) {
            $h[] = $k . ': ' . $v;
        }
        $ch = curl_init(rtrim($url, '/') . $path . $query);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8, CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_HTTPHEADER => $h, CURLOPT_CUSTOMREQUEST => $method,
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $out = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $json = $out ? json_decode($out, true) : null;
        if (!is_array($json)) {
            Yii::error('customer gateway ' . $method . ' ' . $path . ' failed (HTTP ' . $code . ')', 'api');

            return null;
        }

        return $json;
    }
}
