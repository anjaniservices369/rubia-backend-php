<?php

namespace app\modules\customerapp;

use app\modules\api\ApiModule;

/**
 * Customer booking-flow API.
 *
 * Customer profile, bookings, wallet and referrals. Routes /api/user/*, /api/booking/*, /api/wallet/*, /api/user-wallet/*.
 */
class Module extends ApiModule
{
    public $controllerNamespace = 'app\modules\customerapp\controllers';

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        // The customer app's wallet, coupons and referrals are the driver service's own.
        \app\modules\geo\services\ServiceScope::enter(null, $action->id);
        \app\modules\geo\services\ServiceScope::setCommercial(\app\modules\geo\services\ServiceModules::DRIVER);

        return true;
    }
}
