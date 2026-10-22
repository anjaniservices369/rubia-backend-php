<?php

namespace app\modules\auth;

use yii\base\BootstrapInterface;
use yii\base\Module as BaseModule;

/**
 * Authentication service module.
 *
 * Session lookup (auth_code), JWT issuing/verification and OTP request handling. Component: otp.
 * Registered in config/web.php (modules + bootstrap) so its components exist before any request is handled.
 */
class Module extends BaseModule implements BootstrapInterface
{
    public function bootstrap($app)
    {
        $app->setComponents([
            'otp' => ['class' => services\OtpService::class],
        ]);
    }
}