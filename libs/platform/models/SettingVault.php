<?php

namespace app\modules\admin\models;

use Yii;

/**
 * Encryption at rest for the protected dashboard settings (API secrets, passwords, tokens; see SettingGuide).
 *
 * A protected value is saved in the database as "enc1:" + base64(iv + tag + AES-256-GCM ciphertext), so a copy of the database or a backup
 * does not give away the keys. The key is derived from the environment variable SETTINGS_ENCRYPTION_KEY (recommended: a long random text kept only on the server)
 * or, when that is not set, from the application's cookie validation key. If that key text is ever changed, saved protected values can no longer be read
 * (they read as empty) and must be pasted again in the dashboard.
 *
 * A value saved before encryption existed stays readable as it is; `php yii settings/protect --apply` encrypts them.
 */
class SettingVault
{
    const PREFIX = 'enc1:';

    private static function secretText()
    {
        $text = (string) getenv('SETTINGS_ENCRYPTION_KEY');
        if ($text === '') {
            $text = (string) getenv('COOKIE_VALIDATION_KEY');
        }
        if ($text === '') {
            try {
                $text = (string) ((require Yii::getAlias('@app/config/secrets-loader.php'))['cookieValidationKey'] ?? '');
            } catch (\Throwable $e) {
                $text = '';
            }
        }

        return $text;
    }

    // [doc:start] isAvailable - True when there is key text to encrypt with (and the openssl extension).
    public static function isAvailable()
    {
        return function_exists('openssl_encrypt') && self::secretText() !== '';
    }
    // [doc:end] isAvailable

    // [doc:start] isEncrypted - True for a value that was saved encrypted.
    public static function isEncrypted($value)
    {
        return is_string($value) && strncmp($value, self::PREFIX, strlen(self::PREFIX)) === 0;
    }
    // [doc:end] isEncrypted

    // [doc:start] encrypt - Encrypts a value (returns it unchanged when it is empty, already encrypted, or there is no key text).
    public static function encrypt($plain)
    {
        $plain = (string) $plain;
        if ($plain === '' || self::isEncrypted($plain) || !self::isAvailable()) {
            return $plain;
        }
        $key = hash('sha256', 'rubia-settings|' . self::secretText(), true);
        $iv = random_bytes(12);
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false) {
            return $plain;
        }

        return self::PREFIX . base64_encode($iv . $tag . $cipher);
    }
    // [doc:end] encrypt

    // [doc:start] decrypt - The plain value of a stored value (a value that was never encrypted is returned as it is; one that cannot be read gives an empty text).
    public static function decrypt($stored)
    {
        $stored = (string) $stored;
        if (!self::isEncrypted($stored)) {
            return $stored;
        }
        $raw = base64_decode(substr($stored, strlen(self::PREFIX)), true);
        if ($raw === false || strlen($raw) < 29 || !self::isAvailable()) {
            return '';
        }
        $key = hash('sha256', 'rubia-settings|' . self::secretText(), true);
        $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
        if ($plain === false) {
            Yii::warning('A protected setting could not be decrypted (the key text changed?): paste it again in the dashboard.', 'settings');

            return '';
        }

        return $plain;
    }
    // [doc:end] decrypt
}
