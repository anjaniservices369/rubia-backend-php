<?php

namespace app\modules\dispatch;

use yii\base\BootstrapInterface;
use yii\base\Module as BaseModule;

/**
 * Ride dispatch service module.
 *
 * Finds nearby taxi drivers, creates offers and expires unanswered rides. Component: orderDispatch.
 * Registered in config/web.php (modules + bootstrap) so its components exist before any request is handled.
 */
class Module extends BaseModule implements BootstrapInterface
{
    public function bootstrap($app)
    {
        $app->setComponents([
            'orderDispatch' => ['class' => services\OrderDispatch::class],
        ]);
    }
}