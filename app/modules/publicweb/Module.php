<?php

namespace app\modules\publicweb;

use app\modules\api\ApiModule;

/**
 * Public website API.
 *
 * Contact form and public lookups. Routes /api/web/*.
 */
class Module extends ApiModule
{
    public $controllerNamespace = 'app\modules\publicweb\controllers';
}