<?php

namespace app\modules\auth\services;

use Yii;
use yii\base\Component;

/**
 * Request-level OTP helper shared by the customer and partner APIs, so each controller no longer repeats
 * the send/resend handling. The login/registration step that follows verification is still role specific
 * and stays in the controllers.
 */
class OtpService extends Component
{
    const OK = 'OK';
    const NOK = 'NOK';

    // [doc:start] send - Send
    /**
     * @param array|null $post request body (expects contact_no)
     * @return array API payload fragment: [status, details|error]
     */
    public function send($post)
    {
        if (empty($post) || empty($post['contact_no'])) {
            return ['status' => self::NOK, 'error' => Yii::t('app', 'No data posted')];
        }

        // DEV-ONLY TEST LOGIN: no SMS is sent; the tester types the configured test code (see isBypassed()).
        if ($this->isBypassed($post['contact_no'])) {
            Yii::warning('DEV TEST LOGIN: OTP send skipped for ' . $this->mask($post['contact_no']), 'otp');
            return ['status' => self::OK, 'details' => [
                'Status' => 'Success',
                'Details' => 'dev-test-login-session',
                'dev_test_login' => true,
            ]];
        }

        $response = json_decode(Yii::$app->sms->sendOtp($post['contact_no']), true);
        if (is_array($response) && ($response['Status'] ?? '') === 'Success') {
            return ['status' => self::OK, 'details' => $response];
        }

        return ['status' => self::NOK, 'error' => Yii::t('app', 'OTP failed')];
    }
    // [doc:end] send

    // [doc:start] resend - A resend is a new send; the app must use the session id of the latest response.
    /**
     * A resend is a new send; the app must use the session id of the latest response.
     */
    public function resend($post)
    {
        return $this->send($post);
    }
    // [doc:end] resend

    // [doc:start] verify - Check the OTP typed by the user. Returns the provider-style array whose ['Status'] is 'Success' when right.
    /**
     * Check the OTP typed by the user. Returns the provider-style array whose ['Status'] is 'Success' when right.
     * For a DEV-ONLY test number (see isBypassed()) the provider is not called: the code must equal the configured
     * test code. Every other number goes to the SMS provider exactly as before.
     */
    public function verify($contactNo, $sessionCode, $otpCode)
    {
        if ($this->isBypassed($contactNo)) {
            $ok = hash_equals($this->testCode(), (string) $otpCode);
            Yii::warning('DEV TEST LOGIN: ' . ($ok ? 'accepted' : 'REJECTED wrong code') . ' for ' . $this->mask($contactNo), 'otp');
            return ['Status' => $ok ? 'Success' : 'Error', 'Details' => $ok ? 'dev-test-login' : 'Wrong test code'];
        }

        $result = json_decode(Yii::$app->sms->verifyOtp($sessionCode, $otpCode), true);
        return is_array($result) ? $result : ['Status' => 'Error'];
    }
    // [doc:end] verify

    // [doc:start] isBypassed - DEV-ONLY TEST LOGIN. True only when ALL of these hold (otherwise false, whatever else is configured):.
    /**
     * DEV-ONLY TEST LOGIN. True only when ALL of these hold (otherwise false, whatever else is configured):
     *  1. the server runs in development mode (YII_ENV=dev) - never in production;
     *  2. the request comes from this machine itself (127.0.0.1 / ::1, e.g. the phone through adb reverse);
     *  3. the phone number is on the allow-list: OTP_TEST_NUMBERS (comma separated) or config/secrets.php
     *     ['otpTestNumbers'];
     *  4. a test code is configured: OTP_TEST_CODE or config/secrets.php ['otpTestCode'] (no built-in default).
     */
    public function isBypassed($contactNo)
    {
        if (!YII_ENV_DEV) {
            return false;
        }
        if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
            return false;
        }
        if ($this->testCode() === '') {
            return false;
        }

        $configured = getenv('OTP_TEST_NUMBERS');
        if ($configured === false || $configured === '') {
            $secrets = require Yii::getAlias('@app/config/secrets-loader.php');
            $configured = $secrets['otpTestNumbers'] ?? [];
        }
        $numbers = is_array($configured) ? $configured : explode(',', (string) $configured);
        $numbers = array_filter(array_map('trim', array_map('strval', $numbers)));

        return in_array((string) $contactNo, $numbers, true);
    }
    // [doc:end] isBypassed

    /** The configured test code, or '' when none is configured (which keeps the test login switched off). */
    private function testCode()
    {
        $code = getenv('OTP_TEST_CODE');
        if ($code === false || $code === '') {
            $secrets = require Yii::getAlias('@app/config/secrets-loader.php');
            $code = $secrets['otpTestCode'] ?? '';
        }
        return trim((string) $code);
    }

    /** Phone number with the middle hidden, for logs. */
    private function mask($contactNo)
    {
        $n = (string) $contactNo;
        return strlen($n) > 4 ? substr($n, 0, 2) . str_repeat('*', strlen($n) - 4) . substr($n, -2) : '****';
    }
}
