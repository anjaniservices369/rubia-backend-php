<?php

namespace app\modules\admin\models;

use yii\helpers\Html;

/**
 * What each key-like dashboard setting is, where to find it and how it is treated, so the dashboard can say it next to every box.
 *
 * Three levels:
 *   secret   PROTECTED: a password, secret key or token. Saved encrypted, hidden after saving, never shown again, never sent to an app. Type a new value to replace it.
 *   private  an identifier (key id, username, account name). Not a password and shown in the box, but keep it to yourself.
 *   plain    an ordinary setting (an address, a name, a number); shown as it is.
 * Every setting not listed here is "plain".
 */
class SettingGuide
{
    const SECRET = 'secret';
    const PRIVATE_ID = 'private';
    const PLAIN = 'plain';

    /** key => [group, level, where to find it / what to put] */
    private static function registry()
    {
        static $all = null;
        if ($all !== null) {
            return $all;
        }
        $all = self::baseRegistry();
        $all['mail_from'] = ['E-mail and reset forms', self::PLAIN, 'The sender address of the password reset e-mails (for example support@yourcompany.com). It must be an address your e-mail service is allowed to send from. Empty = the standard sender.'];
        $all['mail_from_name'] = ['E-mail and reset forms', self::PLAIN, 'The sender name people see. Empty = the application name.'];
        $all['reset_subject'] = ['E-mail and reset forms', self::PLAIN, 'The subject of the password reset e-mail. Empty = "Restore your password".'];
        $all['reset_text'] = ['E-mail and reset forms', self::PLAIN, 'The wording above the reset link in the password reset e-mail. The link is added below it. Empty = the standard wording.'];

        return $all;
    }

    private static function baseRegistry()
    {
        return [
            // Razorpay
            'razorpay_key_id' => ['Razorpay (payments)', self::PRIVATE_ID, 'Razorpay dashboard > Account & Settings > API Keys. Starts with rzp_test_ (test mode) or rzp_live_ (live mode).'],
            'razorpay_key_secret' => ['Razorpay (payments)', self::SECRET, 'Shown by Razorpay only once, when the key is created (if lost, generate a new key pair there). Must belong to the key id above.'],
            'razorpay_webhook_secret' => ['Razorpay (payments)', self::SECRET, 'The secret you type when you add the webhook in Razorpay (Account & Settings > Webhooks). The same text must be in both places.'],
            // Cashfree
            'cashfree_pg_client_id' => ['Cashfree (payment gateway)', self::PRIVATE_ID, 'Cashfree dashboard > Developers > API Keys (Payment Gateway): the App ID.'],
            'cashfree_pg_client_secret' => ['Cashfree (payment gateway)', self::SECRET, 'The Secret Key next to the App ID. Shown only when generated.'],
            'cashfree_pg_mode' => ['Cashfree (payment gateway)', self::PLAIN, 'test (Cashfree sandbox, no real money) or production.'],
            'cashfree_verify_client_id' => ['Cashfree (Aadhaar verification)', self::PRIVATE_ID, 'Cashfree dashboard > Verification Suite > API Keys: the Client ID.'],
            'cashfree_verify_client_secret' => ['Cashfree (Aadhaar verification)', self::SECRET, 'The Client Secret next to the Client ID.'],
            // verify24x7
            'verify24x7_username' => ['Police verification and face match (verify24x7)', self::PRIVATE_ID, 'The user name verify24x7 gave you.'],
            'verify24x7_password' => ['Police verification and face match (verify24x7)', self::SECRET, 'The password verify24x7 gave you.'],
            'verify24x7_facematch_token' => ['Police verification and face match (verify24x7)', self::SECRET, 'The face-match auth token verify24x7 gave you.'],
            // Photo storage
            'media_driver' => ['Photo storage', self::PLAIN, 'Empty = the server disk. s3 = object storage (Cloudflare R2, Backblaze B2, Spaces, S3). Set it last, after the keys below.'],
            'media_s3_endpoint' => ['Photo storage', self::PLAIN, 'Cloudflare R2: https://<Account ID>.r2.cloudflarestorage.com (without the bucket name). Account ID: Cloudflare dashboard > R2.'],
            'media_s3_bucket' => ['Photo storage', self::PLAIN, 'The private bucket for ride photos (the selfie and car photos that are deleted after the retention days).'],
            'media_s3_bucket_documents' => ['Photo storage', self::PLAIN, 'A second private bucket for the documents and profile photos kept for life. Add an indefinite bucket lock rule to it in Cloudflare. Empty = the same bucket as above.'],
            'media_s3_region' => ['Photo storage', self::PLAIN, 'Leave empty for Cloudflare R2 (it uses "auto"). Other providers: their region name, for example ap-south-1.'],
            'media_s3_key_id' => ['Photo storage', self::PRIVATE_ID, 'Cloudflare R2 > Manage API tokens > create a token with "Object Read & Write": the Access Key ID.'],
            'media_s3_key_secret' => ['Photo storage', self::SECRET, 'The Secret Access Key of that token. Cloudflare shows it only once.'],
            'media_s3_documents_key_id' => ['Photo storage', self::PRIVATE_ID, 'Optional. Cloudflare R2 > Manage API tokens > create a SECOND token limited to the documents bucket only: its Access Key ID. Empty = the main key is used.'],
            'media_s3_documents_key_secret' => ['Photo storage', self::SECRET, 'The Secret Access Key of that second token (Cloudflare shows it only once). Keeps the key that reaches the identity documents apart from the key used for ride photos.'],
            'media_public_base_url' => ['Photo storage', self::PLAIN, 'Optional CDN address of a separate PUBLIC bucket. Leave empty: every photo then opens through a short private link (recommended).'],
            'media_ride_retention_days' => ['Photo storage', self::PLAIN, 'Ride photos older than this many days are deleted (default 180). Documents and profile photos are never deleted.'],
            // Messaging, maps, e-mail, payouts
            'sms_api_key' => ['2Factor SMS', self::SECRET, '2Factor dashboard > API: your API key (the same text is used in the login OTP calls).'],
            'mail_username' => ['E-mail (SMTP)', self::PRIVATE_ID, 'The full mailbox address that sends these e-mails (e.g. info@rubia.services).'],
            'mail_password' => ['E-mail (SMTP)', self::SECRET, 'That mailbox\'s password (cPanel > Email Accounts).'],
            'google_maps_server_key' => ['Maps and routes', self::SECRET, 'Google Cloud Console > APIs & Services > Credentials: a key restricted to the Directions and Geocoding APIs and to the IP address of your server.'],
            'google_maps_browser_key' => ['Google Maps', self::PRIVATE_ID, 'A key restricted to your dashboard web address (HTTP referrer). A browser key is visible in the page, so the restriction is its protection.'],
            // Charges and commission (Halting drivers)
            'default_admin_commission_raider' => ['Charges and commission', self::PLAIN, 'Percent of each ride fare the company keeps as commission when a partner has no commission of his own. 0 to 100.'],
            'gst' => ['Charges and commission', self::PLAIN, 'GST in percent added to the ride fare. 0 to 100.'],
            'platform_charges' => ['Charges and commission', self::PLAIN, 'Platform charge in rupees added to every ride. 0 = none.'],
            'secured_fee' => ['Charges and commission', self::PLAIN, 'Secured fee in rupees added to every ride. 0 = none.'],
            'admin_cancellation_earning' => ['Charges and commission', self::PLAIN, 'What the company keeps, in rupees, from a cancellation charge.'],
            'driver_joining_fee' => ['Driver settings', self::PLAIN, 'What a new partner pays to join, in rupees. 0 = free (the joining fee step disappears from the onboarding checklist).'],
            'enforce_driver_onboarding' => ['Driver settings', self::PLAIN, 'On: only approved partners who finished every step (and paid the joining fee, if any) can go online and get ride offers. Approve the existing partners before switching it on.'],
            'driver_min_wallet_bal' => ['Driver settings', self::PLAIN, 'The wallet balance, in rupees, a partner must have to receive rides. 0 = no minimum.'],
            'driver_referral_earning' => ['Driver settings', self::PLAIN, 'Reward, in rupees, a partner earns when a partner he referred is approved.'],
            'driver_cancellation_charge' => ['Driver settings', self::PLAIN, 'Charge, in rupees, when a partner cancels a ride he accepted.'],
            'user_referral_earning' => ['Customer settings', self::PLAIN, 'Reward, in rupees, a customer earns when a customer he referred completes a ride.'],
            'user_cancellation_charge' => ['Customer settings', self::PLAIN, 'Charge, in rupees, when a customer cancels after a driver has accepted the ride.'],
            'user_waiting_time' => ['Customer settings', self::PLAIN, 'How many minutes the driver waits for the customer at the pickup point before the ride can be cancelled.'],
            'notification_period' => ['Customer settings', self::PLAIN, 'How often, in minutes, drivers are notified again about a booking nobody has accepted yet.'],
            'min_withdraw' => ['Charges and commission', self::PLAIN, 'The smallest amount, in rupees, a partner can withdraw from the wallet.'],
            'mapbox_access_token' => ['Maps and routes', self::SECRET, 'Mapbox account > Tokens: a secret access token (starts with sk. for a secret token or pk. for a public one) that may use the Geocoding and Directions APIs. Optional: only if you use Mapbox.'],
            'maps_provider_order' => ['Maps and routes', self::PLAIN, 'Which map service to ask first, then second ..., separated by commas: google, mapbox, openstreetmap. Empty = google,mapbox,openstreetmap. A service with no key, or one that refuses, is skipped for the next. OpenStreetMap is free but only finds addresses (no road distances).'],
            'app_google_map_api' => ['Google Maps', self::PRIVATE_ID, 'The key the mobile apps use for their maps (Google Cloud > Credentials). Restrict it to the app package names / bundle ids. It is shipped inside the apps, so it is not a secret, but keep it to yourself.'],
            'open_money_key' => ['OpenMoney (payouts)', self::PRIVATE_ID, 'OpenMoney dashboard > Developers: the API key.'],
            'open_money_secret' => ['OpenMoney (payouts)', self::SECRET, 'OpenMoney dashboard > Developers: the secret that goes with the key.'],
            // Company UPI
            'company_upi_id' => ['Company UPI QR (when the payment gateway is down)', self::PLAIN, 'Your UPI id, for example name@bank. Partners see it as a QR to pay when Razorpay does not work. Empty = switched off.'],
            'company_upi_name' => ['Company UPI QR (when the payment gateway is down)', self::PLAIN, 'The name shown with the QR.'],
            // Notifications
            'onesignal_api_key' => ['Push notifications (OneSignal)', self::SECRET, 'OneSignal dashboard > Settings > Keys & IDs: the REST API Key.'],
            'onesignal_app_id' => ['Push notifications (OneSignal)', self::PRIVATE_ID, 'OneSignal dashboard > Settings > Keys & IDs: the App ID.'],
        ];
    }

    // [doc:start] label - The name of a setting for messages.
    public static function label($key)
    {
        $names = [
            'default_admin_commission_raider' => 'Default admin commission', 'gst' => 'GST', 'platform_charges' => 'Platform charges',
            'secured_fee' => 'Secured fee', 'admin_cancellation_earning' => 'Admin cancellation earning', 'min_withdraw' => 'Minimum withdrawal', 'driver_joining_fee' => 'Joining fee', 'user_referral_earning' => 'Referral earning', 'user_cancellation_charge' => 'Cancellation charge', 'user_waiting_time' => 'Waiting time', 'notification_period' => 'Notification period', 'driver_min_wallet_bal' => 'Minimum wallet balance', 'driver_referral_earning' => 'Referral earning', 'driver_cancellation_charge' => 'Cancellation charge',
        ];

        return $names[$key] ?? $key;
    }
    // [doc:end] label

    // [doc:start] level - How a setting is treated: secret (protected), private (an identifier) or plain.
    public static function level($key)
    {
        return self::registry()[$key][1] ?? self::PLAIN;
    }
    // [doc:end] level

    // [doc:start] isSecret - True for a protected setting (saved encrypted, never shown again).
    public static function isSecret($key)
    {
        return self::level($key) === self::SECRET;
    }
    // [doc:end] isSecret

    // [doc:start] secretKeys - The keys of all protected settings.
    public static function secretKeys()
    {
        return array_keys(array_filter(self::registry(), function ($r) {
            return $r[1] === self::SECRET;
        }));
    }
    // [doc:end] secretKeys

    // [doc:start] group - The heading a setting is listed under.
    public static function group($key)
    {
        return self::registry()[$key][0] ?? 'Other';
    }
    // [doc:end] group

    // [doc:start] help - Where to find the value / what to put in the box.
    public static function help($key)
    {
        return self::registry()[$key][2] ?? '';
    }
    // [doc:end] help

    // [doc:start] badge - The small label next to a box: PROTECTED, PRIVATE or VISIBLE, with the saved state for protected ones.
    /**
     * @param string $key   the setting key
     * @param bool   $isSet whether a value is saved
     */
    public static function badge($key, $isSet)
    {
        switch (self::level($key)) {
            case self::SECRET:
                $out = '<span class="badge text-bg-danger" title="Saved encrypted. Hidden after saving, never shown again, never sent to an app.">&#128274; Protected</span>';
                $out .= $isSet ? ' <span class="badge text-bg-success">saved</span>' : ' <span class="badge text-bg-warning">not set yet</span>';
                break;
            case self::PRIVATE_ID:
                $out = '<span class="badge text-bg-warning" title="Not a password, but keep it to yourself.">Private</span>';
                break;
            default:
                $out = '<span class="badge text-bg-secondary">Visible</span>';
        }

        return $out;
    }
    // [doc:end] badge

    // [doc:start] note - The line under a box: the help text and, for a protected one, what protected means.
    public static function note($key)
    {
        $note = Html::encode(self::help($key));
        if (self::isSecret($key)) {
            $note .= ' <strong>Protected:</strong> saved encrypted, hidden after saving and never shown again. Leave the box empty to keep the saved value, type a new one to replace it.';
        }

        return $note;
    }
    // [doc:end] note
}
