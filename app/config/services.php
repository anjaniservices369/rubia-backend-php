<?php

/**
 * The services and what each one's own entry point serves.
 *
 * Every service has its own entry point (web/<service>.php) and can use a separate database through its
 * DB_DSN_<SERVICE> setting. The public addresses are the ones in config/api-routes.php, unchanged.
 *
 *   module    service number (ServiceModules)
 *   api       API address prefixes this entry point answers (everything else is 404)
 *   partnerApp false when the service's partner app is not built yet (Taxi, Auto, Bike: only the Driver app exists): the service is separate all the same
 *   routes    module id => module id: where this service's own process answers an address with its own module instead of the
 *             shared one (the user app and the service share customer-app today; the service answers /api/user/* itself)
 *   db        environment variable prefix for the service's own database (DB_DSN_<DB>, DB_USER_<DB>, DB_PASSWORD_<DB>);
 *             when DB_DSN_<DB> is not set the shared database is used (stage 2)
 */
return [
    // the customer (user) app: its own backend, database, dashboard, authentication and notifications
    'customer' => ['module' => 0, 'kind' => 'customer', 'db' => 'CUSTOMER', 'api' => ['user', 'user-wallet', 'customer', 'media', 'web', 'admin-report']],
    'haltingdriver' => ['module' => 1, 'slug' => 'halting-drivers', 'db' => 'HALTINGDRIVER', 'routes' => ['customer-app' => 'haltingdriver-customer'], 'api' => ['driver', 'user', 'booking', 'wallet', 'manager', 'media', 'customer', 'admin-report']],
    'cartaxi'  => ['module' => 2, 'slug' => 'car-taxi', 'partnerApp' => false, 'db' => 'CARTAXI',  'api' => ['car', 'car-user', 'media', 'customer', 'admin-report']],
    'autotaxi' => ['module' => 3, 'slug' => 'auto-taxi', 'partnerApp' => false, 'db' => 'AUTOTAXI',   'api' => ['auto', 'auto-user', 'media', 'customer', 'admin-report']],
    'biketaxi' => ['module' => 4, 'slug' => 'bike-taxi', 'partnerApp' => false, 'db' => 'BIKETAXI',   'api' => ['bike', 'bike-user', 'media', 'customer', 'admin-report']],
];
