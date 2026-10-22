<?php

namespace app\modules\taxicore\partner;

use app\modules\api\ApiModule;
use app\modules\taxicore\partner\services\TaxiOfferService;
use app\modules\taxicore\partner\services\TaxiPresenceService;

/**
 * Partner (driver) API shared by the car taxi, auto taxi and bike taxi services.
 *
 * Offers, accept/reject, ride progress and position of the partners. There is no generic "taxi driver": each service has its
 * own module (cartaxidriver, autotaxidriver, biketaxidriver) that extends this one and says which service it is; routes
 * /api/car/*, /api/auto/*, /api/bike/*.
 *
 * @property TaxiOfferService $offers
 * @property TaxiPresenceService $presence
 */
class Module extends ApiModule
{
    public $controllerNamespace = 'app\modules\taxicore\partner\controllers';

    public function init()
    {
        parent::init();

        $this->setComponents([
            'offers' => ['class' => TaxiOfferService::class],
            'presence' => ['class' => TaxiPresenceService::class],
        ]);
    }

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
