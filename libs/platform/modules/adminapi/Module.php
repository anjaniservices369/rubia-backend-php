<?php

namespace app\modules\adminapi;

use app\modules\api\ApiModule;

/**
 * What the super admin dashboard asks a service: signed reporting calls (see ReportController).
 * Routes /api/admin-report/*; every call must be signed with ADMIN_SECRET_<SERVICE> (ServiceSignature).
 */
class Module extends ApiModule
{
    public $controllerNamespace = 'app\modules\adminapi\controllers';
}
