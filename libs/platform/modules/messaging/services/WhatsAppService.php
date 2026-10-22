<?php

namespace app\modules\messaging\services;

use Yii;
use yii\base\Component;
use app\modules\admin\models\WebSetting;

/**
 * WhatsApp template messages through Interakt. The credential is the `interakt_key` web setting
 * (the base64 value used after "Basic "), or the INTERAKT_KEY environment variable.
 */
class WhatsAppService extends Component
{
    const ENDPOINT = 'https://api.interakt.ai/v1/public/message/';

    // [doc:start] sendTemplate - Send Template
    /**
     * @return bool true when Interakt accepted the message
     */
    public function sendTemplate($contactNo, $templateName, $param1 = '')
    {
        $key = getenv('INTERAKT_KEY') ?: (string) (new WebSetting())->getSettingBykey('interakt_key');
        if ($key === '') {
            Yii::warning('WhatsApp message skipped: no Interakt key configured', 'push');
            return false;
        }

        $payload = [
            'countryCode' => '+91',
            'phoneNumber' => (string) $contactNo,
            'callbackData' => 'rubia',
            'type' => 'Template',
            'template' => [
                'name' => (string) $templateName,
                'languageCode' => 'en',
                'bodyValues' => [(string) $param1],
            ],
        ];

        $curl = curl_init(self::ENDPOINT);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Authorization: Basic ' . $key, 'Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
        ]);
        $response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($response === false || $status >= 300) {
            Yii::error('WhatsApp send failed (HTTP ' . $status . '): ' . (string) $response, 'push');
            return false;
        }

        return true;
    }
    // [doc:end] sendTemplate
}