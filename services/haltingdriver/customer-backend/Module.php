<?php

namespace app\modules\haltingdrivercustomer;

use app\modules\api\ApiModule;

/**
 * Halting drivers service, customer side: the booking flow, wallet and referral endpoints of the customer app that belong to
 * this service. Routes /api/user/* (booking flow), /api/booking/*, /api/wallet/*, /api/user-wallet/*. The user app's own
 * login / profile / address-book endpoints are not here (app/modules/customerapp).
 */
class Module extends ApiModule
{
    public $controllerNamespace = 'app\modules\haltingdrivercustomer\controllers';

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        // wallet, coupons and referrals of the customer app are this service's own
        \app\modules\geo\services\ServiceScope::enter(null, $action->id);
        \app\modules\geo\services\ServiceScope::setCommercial(\app\modules\geo\services\ServiceModules::DRIVER);

        return true;
    }
}
