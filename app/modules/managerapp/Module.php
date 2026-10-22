<?php

namespace app\modules\managerapp;

use app\modules\api\ApiModule;

/**
 * Manager API.
 *
 * Temple/store manager app. Routes /api/manager/*.
 */
class Module extends ApiModule
{
    public $controllerNamespace = 'app\modules\managerapp\controllers';
}