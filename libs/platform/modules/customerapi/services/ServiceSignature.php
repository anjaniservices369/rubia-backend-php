<?php

namespace app\modules\customerapi\services;

use Yii;

/**
 * Signs and verifies the calls between a service (Driver, Taxi, Auto, Bike) and the user-app system.
 *
 * The user app is a system of its own (own app, backend, authentication, notifications, database, dashboard). A
 * service never holds customer logins or customer records: it asks the user-app gateway each time, and the gateway
 * tells a service who a request comes from. Both directions are signed with a shared secret per service:
 *
 *   X-Service    the service key (driver, taxi, auto, bike)
 *   X-Timestamp  unix time; a call older or newer than 5 minutes is refused (replay)
 *   X-Signature  hex HMAC-SHA256 of "service|timestamp|method|path|body" with the service's secret
 *   X-User-Id    (gateway -> service only) the customer the request is made for
 *
 * Secrets: environment CUSTOMER_API_SECRET_<SERVICE> (e.g. CUSTOMER_API_SECRET_DRIVER), or config/secrets.php
 * ['customerApiSecrets' => ['driver' => '...']]. With no secret configured the gateway is closed.
 */
class ServiceSignature
{
    const WINDOW = 300;

    // [doc:start] secret - Secret
    public static function secret($service, $prefix = 'CUSTOMER_API')
    {
        $service = strtolower((string) $service);
        $v = getenv($prefix . '_SECRET_' . strtoupper($service));
        if ($v === false || $v === '') {
            $secrets = require Yii::getAlias('@app/config/secrets-loader.php');
            $v = $secrets[strtolower($prefix) === 'admin' ? 'adminSecrets' : 'customerApiSecrets'][$service] ?? '';
        }

        return (string) $v;
    }
    // [doc:end] secret

    // [doc:start] sign - Sign
    public static function sign($service, $secret, $method, $path, $body, $timestamp = null)
    {
        $timestamp = $timestamp ?: time();
        $mac = hash_hmac('sha256', implode('|', [$service, $timestamp, strtoupper($method), $path, $body]), $secret);

        return ['X-Service' => $service, 'X-Timestamp' => (string) $timestamp, 'X-Signature' => $mac];
    }
    // [doc:end] sign

    // [doc:start] verifyRequest - Checks the signature headers of the current request.
    /**
     * Checks the signature headers of the current request.
     *
     * @return string|null the service key the call is from, or null when it is not a valid signed call
     */
    public static function verifyRequest($prefix = 'CUSTOMER_API')
    {
        $h = Yii::$app->request->headers;
        $service = strtolower((string) $h->get('X-Service'));
        $ts = (int) $h->get('X-Timestamp');
        $sig = (string) $h->get('X-Signature');
        if ($service === '' || $sig === '' || abs(time() - $ts) > self::WINDOW) {
            return null;
        }
        $secret = self::secret($service, $prefix);
        if ($secret === '') {
            return null;
        }
        $path = Yii::$app->request->getPathInfo();
        $body = Yii::$app->request->getRawBody();
        $expected = hash_hmac('sha256', implode('|', [$service, $ts, strtoupper(Yii::$app->request->method), '/' . ltrim($path, '/'), $body]), $secret);

        return hash_equals($expected, $sig) ? $service : null;
    }
    // [doc:end] verifyRequest

    // [doc:start] forwardedUserId - The customer a gateway-forwarded request is for (a service endpoint called by the gateway), or null.
    /**
     * The customer a gateway-forwarded request is for (a service endpoint called by the gateway), or null.
     */
    public static function forwardedUserId()
    {
        $uid = Yii::$app->request->headers->get('X-User-Id');
        if ($uid === null || $uid === '' || !ctype_digit((string) $uid)) {
            return null;
        }

        return self::verifyRequest() !== null ? (int) $uid : null;
    }
    // [doc:end] forwardedUserId
}
