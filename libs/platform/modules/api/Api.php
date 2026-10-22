<?php

namespace app\modules\api;

/**
 * API kernel module (route /api/default). Holds ApiModule and BKController, the shared base of all API
 * modules. Public URLs of the services (/api/taxi, /api/user, ...) are mapped to their modules in
 * config/api-routes.php.
 */
class Api extends ApiModule
{
    public $controllerNamespace = 'app\modules\api\controllers';
}