<?php

namespace app\modules\adminapi\services;

use app\modules\billing\services\ServiceReport;
use app\modules\customerapi\services\ServiceSignature;
use Yii;

/**
 * The super admin dashboard's view of the services.
 *
 * A service that still lives on this database is answered from it (ServiceReport). A service that runs separately is
 * asked over HTTPS, signed with ADMIN_SECRET_<SERVICE>:
 *
 *   ADMIN_URL_<SERVICE>          base address of the service's entry point (e.g. https://driver.example.com)
 *   ADMIN_SECRET_<SERVICE>       shared secret (see ServiceSignature)
 *   ADMIN_DASHBOARD_URL_<SERVICE> where that service's own dashboard is (optional, for the link)
 */
class ServiceClient
{
    // [doc:start] key - Service key (driver, taxi, auto, bike) of a module number.
    /** Service key (driver, taxi, auto, bike) of a module number. */
    public static function key($module)
    {
        foreach (require Yii::getAlias('@app/config/services.php') as $key => $s) {
            if ((int) $s['module'] === (int) $module) {
                return $key;
            }
        }

        return '';
    }
    // [doc:end] key

    // [doc:start] hasPartnerApp - Whether the service has a partner app in use (Taxi/car has none yet: config/services.php 'partnerApp').
    /** Whether the service has a partner app in use (Taxi/car has none yet: config/services.php 'partnerApp'). */
    public static function hasPartnerApp($module)
    {
        foreach (require Yii::getAlias('@app/config/services.php') as $s) {
            if ((int) $s['module'] === (int) $module) {
                return $s['partnerApp'] ?? true;
            }
        }

        return true;
    }
    // [doc:end] hasPartnerApp

    // [doc:start] isSeparate - Is Separate
    public static function isSeparate($module)
    {
        $k = strtoupper(self::key($module));
        $u = getenv('ADMIN_URL_' . $k);

        return $u !== false && $u !== '';
    }
    // [doc:end] isSeparate

    // [doc:start] dashboardUrl - Dashboard Url
    public static function dashboardUrl($module)
    {
        $u = getenv('ADMIN_DASHBOARD_URL_' . strtoupper(self::key($module)));

        return $u !== false && $u !== '' ? $u : null;
    }
    // [doc:end] dashboardUrl

    // [doc:start] customer - The customer (user) app's numbers: from its own service when ADMIN_URL_CUSTOMER is set, else from this database.
    /**
     * The customer (user) app's numbers: from its own service when ADMIN_URL_CUSTOMER is set, else from this database.
     *
     * @return array CustomerReport::summary() plus 'source' => 'local' | 'remote' and, when a remote call failed, 'error'
     */
    public static function customer($from = null, $to = null)
    {
        $base = getenv('ADMIN_URL_CUSTOMER');
        if ($base === false || $base === '') {
            $report = \app\modules\customerapp\services\CustomerReport::class;

            return class_exists($report)
                ? ['source' => 'local'] + $report::summary($from, $to)
                : ['source' => 'local', 'error' => 'the customer app is not part of this process'];
        }
        $secret = ServiceSignature::secret('customer', 'ADMIN');
        if ($secret === '') {
            return ['source' => 'remote', 'error' => 'ADMIN_SECRET_CUSTOMER is not set'];
        }
        $path = '/api/admin-report/summary';
        $h = ['Accept: application/json'];
        foreach (ServiceSignature::sign('customer', $secret, 'GET', $path, '') as $k => $v) {
            $h[] = $k . ': ' . $v;
        }
        $ch = curl_init(rtrim($base, '/') . $path . '?' . http_build_query(array_filter(['from' => $from, 'to' => $to])));
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8, CURLOPT_CONNECTTIMEOUT => 4, CURLOPT_HTTPHEADER => $h]);
        $out = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $json = $out ? json_decode($out, true) : null;
        if (!is_array($json) || ($json['status'] ?? '') !== 'OK') {
            return ['source' => 'remote', 'error' => 'the customer app did not answer (HTTP ' . $code . ')'];
        }

        return ['source' => 'remote'] + $json['details'];
    }
    // [doc:end] customer

    /**
     * @return array ServiceReport::summary() plus 'source' => 'local' | 'remote' and, when a remote call failed, 'error'
     */
    public static function summary($module, $from = null, $to = null)
    {
        if (!self::isSeparate($module)) {
            return ['source' => 'local'] + ServiceReport::summary($module, $from, $to);
        }
        $key = self::key($module);
        $base = rtrim((string) getenv('ADMIN_URL_' . strtoupper($key)), '/');
        $secret = ServiceSignature::secret($key, 'ADMIN');
        if ($secret === '') {
            return ['source' => 'remote', 'error' => 'ADMIN_SECRET_' . strtoupper($key) . ' is not set'];
        }
        $path = '/api/admin-report/summary';
        $query = http_build_query(array_filter(['module' => $module, 'from' => $from, 'to' => $to]));
        $h = ['Accept: application/json'];
        foreach (ServiceSignature::sign($key, $secret, 'GET', $path, '') as $k => $v) {
            $h[] = $k . ': ' . $v;
        }
        $ch = curl_init($base . $path . '?' . $query);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8, CURLOPT_CONNECTTIMEOUT => 4, CURLOPT_HTTPHEADER => $h]);
        $out = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $json = $out ? json_decode($out, true) : null;
        if (!is_array($json) || ($json['status'] ?? '') !== 'OK') {
            return ['source' => 'remote', 'error' => 'service did not answer (HTTP ' . $code . ')'];
        }

        return ['source' => 'remote'] + $json['details'];
    }
}
