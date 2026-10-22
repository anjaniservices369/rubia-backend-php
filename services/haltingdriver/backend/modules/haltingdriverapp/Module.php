<?php

namespace app\modules\haltingdriverapp;

use app\modules\api\ApiModule;

/**
 * Driver (partner) booking-flow API.
 *
 * Driver onboarding, documents, bookings, earnings and subscription. Routes /api/driver/*.
 */
class Module extends ApiModule
{
    public $controllerNamespace = 'app\modules\haltingdriverapp\controllers';
}