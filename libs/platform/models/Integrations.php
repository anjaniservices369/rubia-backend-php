<?php

namespace app\modules\admin\models;

use Yii;

/**
 * The third-party services this system talks to, each one described on its own: what it is used for, which settings it needs (and which of
 * them are protected, see SettingGuide), where to get the keys, whether it is set up, and how to test the connection.
 *
 * The dashboard module "Integrations" is built on this list: one page per service, so nothing about a service is spread over other screens.
 * Every service reads its keys the same way: the dashboard value first, then the environment variable named like the setting in capitals.
 */
class Integrations
{
    /** @return array id => definition */
    public static function all()
    {
        return self::definitions();
    }

    private static function definitions()
    {
        return [
            'razorpay' => [
                'name' => 'Razorpay', 'category' => 'Payments', 'icon' => '&#128179;',
                'purpose' => 'Joining fee, wallet top-ups and subscriptions paid in the partner app (checkout and the automated UPI QR).',
                'fields' => ['razorpay_key_id' => true, 'razorpay_key_secret' => true, 'razorpay_webhook_secret' => false],
                'docs' => 'https://razorpay.com/docs/payments/dashboard/account-settings/api-keys/',
                'webhook' => '/api/driver/razorpay-webhook',
                'webhookNote' => 'Add this address in Razorpay (Account & Settings > Webhooks) with the events payment.captured, order.paid, payment.failed and qr_code.credited, and type the same secret in both places.',
                'testable' => true,
            ],
            'cashfree_pg' => [
                'name' => 'Cashfree Payment Gateway', 'category' => 'Payments', 'icon' => '&#128179;',
                'purpose' => 'Older online payment orders. The partner payments now use Razorpay; keep this only if some app version still calls it.',
                'fields' => ['cashfree_pg_client_id' => true, 'cashfree_pg_client_secret' => true, 'cashfree_pg_mode' => false],
                'docs' => 'https://docs.cashfree.com/docs/api-keys', 'testable' => true,
            ],
            'cashfree_verify' => [
                'name' => 'Cashfree Aadhaar Verification', 'category' => 'Identity checks', 'icon' => '&#129706;',
                'purpose' => 'Aadhaar OTP and Aadhaar document checks when a partner registers.',
                'fields' => ['cashfree_verify_client_id' => true, 'cashfree_verify_client_secret' => true],
                'docs' => 'https://docs.cashfree.com/docs/verification-suite', 'testable' => false,
            ],
            'verify24x7' => [
                'name' => 'verify24x7', 'category' => 'Identity checks', 'icon' => '&#129706;',
                'purpose' => 'Police verification and face match of partners.',
                'fields' => ['verify24x7_username' => true, 'verify24x7_password' => true, 'verify24x7_facematch_token' => false],
                'docs' => 'https://verify24x7.in', 'testable' => true,
            ],
            'sms' => [
                'name' => '2Factor SMS', 'category' => 'Messaging', 'icon' => '&#128172;',
                'purpose' => 'The login OTP SMS for customers and partners.',
                'fields' => ['sms_api_key' => true],
                'docs' => 'https://2factor.in/v3/', 'testable' => true,
            ],
            'onesignal' => [
                'name' => 'OneSignal', 'category' => 'Messaging', 'icon' => '&#128276;',
                'purpose' => 'Push notifications: ride offers to partners, booking updates to customers.',
                'fields' => ['onesignal_app_id' => true, 'onesignal_api_key' => true],
                'docs' => 'https://documentation.onesignal.com/docs/keys-and-ids', 'testable' => true,
            ],
            'gmail' => [
                'name' => 'Gmail SMTP', 'category' => 'Messaging', 'icon' => '&#9993;',
                'purpose' => 'E-mails: password reset (sender, subject and wording of the reset e-mail) and contact forms. Sent through Gmail\'s own SMTP server, logged in as a real Gmail account. That account needs 2-Step Verification turned on, so an App Password can be created for it -- Gmail no longer accepts the account\'s normal password over SMTP.',
                'fields' => ['gmail_username' => true, 'gmail_app_password' => true, 'mail_from' => false, 'mail_from_name' => false, 'reset_subject' => false, 'reset_text' => false],
                'multiline' => ['reset_text'],
                'docs' => 'https://support.google.com/accounts/answer/185833', 'testable' => false,
            ],
            'google_maps' => [
                'name' => 'Maps and routes', 'category' => 'Maps', 'icon' => '&#128205;',
                'purpose' => 'Road distance and fares, and finding a city from an address. Google, Mapbox and OpenStreetMap are built in: paste the keys of the ones you use and set the order they are asked in; one that has no key or is refused is skipped for the next. Also the maps shown in the dashboard (browser key) and in the mobile apps (app key).',
                'fields' => ['google_maps_server_key' => false, 'mapbox_access_token' => false, 'maps_provider_order' => false, 'google_maps_browser_key' => false, 'app_google_map_api' => false],
                'anyOf' => ['google_maps_server_key', 'mapbox_access_token'],
                'docs' => 'https://developers.google.com/maps/documentation/geocoding/get-api-key', 'testable' => true,
            ],
            'storage' => [
                'name' => 'Photo and document storage (Cloudflare R2)', 'category' => 'Storage', 'icon' => '&#128451;',
                'purpose' => 'Ride photos, and the identity documents and profile photos that are kept for life. Any S3-compatible storage works.',
                'fields' => ['media_driver' => false, 'media_s3_endpoint' => true, 'media_s3_bucket' => true, 'media_s3_bucket_documents' => false, 'media_s3_region' => false,
                    'media_s3_key_id' => true, 'media_s3_key_secret' => true, 'media_s3_documents_key_id' => false, 'media_s3_documents_key_secret' => false,
                    'media_public_base_url' => false, 'media_ride_retention_days' => false],
                'docs' => 'https://developers.cloudflare.com/r2/', 'testable' => true,
            ],
            'charges' => [
                'name' => 'Charges and commission', 'category' => 'Charges', 'icon' => '&#128176;', 'plain' => true, 'services' => ['haltingdriver'],
                'purpose' => 'What the company earns and charges on Halting drivers rides: commission, GST, platform and secured fee, cancellation earning and the smallest withdrawal.',
                'fields' => ['default_admin_commission_raider' => false, 'gst' => false, 'platform_charges' => false, 'secured_fee' => false, 'admin_cancellation_earning' => false, 'min_withdraw' => false],
                'percent' => ['default_admin_commission_raider', 'gst'],
                'testable' => false,
            ],
            'driver_rules' => [
                'name' => 'Driver settings', 'category' => 'Charges', 'icon' => '&#129489;', 'plain' => true, 'services' => ['haltingdriver'],
                'purpose' => 'Rules for Halting drivers partners: joining fee, whether onboarding is enforced, minimum wallet balance, referral reward and cancellation charge.',
                'fields' => ['driver_joining_fee' => false, 'enforce_driver_onboarding' => false, 'driver_min_wallet_bal' => false, 'driver_referral_earning' => false, 'driver_cancellation_charge' => false],
                'bool' => ['enforce_driver_onboarding'],
                'testable' => false,
            ],
            'user_rules' => [
                'name' => 'Customer settings', 'category' => 'Charges', 'icon' => '&#128100;', 'plain' => true, 'services' => ['haltingdriver', 'customer'],
                'purpose' => 'Rules for the customers of Halting drivers: referral reward, cancellation charge, how long the driver waits for the customer and how often drivers are notified of a new booking.',
                'fields' => ['user_referral_earning' => false, 'user_cancellation_charge' => false, 'user_waiting_time' => false, 'notification_period' => false],
                'testable' => false,
            ],
            'openmoney' => [
                'name' => 'OpenMoney', 'category' => 'Payouts', 'icon' => '&#128181;',
                'purpose' => 'Paying out partner earnings to bank accounts (taxi partner payouts).',
                'fields' => ['open_money_key' => true, 'open_money_secret' => true],
                'docs' => 'https://docs.open.money', 'testable' => false,
            ],
        ];
    }

    // [doc:start] visible - The integrations that belong to this dashboard's service (an entry with 'services' is shown only there; the super admin sees all).
    public static function visible()
    {
        $service = (string) (Yii::$app->params['serviceEntry']['service'] ?? '');

        return array_filter(self::all(), function ($def) use ($service) {
            return empty($def['services']) || $service === '' || in_array($service, $def['services'], true);
        });
    }
    // [doc:end] visible

    // [doc:start] find - One integration of this dashboard's service by its id, or null.
    public static function find($id)
    {
        return self::visible()[$id] ?? null;
    }
    // [doc:end] find

    // [doc:start] serviceName - The service this dashboard belongs to (its settings are its own), for the page headings.
    public static function serviceName()
    {
        $names = ['haltingdriver' => 'Halting drivers', 'cartaxi' => 'Car taxi', 'autotaxi' => 'Auto taxi', 'biketaxi' => 'Bike taxi', 'customer' => 'Customer app'];

        return $names[Yii::$app->params['serviceEntry']['service'] ?? ''] ?? 'All services';
    }
    // [doc:end] serviceName

    // [doc:start] env - An environment variable for this service: <SERVICE>_<NAME> first (for example HALTINGDRIVER_RAZORPAY_KEY_ID), then <NAME>.
    /**
     * Every service of the system has its own settings. On a server that runs several services, an environment variable can be given to one
     * service only by prefixing it with the service key in capitals (HALTINGDRIVER_, CARTAXI_, AUTOTAXI_, BIKETAXI_, CUSTOMER_); without the
     * prefix it applies to every service.
     *
     * @return string '' when neither is set
     */
    public static function env($name)
    {
        $name = strtoupper($name);
        $service = strtoupper((string) (Yii::$app->params['serviceEntry']['service'] ?? ''));
        if ($service !== '') {
            $v = getenv($service . '_' . $name);
            if ($v !== false && trim($v) !== '') {
                return trim($v);
            }
        }
        $v = getenv($name);

        return $v === false ? '' : trim($v);
    }
    // [doc:end] env

    // [doc:start] raw - The exact setting: the dashboard value first, then the environment variable named like the setting in capitals ('' when neither is set).
    public static function raw($key)
    {
        $v = '';
        try {
            $v = trim((string) (new WebSetting())->getSettingBykey($key));
        } catch (\Throwable $e) {
            $v = '';
        }
        if ($v === '') {
            $v = self::env($key);
        }

        return $v;
    }
    // [doc:end] raw

    // [doc:start] value - A setting as the code uses it: the dashboard value first, then the environment variable named like the setting in capitals ('' when neither is set).
    public static function value($key)
    {
        return self::raw($key);
    }
    // [doc:end] value

    // [doc:start] userMail - The sender and the password reset wording for the e-mails to customers (Integrations > Gmail SMTP).
    /**
     * @param string $defaultFrom the sender used when the customers have none of their own
     * @param string $defaultSubject
     * @param string $defaultText the wording above the reset link
     * @return array ['from' => address or [address => name], 'subject', 'text']
     */
    public static function userMail($defaultFrom, $defaultSubject = 'Restore your password', $defaultText = 'To restore your password, please follow this link')
    {
        $address = self::raw('mail_from');
        $name = self::raw('mail_from_name');
        if ($address !== '' && !filter_var($address, FILTER_VALIDATE_EMAIL)) {
            $address = ''; // an invalid sender is never used
        }
        $from = $address !== '' ? $address : $defaultFrom;

        return [
            'from' => $name !== '' ? [$from => $name] : $from,
            'subject' => self::raw('reset_subject') ?: $defaultSubject,
            'text' => self::raw('reset_text') ?: $defaultText,
        ];
    }
    // [doc:end] userMail

    // [doc:start] managedKeys - The setting keys that belong to an integration (the other screens do not edit them any more).
    public static function managedKeys()
    {
        $keys = [];
        foreach (self::visible() as $def) {
            $keys = array_merge($keys, array_keys($def['fields']));
        }

        return $keys;
    }
    // [doc:end] managedKeys

    // [doc:start] status - ready (all required settings set), partial (some) or missing (none), with the names of what is missing.
    /**
     * @return array ['state' => ready|partial|missing, 'missing' => string[], 'mode' => test|live|'']
     */
    public static function status($id)
    {
        $def = self::find($id);
        $required = array_keys(array_filter($def['fields']));
        if (!empty($def['anyOf'])) {
            // one of several services is enough (for example Google or Mapbox)
            $anySet = array_filter($def['anyOf'], function ($k) {
                return self::raw($k) !== '';
            });
            $required = $anySet ? [] : $def['anyOf'];
        }
        $missing = [];
        foreach ($required as $key) {
            if (self::raw($key) === '') {
                $missing[] = $key;
            }
        }
        $state = !$missing ? 'ready' : (count($missing) === count($required) ? 'missing' : 'partial');
        $mode = '';
        if ($id === 'razorpay' && $state !== 'missing') {
            $mode = strpos(self::raw('razorpay_key_id'), 'rzp_live_') === 0 ? 'live' : 'test';
        } elseif ($id === 'cashfree_pg' && $state !== 'missing') {
            $mode = self::raw('cashfree_pg_mode') === 'production' ? 'live' : 'test';
        }

        return ['state' => $state, 'missing' => $missing, 'mode' => $mode];
    }
    // [doc:end] status

    // [doc:start] save - Saves the boxes of one integration. A protected box left empty keeps the saved value; every other box is saved as typed.
    /**
     * @param string $id
     * @param array  $post setting_key => typed value
     * @return int how many settings were changed
     */
    public static function save($id, array $post)
    {
        $def = self::find($id);
        $changed = 0;
        foreach (array_keys($def['fields']) as $key) {
            if (!array_key_exists($key, $post)) {
                continue;
            }
            $value = trim((string) $post[$key]);
            if (!empty($def['plain']) && in_array($key, $def['bool'] ?? [], true)) {
                $value = $value === '1' ? '1' : '0';
            } elseif (!empty($def['plain'])) {
                // money and percentages: a number, not negative, and a percentage not above 100
                if ($value === '' || !is_numeric($value) || (float) $value < 0) {
                    throw new \InvalidArgumentException(SettingGuide::label($key) . ' must be a number, zero or more.');
                }
                if (in_array($key, $def['percent'] ?? [], true) && (float) $value > 100) {
                    throw new \InvalidArgumentException(SettingGuide::label($key) . ' is a percentage: at most 100.');
                }
                $value = (string) (0 + $value);
            }
            if (SettingGuide::isSecret($key) && $value === '') {
                continue; // keep the saved secret
            }
            $row = WebSetting::findOne(['setting_key' => $key]);
            if (!$row) {
                $row = new WebSetting();
                $row->setting_key = $key;
                $row->name = $key;
                $row->type_id = !empty($def['plain']) ? 70 : WebSetting::PAYMENT_GATEWAY;
                $row->status = WebSetting::STATUS_ACTIVE;
            }
            if (!SettingGuide::isSecret($key) && (string) $row->value === $value) {
                continue;
            }
            $row->value = $value;
            $row->save(false);
            $changed++;
        }

        return $changed;
    }
    // [doc:end] save

    private static function http($url, array $headers = [], $method = 'GET', $body = null)
    {
        $ch = curl_init($url);
        $o = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $headers];
        if ($body !== null) {
            $o[CURLOPT_POSTFIELDS] = $body;
        }
        curl_setopt_array($ch, $o);
        $out = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        return [$code, $out === false ? '' : (string) $out, $err];
    }

    // [doc:start] test - Checks the connection and the keys of one integration with a harmless call. Never creates a payment and never sends a message.
    /**
     * @return array ['ok' => bool, 'message' => string]
     */
    public static function test($id)
    {
        $def = self::find($id);
        if (!$def || empty($def['testable'])) {
            return ['ok' => false, 'message' => 'There is no automatic test for this service.'];
        }
        if (self::status($id)['state'] !== 'ready') {
            return ['ok' => false, 'message' => 'Fill in the required boxes first (' . implode(', ', self::status($id)['missing'] ?: ['all of them']) . ').'];
        }
        return self::runTest($id);
    }
    // [doc:end] test

    private static function runTest($id)
    {
        try {
            switch ($id) {
                case 'razorpay':
                    \app\modules\payments\services\Razorpay::request('GET', '/orders?count=1');
                    return ['ok' => true, 'message' => 'Razorpay accepted the key and secret (' . (self::status('razorpay')['mode'] ?: 'test') . ' mode).'];
                case 'cashfree_pg':
                    $base = \app\modules\payments\services\CashFree::pgBase();
                    [$code] = self::http($base . '/orders/connection-test-nonexistent', ['x-api-version: 2022-09-01', 'x-client-id: ' . self::value('cashfree_pg_client_id'), 'x-client-secret: ' . self::value('cashfree_pg_client_secret')]);
                    return $code === 401 || $code === 403
                        ? ['ok' => false, 'message' => 'Cashfree refused the keys (HTTP ' . $code . ').']
                        : ['ok' => true, 'message' => 'Cashfree accepted the keys (' . (self::status('cashfree_pg')['mode'] ?: 'test') . ' mode).'];
                case 'verify24x7':
                    [$code, $body] = self::http('https://mvp.verify24x7.in/verifyManualApi/api/users/signIn', ['Content-Type: application/x-www-form-urlencoded'], 'POST',
                        'username=' . urlencode(self::value('verify24x7_username')) . '&password=' . urlencode(self::value('verify24x7_password')));
                    return $code === 200 && strpos($body, 'apiKey') !== false ? ['ok' => true, 'message' => 'verify24x7 accepted the user name and password.'] : ['ok' => false, 'message' => 'verify24x7 refused the login (HTTP ' . $code . ').'];
                case 'sms':
                    [$code, $body] = self::http('https://2factor.in/API/V1/' . rawurlencode(self::value('sms_api_key')) . '/BAL/SMS');
                    $j = json_decode($body, true);
                    return ($j['Status'] ?? '') === 'Success' ? ['ok' => true, 'message' => '2Factor accepted the key. SMS balance: ' . ($j['Details'] ?? '?') . '.'] : ['ok' => false, 'message' => '2Factor refused the key (' . ($j['Details'] ?? 'HTTP ' . $code) . ').'];
                case 'onesignal':
                    [$code] = self::http('https://onesignal.com/api/v1/apps/' . rawurlencode(self::value('onesignal_app_id')), ['Authorization: Basic ' . self::value('onesignal_api_key')]);
                    return $code === 200 ? ['ok' => true, 'message' => 'OneSignal accepted the App ID and REST API key.'] : ['ok' => false, 'message' => 'OneSignal refused them (HTTP ' . $code . '). Check that the REST API key belongs to this App ID.'];
                case 'google_maps':
                    return self::testGoogle();
                case 'storage':
                    return self::testStorage();
            }
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        return ['ok' => false, 'message' => 'No test.'];
    }

    /**
     * Asks every maps service that has its key for an address and a road, and says which one fails and what to switch on.
     */
    private static function testGoogle()
    {
        $report = [];
        $working = 0;
        foreach (\app\modules\geo\services\MapProviders::all() as $p) {
            if (!$p->isConfigured()) {
                continue;
            }
            $a = $p->geocode('Hyderabad, India');
            $line = $p->label() . ' - addresses: ' . ($a['ok'] ? 'works' : 'NOT working (' . $a['message'] . ')');
            if ($p->canRoute()) {
                try {
                    $p->route(17.3626, 78.4313, 17.4401, 78.3489);
                    $line .= ', roads and fares: works';
                    $working++;
                } catch (\app\modules\geo\services\DistanceUnavailableException $e) {
                    $line .= ', roads and fares: NOT working (' . $e->reason . ')';
                }
            }
            $report[] = $line;
        }
        $ok = $working > 0;

        return ['ok' => $ok, 'message' => implode('; ', $report) . ($ok ? '.' : '. No service can give road distances: switch the Routes API on for the Google key\'s project (Google Cloud > APIs & Services > Library), or paste a Mapbox access token.')];
    }

    private static function testStorage()
    {
        $S3 = '\\app\\modules\\filestorage\\services\\S3Client';
        $report = [];
        $buckets = ['main bucket' => $S3::setting('media_s3_bucket')];
        if ($S3::setting('media_s3_bucket_documents') !== '') {
            $buckets['documents bucket'] = $S3::setting('media_s3_bucket_documents');
        }
        foreach ($buckets as $label => $bucket) {
            $key = 'connection-test/' . bin2hex(random_bytes(6)) . '.txt';
            $S3::put($key, 'rubia connection test', 'text/plain', $bucket);
            $ok = $S3::get($key, $bucket) === 'rubia connection test';
            $linkOk = @file_get_contents($S3::presignedGet($key, 60, $bucket)) === 'rubia connection test';
            // the documents bucket is locked for life on purpose: the test file may stay there, which is harmless
            $S3::delete($key, $bucket);
            $report[] = $label . ': write ' . ($ok ? 'ok' : 'FAILED') . ', read ' . ($ok ? 'ok' : 'FAILED') . ', signed link ' . ($linkOk ? 'ok' : 'FAILED');
            if (!$ok || !$linkOk) {
                return ['ok' => false, 'message' => implode('; ', $report)];
            }
        }

        return ['ok' => true, 'message' => implode('; ', $report) . '.'];
    }
}
