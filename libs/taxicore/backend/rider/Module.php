<?php

namespace app\modules\taxicore\rider;

use app\modules\api\ApiModule;

/**
 * Rider (customer) API shared by the car taxi, auto taxi and bike taxi services.
 *
 * Booking a ride, ride status, payment choice and rating by riders. Each service has its own module (cartaxirider,
 * autotaxirider, biketaxirider) that extends this one; routes /api/car-user/*, /api/auto-user/*, /api/bike-user/*.
 */
class Module extends ApiModule
{
    public $controllerNamespace = 'app\modules\taxicore\rider\controllers';

    /**
     * Which service this module instance IS: ServiceModules::BIKE / AUTO / TAXI (the car taxi). Set by the service's own
     * module (biketaxi..., autotaxi..., cartaxi...); every ride, vehicle and offer this module touches is limited to it.
     */
    public $serviceModule = null;

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        // false = the request was refused (wrong vehicle for this service); the NOK reply is already set
        return \app\modules\geo\services\ServiceScope::enter($this->serviceModule, $action->id);
    }
}
