<?php

namespace app\modules\filestorage\services;

use app\modules\admin\models\WebSetting;

/**
 * A small S3-compatible object storage client (AWS Signature V4, path-style addresses, no SDK needed).
 * Works with Cloudflare R2, Backblaze B2, DigitalOcean Spaces, AWS S3, Wasabi and MinIO: the provider is only configuration.
 *
 * Settings: pasted in Integrations or set as environment variables (the key name in capitals):
 *   media_s3_endpoint     e.g. https://<account>.r2.cloudflarestorage.com  (no bucket name in it)
 *   media_s3_bucket       the bucket name (ride photos and everything else)
 *   media_s3_bucket_documents  a second bucket for the files kept for life: identity documents and profile photos (empty = same bucket)
 *   media_s3_region       the provider's region; empty means "auto" for Cloudflare R2 and us-east-1 for everything else
 *   media_s3_key_id       access key id
 *   media_s3_key_secret   secret access key (never kept in code, never shown again in the dashboard)
 *   media_s3_documents_key_id / media_s3_documents_key_secret   an own key for the documents bucket (empty = the main key is used)
 */
class S3Client
{
    // [doc:start] setting - Setting
    public static function setting($key, $default = '')
    {
        // the dashboard value (Integrations) first, then the environment
        $value = trim((string) \app\modules\admin\models\Integrations::value($key));

        return $value === '' ? $default : $value;
    }
    // [doc:end] setting

    // [doc:start] isConfigured - Is Configured
    public static function isConfigured()
    {
        foreach (['media_s3_endpoint', 'media_s3_bucket', 'media_s3_key_id', 'media_s3_key_secret'] as $k) {
            if (self::setting($k) === '') {
                return false;
            }
        }

        return true;
    }
    // [doc:end] isConfigured

    private static function host()
    {
        return parse_url(self::setting('media_s3_endpoint'), PHP_URL_HOST) . ((($p = parse_url(self::setting('media_s3_endpoint'), PHP_URL_PORT)) ? ':' . $p : ''));
    }

    private static function path($key, $bucket = null)
    {
        return '/' . rawurlencode($bucket ?: self::setting('media_s3_bucket')) . '/' . implode('/', array_map('rawurlencode', explode('/', $key)));
    }

    private static function region()
    {
        // Cloudflare R2 has one region, called "auto"; every other provider is asked for its own region name (default us-east-1)
        $default = str_ends_with((string) parse_url(self::setting('media_s3_endpoint'), PHP_URL_HOST), '.r2.cloudflarestorage.com') ? 'auto' : 'us-east-1';

        return self::setting('media_s3_region', $default);
    }

    /**
     * The key id and secret to use for a bucket: the documents bucket has its own key when one is set (so a key that can reach the
     * documents is not the key used for ride photos), every other bucket uses the main key.
     *
     * @return array [key id, secret]
     */
    private static function creds($bucket = null)
    {
        $docs = self::setting('media_s3_bucket_documents');
        if ($bucket !== null && $docs !== '' && $bucket === $docs
            && self::setting('media_s3_documents_key_id') !== '' && self::setting('media_s3_documents_key_secret') !== '') {
            return [self::setting('media_s3_documents_key_id'), self::setting('media_s3_documents_key_secret')];
        }

        return [self::setting('media_s3_key_id'), self::setting('media_s3_key_secret')];
    }

    private static function signingKey($date, $secret)
    {
        $k = hash_hmac('sha256', $date, 'AWS4' . $secret, true);
        $k = hash_hmac('sha256', self::region(), $k, true);
        $k = hash_hmac('sha256', 's3', $k, true);

        return hash_hmac('sha256', 'aws4_request', $k, true);
    }

    // [doc:start] request - Request
    /**
     * @return array [int status, string body]
     */
    public static function request($method, $key, $body = '', array $headers = [], $query = '', $bucket = null)
    {
        $now = gmdate('Ymd\THis\Z');
        $date = substr($now, 0, 8);
        $payloadHash = hash('sha256', $body);
        $host = self::host();
        $path = $key === '' ? '/' . rawurlencode($bucket ?: self::setting('media_s3_bucket')) : self::path($key, $bucket);

        $signed = array_change_key_case($headers, CASE_LOWER) + ['host' => $host, 'x-amz-content-sha256' => $payloadHash, 'x-amz-date' => $now];
        ksort($signed);
        $canonicalHeaders = '';
        foreach ($signed as $k => $v) {
            $canonicalHeaders .= $k . ':' . trim($v) . "\n";
        }
        $signedNames = implode(';', array_keys($signed));
        $canonical = implode("\n", [$method, $path, $query, $canonicalHeaders, $signedNames, $payloadHash]);
        $scope = $date . '/' . self::region() . '/s3/aws4_request';
        $toSign = implode("\n", ['AWS4-HMAC-SHA256', $now, $scope, hash('sha256', $canonical)]);
        [$keyId, $secret] = self::creds($bucket ?: self::setting('media_s3_bucket'));
        $signature = hash_hmac('sha256', $toSign, self::signingKey($date, $secret));
        $auth = 'AWS4-HMAC-SHA256 Credential=' . $keyId . '/' . $scope . ', SignedHeaders=' . $signedNames . ', Signature=' . $signature;

        $curlHeaders = ['Authorization: ' . $auth];
        foreach ($signed as $k => $v) {
            if ($k !== 'host') {
                $curlHeaders[] = $k . ': ' . $v;
            }
        }
        $ch = curl_init(rtrim(self::setting('media_s3_endpoint'), '/') . $path . ($query !== '' ? '?' . $query : ''));
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => $curlHeaders,
        ]);
        if ($body !== '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $out = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$status, $out === false ? '' : (string) $out];
    }
    // [doc:end] request

    // [doc:start] put - Put
    public static function put($key, $bytes, $contentType, $bucket = null)
    {
        [$status, $body] = self::request('PUT', $key, $bytes, ['content-type' => $contentType], '', $bucket);
        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException('Object storage refused the file (HTTP ' . $status . ').');
        }
    }
    // [doc:end] put

    // [doc:start] get - Get
    public static function get($key, $bucket = null)
    {
        [$status, $body] = self::request('GET', $key, '', [], '', $bucket);

        return $status === 200 ? $body : null;
    }
    // [doc:end] get

    // [doc:start] delete - Delete
    public static function delete($key, $bucket = null)
    {
        [$status] = self::request('DELETE', $key, '', [], '', $bucket);

        return $status === 204 || $status === 200 || $status === 404;
    }
    // [doc:end] delete

    // [doc:start] createBucket - Create Bucket
    public static function createBucket($bucket = null)
    {
        [$status] = self::request('PUT', '', '', [], '', $bucket);

        return $status === 200 || $status === 409;
    }
    // [doc:end] createBucket

    // [doc:start] presignedGet - Presigned Get
    /**
     * @param int $ttl seconds the link works (default 15 minutes)
     */
    public static function presignedGet($key, $ttl = 900, $bucket = null)
    {
        $now = gmdate('Ymd\THis\Z');
        $date = substr($now, 0, 8);
        $scope = $date . '/' . self::region() . '/s3/aws4_request';
        [$keyId, $secret] = self::creds($bucket ?: self::setting('media_s3_bucket'));
        $params = [
            'X-Amz-Algorithm' => 'AWS4-HMAC-SHA256',
            'X-Amz-Credential' => $keyId . '/' . $scope,
            'X-Amz-Date' => $now,
            'X-Amz-Expires' => (string) $ttl,
            'X-Amz-SignedHeaders' => 'host',
        ];
        ksort($params);
        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $path = self::path($key, $bucket);
        $canonical = implode("\n", ['GET', $path, $query, 'host:' . self::host() . "\n", 'host', 'UNSIGNED-PAYLOAD']);
        $toSign = implode("\n", ['AWS4-HMAC-SHA256', $now, $scope, hash('sha256', $canonical)]);
        $signature = hash_hmac('sha256', $toSign, self::signingKey($date, $secret));

        return rtrim(self::setting('media_s3_endpoint'), '/') . $path . '?' . $query . '&X-Amz-Signature=' . $signature;
    }
    // [doc:end] presignedGet
}
