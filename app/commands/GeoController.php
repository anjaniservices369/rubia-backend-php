<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Google key diagnostics.
 *
 *   php yii geo/check
 *
 * Tests every Google key this installation knows (server key, browser key, the `map_key` and
 * `app_google_map_api` web settings) against the APIs the backend uses, and prints the Google status.
 * Keys are shown masked.
 */
class GeoController extends Controller
{
    private static $apis = [
        'Directions' => 'https://maps.googleapis.com/maps/api/directions/json?origin=17.3616,78.4747&destination=17.4435,78.3772&key=%s',
        'Distance Matrix' => 'https://maps.googleapis.com/maps/api/distancematrix/json?origins=17.3616,78.4747&destinations=17.4435,78.3772&key=%s',
        'Geocoding' => 'https://maps.googleapis.com/maps/api/geocode/json?latlng=17.3616,78.4747&key=%s',
        'Places' => 'https://maps.googleapis.com/maps/api/place/autocomplete/json?input=charminar&key=%s',
    ];

    // [doc:start] actionPincodeArea - Draws a city's service area from its pincodes (see PincodeAreas).
    /**
     * Draws a city's service area from its pincodes (see PincodeAreas).
     *
     *   php yii geo/pincode-area --city=8            show what would be drawn (looks the pincodes up on Google once)
     *   php yii geo/pincode-area --city=8 --apply    save it as the city's service area (city.boundary)
     *   add --refresh to look the pincodes up on Google again
     */
    public function actionPincodeArea()
    {
        if (!$this->city) {
            $this->stderr("Give a city: --city=<id>
");

            return ExitCode::USAGE;
        }
        $r = \app\modules\geo\services\PincodeAreas::forCity((int) $this->city, (bool) $this->refresh);
        foreach ($r['pincodes'] as $p) {
            $this->stdout(sprintf("%-8s %-9s %s
", $p['pincode'], $p['found'] ? ($p['outlier'] ? 'OUTLIER' : 'ok') : ($p['asked'] ? 'not found' : 'not asked'), $p['address']));
        }
        $this->stdout(sprintf("found %d, missing %d, outliers %d, outline points %d
", $r['found'], $r['missing'], $r['outliers'], count($r['hull'])));
        if ($this->apply) {
            if (\app\modules\geo\services\PincodeAreas::saveBoundary((int) $this->city, $r['hull'])) {
                $this->stdout("saved as the service area of city {$this->city}
");
            } else {
                $this->stderr("nothing saved: fewer than 3 outline points (need at least one located pincode)
");

                return ExitCode::UNSPECIFIED_ERROR;
            }
        } else {
            $this->stdout("dry run - add --apply to save it
");
        }

        return ExitCode::OK;
    }
    // [doc:end] actionPincodeArea

    public $city;
    public $apply = false;
    public $refresh = false;

    // [doc:start] options - Options
    public function options($actionID)
    {
        return array_merge(parent::options($actionID), ['city', 'apply', 'refresh']);
    }
    // [doc:end] options

    // [doc:start] actionCheck - Check
    public function actionCheck()
    {
        $secrets = require Yii::getAlias('@app/config/secrets-loader.php');
        $named = [];
        $named['server key (GOOGLE_MAPS_SERVER_KEY / secrets googleMapsKey)'] = getenv('GOOGLE_MAPS_SERVER_KEY') ?: ($secrets['googleMapsKey'] ?? '');
        $named['browser key (GOOGLE_MAPS_BROWSER_KEY / secrets googleMapsBrowserKey)'] = getenv('GOOGLE_MAPS_BROWSER_KEY') ?: ($secrets['googleMapsBrowserKey'] ?? '');
        foreach (['map_key', 'app_google_map_api'] as $settingKey) {
            $value = Yii::$app->db->createCommand('SELECT value FROM web_setting WHERE setting_key = :k', [':k' => $settingKey])->queryScalar();
            $named["web setting $settingKey"] = $value ?: '';
        }

        $seen = [];
        foreach ($named as $label => $key) {
            $this->stdout("\n$label\n");
            if ($key === '' || $key === false) {
                $this->stdout("  (not set)\n");
                continue;
            }
            $this->stdout('  key ' . substr($key, 0, 8) . '...' . substr($key, -4) . "\n");
            if (isset($seen[$key])) {
                $this->stdout("  same key as above\n");
                continue;
            }
            $seen[$key] = true;
            foreach (self::$apis as $name => $url) {
                $this->stdout(sprintf("  %-16s %s\n", $name, $this->status(sprintf($url, rawurlencode($key)))));
            }
        }

        return ExitCode::OK;
    }
    // [doc:end] actionCheck

    private function status($url)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_CONNECTTIMEOUT => 10]);
        $body = curl_exec($ch);
        curl_close($ch);
        $reply = $body === false ? null : json_decode($body, true);
        if (!is_array($reply)) {
            return 'no answer';
        }
        $text = $reply['status'] ?? '?';
        if (!empty($reply['error_message'])) {
            $text .= ' - ' . substr($reply['error_message'], 0, 70);
        }

        return $text;
    }
}