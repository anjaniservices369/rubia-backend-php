<?php

namespace app\modules\geo\services;

use Yii;

/**
 * Google keys that are sent to the browser (Maps JavaScript API on the admin pages).
 *
 * Browser key, first one found: GOOGLE_MAPS_BROWSER_KEY environment variable, config/secrets.php
 * ['googleMapsBrowserKey'], then the server key (['googleMapsKey']). A browser key is visible in the page
 * source, so restrict it in Google Cloud to your admin site's HTTP referrer and to the Maps JavaScript API.
 */
class GoogleKeys
{
    // [doc:start] serverKeys - A Google project must have the right APIs switched on for a key to work (Routes API, Geocoding API). When the first key is refused.
    /**
     * A Google project must have the right APIs switched on for a key to work (Routes API, Geocoding API). When the first key is refused
     * (REQUEST_DENIED) the callers try the next one, so pasting a new key in the dashboard can never stop bookings that an older key still served.
     *
     * @return string[]
     */
    public static function serverKeys()
    {
        $keys = [\app\modules\admin\models\Integrations::value('google_maps_server_key'), \app\modules\admin\models\Integrations::env('GOOGLE_MAPS_SERVER_KEY')];
        $secrets = require Yii::getAlias('@app/config/secrets-loader.php');
        $keys[] = (string) ($secrets['googleMapsKey'] ?? '');
        try {
            $keys[] = (string) (new \app\modules\admin\models\WebSetting())->getSettingBykey('map_key');
        } catch (\Throwable $e) {
            // no settings table: the other keys decide
        }

        return array_values(array_unique(array_filter(array_map('trim', $keys), function ($k) {
            return $k !== '';
        })));
    }
    // [doc:end] serverKeys

    // [doc:start] serverKey - Server Key
    public static function serverKey()
    {
        return self::serverKeys()[0] ?? '';
    }
    // [doc:end] serverKey

    // [doc:start] browserKey - Browser Key
    public static function browserKey()
    {
        $key = \app\modules\admin\models\Integrations::value('google_maps_browser_key');
        if ($key === '') {
            $key = \app\modules\admin\models\Integrations::env('GOOGLE_MAPS_BROWSER_KEY');
        }
        if ($key === false || $key === '') {
            $secrets = require Yii::getAlias('@app/config/secrets-loader.php');
            $key = $secrets['googleMapsBrowserKey'] ?? ($secrets['googleMapsKey'] ?? '');
        }

        return (string) $key;
    }
    // [doc:end] browserKey
}