<?php

namespace app\components;

use Yii;

/**
 * Small per-IP counter backed by the app cache, used to rate-limit things an anonymous visitor can trigger
 * repeatedly (failed logins, password-reset requests). Each caller picks its own $bucket name and limits, so
 * a lockout in one bucket never affects another.
 */
class IpAttemptThrottle
{
    public static function isThrottled(string $bucket, int $max)
    {
        return self::count($bucket) >= $max;
    }

    public static function registerAttempt(string $bucket, int $windowSeconds)
    {
        $key = self::cacheKey($bucket);
        $count = (int) (Yii::$app->cache->get($key) ?: 0);
        Yii::$app->cache->set($key, $count + 1, $windowSeconds);
    }

    public static function clear(string $bucket)
    {
        Yii::$app->cache->delete(self::cacheKey($bucket));
    }

    private static function count(string $bucket)
    {
        return (int) (Yii::$app->cache->get(self::cacheKey($bucket)) ?: 0);
    }

    private static function cacheKey(string $bucket)
    {
        $ip = Yii::$app->request->userIP ?: 'unknown';
        return "throttle_{$bucket}_{$ip}";
    }
}
