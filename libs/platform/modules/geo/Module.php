<?php

namespace app\modules\geo;

use yii\base\BootstrapInterface;
use yii\base\Module as BaseModule;

/**
 * Geo service module.
 *
 * Driving distance and time (Google Maps) used for fares. Used with new DrivingDistance().
 * Registered in config/web.php (modules + bootstrap) so its components exist before any request is handled.
 */
class Module extends BaseModule implements BootstrapInterface
{
    public function bootstrap($app)
    {
        // No application-wide components: the service classes are used directly.
    }
}