<?php

/**
 * Application configuration of one service's entry point.
 *
 * Built from the full web config: same modules, components and secrets, but
 *  - only the service's API addresses are routed (strict parsing: anything else is 404, no admin, no website),
 *  - the database is the service's own when DB_DSN_<SERVICE> is set, the shared one otherwise,
 *  - the user-app gateway client calls as this service (params customerApi.service).
 *
 * @param string $service key in config/services.php
 */
return function ($service) {
    $registry = require __DIR__ . '/services.php';
    if (!isset($registry[$service])) {
        throw new \InvalidArgumentException("Unknown service '$service'");
    }
    $entry = $registry[$service];
    // web.php runs in this scope and may define variables of its own: keep ours under names it does not use
    $config = (function () {
        return require __DIR__ . '/web.php';
    })();

    // api-routes.php defines $map and $rules in the scope it is required from: load it in a scope of its own
    $all = (function () {
        return require __DIR__ . '/api-routes.php';
    })();
    $routed = [];
    foreach ($all as $pattern => $route) {
        foreach ($entry['api'] as $prefix) {
            if ($pattern === "api/$prefix" || strpos($pattern, "api/$prefix/") === 0) {
                $routed[$pattern] = $route;
            }
        }
    }
    // addresses the service answers with its own module instead of the shared one (config/services.php 'routes')
    foreach ($entry['routes'] ?? [] as $from => $to) {
        foreach ($routed as $pattern => $route) {
            if (strpos($route, $from . '/') === 0) {
                $routed[$pattern] = $to . substr($route, strlen($from));
            }
        }
    }
    $config['components']['urlManager']['rules'] = $routed;
    // modules that serve only addresses of other services are not registered here (a release does not contain them)
    $kept = [];
    foreach ($routed as $route) {
        $kept[strtok($route, '/')] = true;
    }
    foreach ($all as $route) {
        $id = strtok($route, '/');
        if (!isset($kept[$id])) {
            unset($config['modules'][$id]);
        }
    }
    $config['components']['urlManager']['enableStrictParsing'] = true;
    $config['components']['urlManager']['showScriptName'] = false;

    $own = getenv('DB_DSN_' . $entry['db']);
    if ($own) {
        $config['components']['db']['dsn'] = $own;
        $config['components']['db']['username'] = getenv('DB_USER_' . $entry['db']) ?: $config['components']['db']['username'];
        $config['components']['db']['password'] = getenv('DB_PASSWORD_' . $entry['db']) ?: $config['components']['db']['password'];
    }

    // an API process answers errors itself (plain page / JSON by the framework), not through the website's error page
    $config['components']['errorHandler'] = ['class' => 'yii\web\ErrorHandler'];

    // no admin panel, debug or code generator on a service entry point
    unset($config['modules']['admin'], $config['modules']['debug'], $config['modules']['gii']);
    $config['bootstrap'] = array_values(array_diff((array) ($config['bootstrap'] ?? []), ['debug', 'gii']));

    // the shared-process glue controllers (app/glue) belong to index.php only; a service entry answers with its own
    unset($config['modules']['customer-app']['controllerNamespace']);

    $config['params']['customerApi'] = array_merge($config['params']['customerApi'] ?? [], ['service' => $service]);
    $config['params']['serviceEntry'] = ['service' => $service, 'module' => $entry['module']];

    return $config;
};
