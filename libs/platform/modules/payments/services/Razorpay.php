<?php

namespace app\modules\payments\services;

use app\modules\admin\models\WebSetting;
use Yii;

/**
 * Razorpay client: create an order, fetch/capture a payment, and check the signatures Razorpay sends back.
 *
 * The keys are not in code. They are pasted in the dashboard (Payments screen, stored as the settings razorpay_key_id,
 * razorpay_key_secret, razorpay_webhook_secret) or, as the alternative, set as the environment variables RAZORPAY_KEY_ID,
 * RAZORPAY_KEY_SECRET and RAZORPAY_WEBHOOK_SECRET on the server. The dashboard value is used when it is filled in.
 * RAZORPAY_API_BASE can point the client to another address (used by the local test double).
 */
class Razorpay
{
    // [doc:start] setting - The value of a key: the dashboard setting first, then the server environment variable.
    /**
     * The value of a key: the dashboard setting first, then the server environment variable.
     *
     * @param string $name key_id | key_secret | webhook_secret
     */
    public static function setting($name)
    {
        $value = '';
        try {
            $value = (string) (new WebSetting())->getSettingBykey('razorpay_' . $name);
        } catch (\Throwable $e) {
            $value = '';
        }
        if (trim($value) === '') {
            $value = (string) \app\modules\admin\models\Integrations::env('RAZORPAY_' . $name);
        }

        return trim($value);
    }
    // [doc:end] setting

    // [doc:start] isConfigured - Is Configured
    public static function isConfigured()
    {
        return self::setting('key_id') !== '' && self::setting('key_secret') !== '';
    }
    // [doc:end] isConfigured

    // [doc:start] mode - Mode
    public static function mode()
    {
        $id = self::setting('key_id');
        if (strpos($id, 'rzp_live_') === 0) {
            return 'live';
        }

        return $id === '' ? '' : 'test';
    }
    // [doc:end] mode

    private static function base()
    {
        $b = getenv('RAZORPAY_API_BASE');

        return rtrim($b ?: 'https://api.razorpay.com/v1', '/');
    }

    // [doc:start] request - One call to the Razorpay API with the key id and secret.
    /**
     * One call to the Razorpay API with the key id and secret.
     *
     * @return array the decoded JSON reply
     * @throws \RuntimeException when the keys are missing, the gateway is unreachable, or it answers with an error
     */
    public static function request($method, $path, array $body = null)
    {
        if (!self::isConfigured()) {
            throw new \RuntimeException('The payment gateway is not set up yet.');
        }
        $ch = curl_init(self::base() . $path);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_USERPWD => self::setting('key_id') . ':' . self::setting('key_secret'),
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ];
        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($body);
        }
        curl_setopt_array($ch, $opts);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false) {
            Yii::warning('Razorpay unreachable: ' . $err, 'payments');
            throw new \RuntimeException('The payment gateway could not be reached. Please try again.');
        }
        $json = json_decode($raw, true);
        if ($code >= 400 || !is_array($json)) {
            $msg = is_array($json) && isset($json['error']['description']) ? $json['error']['description'] : 'Payment gateway error';
            Yii::warning('Razorpay ' . $method . ' ' . $path . ' -> ' . $code . ' ' . $msg, 'payments');
            throw new \RuntimeException($msg);
        }

        return $json;
    }
    // [doc:end] request

    // [doc:start] createOrder - Create Order
    public static function createOrder($amountRupees, $receipt, array $notes = [])
    {
        return self::request('POST', '/orders', [
            'amount' => (int) round($amountRupees * 100),
            'currency' => 'INR',
            'receipt' => substr((string) $receipt, 0, 40),
            'notes' => $notes,
        ]);
    }
    // [doc:end] createOrder

    // [doc:start] fetchPayment - Fetch Payment
    public static function fetchPayment($paymentId)
    {
        return self::request('GET', '/payments/' . rawurlencode($paymentId));
    }
    // [doc:end] fetchPayment

    // [doc:start] capturePayment - Capture Payment
    public static function capturePayment($paymentId, $amountPaise)
    {
        return self::request('POST', '/payments/' . rawurlencode($paymentId) . '/capture', ['amount' => (int) $amountPaise, 'currency' => 'INR']);
    }
    // [doc:end] capturePayment

    // [doc:start] createQr - A single-use UPI QR for a fixed amount that expires after two hours. Razorpay must have switched the "UPI QR codes" feature on.
    /**
     * A single-use UPI QR for a fixed amount that expires after two hours. Razorpay must have switched the "UPI QR codes" feature on
     * for the account (on request to Razorpay support); otherwise the call is refused and the caller falls back to a manual payment.
     *
     * @return array the QR: id, image_url, status, payment_amount, close_by
     */
    public static function createQr($amountRupees, $name, array $notes = [])
    {
        return self::request('POST', '/payments/qr_codes', [
            'type' => 'upi_qr',
            'name' => substr((string) $name, 0, 40),
            'usage' => 'single_use',
            'fixed_amount' => true,
            'payment_amount' => (int) round($amountRupees * 100),
            'description' => substr((string) $name, 0, 100),
            'close_by' => time() + 7200 - 30,
            'notes' => $notes,
        ]);
    }
    // [doc:end] createQr

    // [doc:start] fetchQrPayments - Fetch Qr Payments
    public static function fetchQrPayments($qrId)
    {
        return self::request('GET', '/payments/qr_codes/' . rawurlencode($qrId) . '/payments?count=10');
    }
    // [doc:end] fetchQrPayments

    // [doc:start] closeQr - Close Qr
    public static function closeQr($qrId)
    {
        return self::request('POST', '/payments/qr_codes/' . rawurlencode($qrId) . '/close', []);
    }
    // [doc:end] closeQr

    // [doc:start] verifyCheckoutSignature - Verify Checkout Signature
    public static function verifyCheckoutSignature($orderId, $paymentId, $signature)
    {
        $secret = self::setting('key_secret');
        if ($secret === '' || $orderId === '' || $paymentId === '' || $signature === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $orderId . '|' . $paymentId, $secret), (string) $signature);
    }
    // [doc:end] verifyCheckoutSignature

    // [doc:start] verifyWebhookSignature - Verify Webhook Signature
    public static function verifyWebhookSignature($rawBody, $signature)
    {
        $secret = self::setting('webhook_secret');
        if ($secret === '' || $signature === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $rawBody, $secret), (string) $signature);
    }
    // [doc:end] verifyWebhookSignature
}
