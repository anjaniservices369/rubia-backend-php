<?php

/**
 * Public API URLs and the module that serves each of them.
 *
 * The URLs are what the installed mobile apps call, so they stay as they are; only the code behind them
 * moved into one module per service. Add new endpoints to the module, not here.
 *
 *   URL prefix            module        controller
 */
$map = [
    'admin-report' => 'admin-api/report',          // super admin dashboard -> service (signed, read-only)
    'customer'    => 'customer-api/customer',   // services -> user-app system (signed calls only)
    'bike'        => 'biketaxidriver/taxi',        // bike partner app (own service)
    'bike-user'   => 'biketaxirider/taxi-user',    // bike customer API (own service)
    'auto'        => 'autotaxidriver/taxi',        // auto partner app (own service)
    'auto-user'   => 'autotaxirider/taxi-user',    // auto customer API (own service)
    'car'        => 'cartaxidriver/taxi',        // car partner app (own service)
    'car-user'   => 'cartaxirider/taxi-user',    // car customer API (own service)
    'driver'      => 'haltingdriver-app/driver',
    'user'        => 'customer-app/user',
    'booking'     => 'customer-app/booking',
    'wallet'      => 'customer-app/wallet',
    'user-wallet' => 'customer-app/user-wallet',
    'manager'     => 'manager-app/manager',
    'media'       => 'file-storage/media',
    'web'         => 'public-web/web',
];

$rules = [];
foreach ($map as $prefix => $route) {
    $rules["api/$prefix"] = "$route/index";
    $rules["api/$prefix/<action:[\w-]+>"] = "$route/<action>";
}

return $rules;