<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Push notification diagnostics.
 *
 *   php yii push/check
 *
 * Tells whether the OneSignal App ID and REST API key (config/secrets.php or environment variables) are
 * present and accepted by OneSignal. It sends a request for a subscription id that does not exist, so
 * no notification is delivered to anyone.
 */
class PushController extends Controller
{
    // [doc:start] actionCheck - Check
    public function actionCheck()
    {
        $secrets = require Yii::getAlias('@app/config/secrets-loader.php');
        // Same resolution as the sender: environment / secrets first, then the dashboard settings.
        $credentials = \app\modules\messaging\services\PushNotifier::credentials();
        $appId = $credentials['appId'];
        $apiKey = $credentials['apiKey'];
        $explicit = getenv('PUSH_DRIVER') ?: ($secrets['push']['driver'] ?? '');
        $driver = $explicit !== '' ? $explicit : ($apiKey !== '' ? 'onesignal' : 'none');

        $this->stdout("push driver : $driver\n");
        $this->stdout('app id      : ' . ($appId !== '' ? $appId : '(not set)') . "\n");
        $this->stdout('REST API key: ' . ($apiKey !== '' ? substr($apiKey, 0, 6) . '...' . substr($apiKey, -3) . ' (' . strlen($apiKey) . " characters)\n" : "(not set)\n"));

        if ($driver !== 'onesignal') {
            $this->stdout("\nPUSH_DRIVER is not 'onesignal': no push is sent.\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
        if ($appId === '' || $apiKey === '') {
            $this->stdout("\nPaste the REST API key in the dashboard: Web Settings > Notification > OneSignal REST API Key.\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $ch = curl_init('https://api.onesignal.com/notifications?c=push');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'app_id' => $appId,
                'target_channel' => 'push',
                'include_subscription_ids' => ['00000000-0000-0000-0000-000000000000'],
                'contents' => ['en' => 'connection check'],
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Key ' . $apiKey],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $reply = json_decode((string) $body, true);
        $errors = json_encode($reply['errors'] ?? $reply);
        $this->stdout("\nOneSignal answered HTTP $status\n");

        if ($status === 401 || $status === 403) {
            $this->stdout("RESULT: the REST API key was REJECTED (wrong key, or a key of another app).\n  $errors\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
        if ($status === 404 || (is_string($errors) && stripos($errors, 'app_id') !== false && stripos($errors, 'not') !== false)) {
            $this->stdout("RESULT: the App ID was not found.\n  $errors\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("RESULT: the key and App ID are accepted. (No notification was delivered: the test id is not a device.)\n  $errors\n");

        return ExitCode::OK;
    }
    // [doc:end] actionCheck
}
