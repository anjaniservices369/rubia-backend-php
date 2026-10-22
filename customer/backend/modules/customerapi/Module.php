<?php

namespace app\modules\customerapi;

use app\modules\api\ApiModule;

/**
 * The user-app gateway: what the services ask the user-app system (profile, notify, authenticate).
 * Routes /api/customer/*. Callers are services, never the mobile app, and every call must be signed
 * (ServiceSignature).
 */
class Module extends ApiModule
{
    public $controllerNamespace = 'app\modules\customerapi\controllers';
}
