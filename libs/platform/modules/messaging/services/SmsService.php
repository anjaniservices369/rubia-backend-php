<?php

namespace app\modules\messaging\services;

use Yii;
use yii\base\Component;
use app\modules\admin\models\WebSetting;

/**
 * SMS and OTP delivery through 2factor.in. The API key is the `sms_api_key` web setting.
 *
 * OTP flow: sendOtp() returns the provider response (JSON string) whose "Details" field is the session id the
 * app posts back as `session_code`; verifyOtp() checks that session id with the code the user typed.
 */
class SmsService extends Component
{
    const BASE_URL = 'https://2factor.in/API/V1/';

    // [doc:start] sendText - Sends a transactional SMS.
    /**
     * Sends a transactional SMS.
     *
     * @return string|false provider response (JSON) or false on transport failure
     */
    public function sendText($contactNo, $message)
    {
        return $this->request(
            'POST',
            self::BASE_URL . $this->apiKey() . '/ADDON_SERVICES/SEND/TSMS',
            ['From' => 'TFCTOR', 'To' => '91' . $contactNo, 'Msg' => $message]
        );
    }
    // [doc:end] sendText

    // [doc:start] sendOtp - Sends a one-time password. Transport failures are logged and returned as a JSON error so callers can.
    /**
     * Sends a one-time password. Transport failures are logged and returned as a JSON error so callers can
     * always json_decode() the result.
     *
     * @return string JSON, {"Status":"Success","Details":"<session id>"} on success
     */
    public function sendOtp($contactNo)
    {
        $otp = random_int(1000, 9999);
        $url = self::BASE_URL . $this->apiKey() . '/SMS/91' . $contactNo . '/' . $otp . '/OTP%20Verfication';

        $response = $this->request('GET', $url);
        if ($response === false) {
            return json_encode(['Status' => 'Error', 'Details' => 'SMS gateway unreachable']);
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded) || ($decoded['Status'] ?? '') !== 'Success') {
            Yii::error('sendOtp failed for ' . $this->mask($contactNo) . ': ' . $response, 'otp');
        }

        return $response;
    }
    // [doc:end] sendOtp

    // [doc:start] resendOtp - Resending an OTP is a fresh send; the new session id replaces the previous one.
    /**
     * Resending an OTP is a fresh send; the new session id replaces the previous one.
     */
    public function resendOtp($contactNo)
    {
        return $this->sendOtp($contactNo);
    }
    // [doc:end] resendOtp

    // [doc:start] verifyOtp - Verify Otp
    /**
     * @param string $sessionCode session id returned by sendOtp()
     * @param string $otpCode     code typed by the user
     * @return string|false provider response (JSON); Status is "Success" when the code matches
     */
    public function verifyOtp($sessionCode, $otpCode)
    {
        return $this->request(
            'GET',
            self::BASE_URL . $this->apiKey() . '/SMS/VERIFY/' . rawurlencode($sessionCode) . '/' . rawurlencode($otpCode)
        );
    }
    // [doc:end] verifyOtp

    private function apiKey()
    {
        return \app\modules\admin\models\Integrations::value('sms_api_key');
    }

    private function mask($contactNo)
    {
        $contactNo = (string) $contactNo;

        return substr($contactNo, 0, 2) . '******' . substr($contactNo, -2);
    }

    private function request($method, $url, array $fields = [])
    {
        $curl = curl_init($url);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
        ];
        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = $fields;
        }
        curl_setopt_array($curl, $options);

        $response = curl_exec($curl);
        if ($response === false) {
            Yii::error('SMS request failed: ' . curl_error($curl), 'otp');
        }
        curl_close($curl);

        return $response;
    }
}