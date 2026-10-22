<?php

namespace app\modules\messaging;

use yii\base\BootstrapInterface;
use yii\base\Module as BaseModule;

/**
 * Messaging service module.
 *
 * Push (OneSignal/Expo), SMS/OTP delivery (2factor.in) and WhatsApp. Components: push, sms, whatsapp.
 * Registered in config/web.php (modules + bootstrap) so its components exist before any request is handled.
 */
class Module extends BaseModule implements BootstrapInterface
{
    public function bootstrap($app)
    {
        $app->setComponents([
            'push' => ['class' => services\PushService::class],
            'sms' => ['class' => services\SmsService::class],
            'whatsapp' => ['class' => services\WhatsAppService::class],
        ]);
    }
}