<?php

namespace app\modules\payments;

use yii\base\BootstrapInterface;
use yii\base\Module as BaseModule;

/**
 * Payments service module.
 *
 * CashFree and Open Money gateway clients (used with new CashFree() / new OpenMoney()).
 * Registered in config/web.php (modules + bootstrap) so its components exist before any request is handled.
 */
class Module extends BaseModule implements BootstrapInterface
{
    public function bootstrap($app)
    {
        // No application-wide components: the service classes are used directly.
    }
}