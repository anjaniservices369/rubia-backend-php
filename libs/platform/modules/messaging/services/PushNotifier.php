<?php

namespace app\modules\messaging\services;

use Yii;

/**
 * Provider-neutral push sender. The device_token stored in auth_session is passed to the configured driver.
 *
 * Driver (PUSH_DRIVER env var or config/secrets.php ['push']['driver']):
 *   none      - nothing is sent (default); notifications are still stored in the database.
 *   onesignal - OneSignal REST API. device_token = OneSignal subscription id.
 *               Needs ONESIGNAL_APP_ID and ONESIGNAL_API_KEY (or secrets ['push']['onesignal']).
 *               Separate customer/partner OneSignal apps: ONESIGNAL_APP_ID_USER / _DRIVER and
 *               ONESIGNAL_API_KEY_USER / _DRIVER (or secrets ['push']['onesignal']['apps']['user'|'driver']).
 *   expo      - Expo push service. device_token = ExponentPushToken[...].
 *               Optional EXPO_ACCESS_TOKEN (or secrets ['push']['expoAccessToken']).
 */
class PushNotifier
{
    // [doc:start] appFor - App For
    public static function appFor($userId)
    {
        $role = \app\models\User::find()->select('user_role')->where(['id' => (int) $userId])->scalar();

        return in_array($role, [\app\models\User::ROLE_DRIVER, \app\models\User::ROLE_TAXI], true) ? 'driver' : 'user';
    }
    // [doc:end] appFor

    // [doc:start] send - Send
    /**
     * @return string|false raw provider response, or false when nothing was sent
     */
    public static function send($deviceToken, $title, $body, array $data = [], $app = null)
    {
        if (empty($deviceToken)) {
            return false;
        }

        switch (self::driver()) {
            case 'onesignal':
                return self::oneSignal($deviceToken, $title, $body, $data, $app);
            case 'expo':
                return self::expo($deviceToken, $title, $body, $data);
            default:
                return false;
        }
    }
    // [doc:end] send

    // [doc:start] describe - Configuration status for the admin dashboard.
    /**
     * Configuration status for the admin dashboard.
     *
     * @return array [driver => string, ready => bool, note => string]
     */
    public static function describe()
    {
        $driver = self::driver();
        if ($driver === 'onesignal') {
            $credentials = self::credentials();
            $ready = $credentials['appId'] && $credentials['apiKey'];

            return ['driver' => 'OneSignal', 'ready' => (bool) $ready, 'note' => $ready ? 'ready' : 'REST API key missing'];
        }
        if ($driver === 'expo') {
            return ['driver' => 'Expo', 'ready' => true, 'note' => 'ready'];
        }

        return ['driver' => 'none', 'ready' => false, 'note' => 'push is turned off'];
    }
    // [doc:end] describe

    private static function oneSignal($token, $title, $body, array $data, $app = null)
    {
        // OneSignal subscription ids are UUIDs. Anything else is a leftover (an old FCM registration token from the
        // Firebase days): OneSignal would answer 400 for it, so skip it instead of logging an error per send.
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', (string) $token)) {
            Yii::info('push skipped: device token is not a OneSignal subscription id', 'push');
            return false;
        }

        $credentials = self::credentials($app);
        $appId = $credentials['appId'];
        $apiKey = $credentials['apiKey'];
        if (!$appId || !$apiKey) {
            Yii::error('OneSignal driver selected but ONESIGNAL_APP_ID / ONESIGNAL_API_KEY are not set', 'push');
            return false;
        }

        $payload = [
            'app_id' => $appId,
            'target_channel' => 'push',
            'include_subscription_ids' => [(string) $token],
            'headings' => ['en' => (string) $title],
            'contents' => ['en' => (string) $body],
            'priority' => 10,
            'existing_android_channel_id' => self::channel($data),
        ];
        if ($data) {
            $payload['data'] = $data;
        }

        return self::post('https://api.onesignal.com/notifications?c=push', $payload, [
            'Authorization: Key ' . $apiKey,
        ]);
    }

    private static function expo($token, $title, $body, array $data)
    {
        $headers = [];
        if ($access = self::setting('EXPO_ACCESS_TOKEN', 'expoAccessToken')) {
            $headers[] = 'Authorization: Bearer ' . $access;
        }

        return self::post('https://exp.host/--/api/v2/push/send', [
            'to' => (string) $token,
            'title' => (string) $title,
            'body' => (string) $body,
            'sound' => 'default',
            'priority' => 'high',
            'channelId' => self::channel($data),
            'data' => (object) $data,
        ], $headers);
    }

    /**
     * Which provider is used. An explicit PUSH_DRIVER (environment / secrets) wins; when none is set, OneSignal is
     * used as soon as its REST key has been entered in the dashboard.
     */
    private static function driver()
    {
        $driver = self::setting('PUSH_DRIVER', 'driver', '');
        if ($driver !== '' && $driver !== null) {
            return $driver;
        }

        return self::credentials()['apiKey'] ? 'onesignal' : 'none';
    }

    // [doc:start] credentials - OneSignal App ID and REST key, first source that has a value:.
    /**
     * OneSignal App ID and REST key, first source that has a value:
     *   1. the dashboard: Web Settings > Notification ("OneSignal App ID", "OneSignal REST API Key"),
     *   2. per-app environment / secrets (customer = 'user', partner = 'driver'),
     *   3. shared environment variable / config/secrets.php (fallback for servers that do not use the dashboard).
     * The dashboard wins, so pasting a new key there takes effect at once, whatever the server environment holds.
     *
     * @param string|null $app 'user' or 'driver'
     * @return array [appId => string, apiKey => string]
     */
    public static function credentials($app = null)
    {
        $suffix = $app ? strtoupper($app) : null;

        $appId = self::webSetting('onesignal_app_id')
            ?: ($suffix ? self::setting('ONESIGNAL_APP_ID_' . $suffix, 'onesignal.apps.' . $app . '.appId') : null)
            ?: self::setting('ONESIGNAL_APP_ID', 'onesignal.appId');

        $apiKey = self::webSetting('onesignal_api_key')
            ?: ($suffix ? self::setting('ONESIGNAL_API_KEY_' . $suffix, 'onesignal.apps.' . $app . '.apiKey') : null)
            ?: self::setting('ONESIGNAL_API_KEY', 'onesignal.apiKey');

        return ['appId' => trim((string) $appId), 'apiKey' => trim((string) $apiKey)];
    }
    // [doc:end] credentials

    /**
     * A value from the dashboard settings table; empty string when missing or when the table is unavailable.
     */
    private static function webSetting($key)
    {
        try {
            $value = (new \yii\db\Query())->select('value')->from('web_setting')
                ->where(['setting_key' => $key, 'status' => 1])->scalar();
        } catch (\Throwable $e) {
            return '';
        }

        return $value === false || $value === null ? '' : trim(\app\modules\admin\models\SettingVault::decrypt((string) $value));
    }
    /**
     * Android notification channel for a push. The channel decides the sound, so a "newOrder" push must name
     * the booking-alert channel the partner app creates. Override with params['push']['channels'].
     */
    private static function channel(array $data)
    {
        $channels = (Yii::$app->params['push']['channels'] ?? []) + [
            'newOrder' => 'new_booking_alert',
            'default' => 'other_high_importance_channel',
        ];
        $type = isset($data['type']) ? (string) $data['type'] : '';

        return $channels[$type] ?? $channels['default'];
    }

    private static function post($url, array $payload, array $headers = [])
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json', 'Accept: application/json'], $headers),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
        ]);
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($response === false) {
            Yii::error('push curl error: ' . curl_error($ch), 'push');
        } elseif ($status >= 300) {
            Yii::error('push provider returned HTTP ' . $status . ': ' . $response, 'push');
        }
        curl_close($ch);

        return $response;
    }

    /**
     * Reads a setting from the environment first, then config/secrets.php ['push'] (dot path for nesting).
     */
    private static function setting($env, $path, $default = null)
    {
        $value = \app\modules\admin\models\Integrations::env($env);
        if ($value !== '') {
            return $value;
        }

        $node = (require Yii::getAlias('@app/config/secrets-loader.php'))['push'] ?? [];
        foreach (explode('.', $path) as $part) {
            if (!is_array($node) || !isset($node[$part])) {
                return $default;
            }
            $node = $node[$part];
        }

        return $node;
    }
}
