<?php

/**
 * Application configuration of one service's OWN dashboard (web/<service>-dashboard.php).
 *
 * The admin panel (maps, franchise, billing, invoices, tax reports, drivers, bookings...) running on the service's
 * database (DB_DSN_<SERVICE>), showing that service only (params serviceDashboard), logging in staff accounts that live
 * in that database. No customer or other service's data is reachable from it.
 *
 * @param string $service key in config/services.php
 */
return function ($service) {
    $registry = require __DIR__ . '/services.php';
    if (!isset($registry[$service])) {
        throw new \InvalidArgumentException("Unknown service '$service'");
    }
    $entry = $registry[$service];
    $config = (function () {
        return require __DIR__ . '/web.php';
    })();

    $own = getenv('DB_DSN_' . $entry['db']);
    if ($own) {
        $config['components']['db']['dsn'] = $own;
        $config['components']['db']['username'] = getenv('DB_USER_' . $entry['db']) ?: $config['components']['db']['username'];
        $config['components']['db']['password'] = getenv('DB_PASSWORD_' . $entry['db']) ?: $config['components']['db']['password'];
    }
    // a service dashboard is bound to that service's number; the customer dashboard to none (-1) and flagged
    $config['params']['serviceDashboard'] = $entry['module'] > 0 ? $entry['module'] : -1;
    if (($entry['kind'] ?? '') === 'customer') {
        $config['params']['customerDashboard'] = true;
    }
    $config['params']['serviceEntry'] = ['service' => $service, 'module' => $entry['module']];
    $config['params']['customerApi'] = array_merge($config['params']['customerApi'] ?? [], ['service' => $service]);
    // the integrations module lives at dashboard/<service>/integrations (for example dashboard/halting-drivers/integrations)
    $base = 'dashboard/' . ($entry['slug'] ?? $service) . '/integrations';
    $config['components']['urlManager']['rules'] = array_merge([
        $base => 'admin/integrations/index',
        $base . '/<id:\w+>/test' => 'admin/integrations/test',
        $base . '/<id:\w+>' => 'admin/integrations/view',
    ], $config['components']['urlManager']['rules']);
    // the dashboard is served from its own address: its home is the service dashboard
    $config['defaultRoute'] = 'admin/service-map/index';

    return $config;
};
