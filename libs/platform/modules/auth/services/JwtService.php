<?php

namespace app\modules\auth\services;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Psr\Clock\ClockInterface;
use Yii;

/**
 * Issues and verifies the signed (HS256) JWTs used as the API "auth_code".
 *
 * Secret lookup order: JWT_SECRET env var, params['jwt']['secret'], config/jwt-secret.php.
 * Optional params['jwt']: ttl (seconds, default 90 days), allowLegacy (accept pre-JWT random codes).
 */
class JwtService
{
    const DEFAULT_TTL = 7776000; // 90 days, same as the app's login duration.

    private static $secret;
    private static $config;

    // [doc:start] issue - Issue
    public static function issue($userId, $role = '')
    {
        $now = new DateTimeImmutable();
        $builder = self::config()->builder()
            ->relatedTo((string) $userId)
            ->identifiedBy(bin2hex(random_bytes(8)))
            ->issuedAt($now)
            ->expiresAt($now->modify('+' . self::ttl() . ' seconds'));
        if ($role !== '' && $role !== null) {
            $builder = $builder->withClaim('r', (string) $role);
        }

        return $builder->getToken(self::config()->signer(), self::config()->signingKey())->toString();
    }
    // [doc:end] issue

    // [doc:start] verify - Verify
    /**
     * @return array|null claims when the signature and expiry are valid, otherwise null.
     */
    public static function verify($token)
    {
        if (!self::looksLikeJwt($token)) {
            return null;
        }
        $config = self::config();
        try {
            $parsed = $config->parser()->parse($token);
            if (!$parsed instanceof UnencryptedToken) {
                return null;
            }
                // [doc:start] now - Now
            $clock = new class implements ClockInterface {
                public function now(): DateTimeImmutable
                {
                    return new DateTimeImmutable('now', new DateTimeZone('UTC'));
                // [doc:end] now
                }
            };
            $valid = $config->validator()->validate(
                $parsed,
                new SignedWith($config->signer(), $config->verificationKey()),
                new LooseValidAt($clock, new DateInterval('PT60S'))
            );

            return $valid ? $parsed->claims()->all() : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
    // [doc:end] verify

    // [doc:start] looksLikeJwt - Looks Like Jwt
    public static function looksLikeJwt($token)
    {
        return is_string($token)
            && preg_match('/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/', $token) === 1;
    }
    // [doc:end] looksLikeJwt

    // [doc:start] isAcceptable - Whether an incoming auth_code may be used: a valid JWT, or (only when enabled) a legacy random code.
    /**
     * Whether an incoming auth_code may be used: a valid JWT, or (only when enabled) a legacy random code.
     */
    public static function isAcceptable($token)
    {
        if (self::looksLikeJwt($token)) {
            return self::verify($token) !== null;
        }

        return self::allowLegacy();
    }
    // [doc:end] isAcceptable

    // [doc:start] allowLegacy - Allow Legacy
    public static function allowLegacy()
    {
        return !empty(Yii::$app->params['jwt']['allowLegacy']);
    }
    // [doc:end] allowLegacy

    // [doc:start] ttl - Ttl
    public static function ttl()
    {
        $ttl = (int) (Yii::$app->params['jwt']['ttl'] ?? 0);

        return $ttl > 0 ? $ttl : self::DEFAULT_TTL;
    }
    // [doc:end] ttl

    private static function config()
    {
        if (self::$config === null) {
            self::$config = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText(self::secret()));
        }

        return self::$config;
    }

    private static function secret()
    {
        if (self::$secret !== null) {
            return self::$secret;
        }

        $secret = getenv('JWT_SECRET') ?: (Yii::$app->params['jwt']['secret'] ?? '');
        if ($secret === '') {
            $file = Yii::getAlias('@app/config/jwt-secret.php');
            if (is_file($file)) {
                $secret = (string) require $file;
            }
        }
        if (strlen($secret) < 32) {
            throw new \RuntimeException('JWT secret is missing or shorter than 32 characters (set JWT_SECRET or config/jwt-secret.php).');
        }

        return self::$secret = $secret;
    }
}