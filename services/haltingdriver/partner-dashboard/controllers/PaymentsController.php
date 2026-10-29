<?php

namespace app\modules\admin\controllers;

use app\models\User;
use app\modules\admin\models\WebSetting;
use app\modules\payments\services\Razorpay;
use Yii;
use yii\db\Query;
use yii\filters\AccessControl;
use yii\web\Controller;

/**
 * Payments screen of the Halting drivers dashboard: the Razorpay keys (pasted here, never in code), the joining fee, the
 * "enforce driver onboarding" switch, the webhook address, and the latest payments of the partners.
 *
 * The secrets are never shown again after saving: the screen only says whether they are set. A field left empty keeps the saved value.
 * Administrators only.
 */
class PaymentsController extends Controller
{
    // [doc:start] behaviors - Behaviors
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [[
                    'allow' => true,
                    'matchCallback' => function () {
                        $u = Yii::$app->user->identity;

                        return $u && $u->user_role === User::ROLE_ADMIN;
                    },
                ]],
            ],
        ];
    }
    // [doc:end] behaviors

    private function save($key, $name, $value, $type)
    {
        $row = WebSetting::findOne(['setting_key' => $key]);
        if (!$row) {
            $row = new WebSetting();
            $row->setting_key = $key;
            $row->name = $name;
            $row->type_id = $type;
            $row->status = WebSetting::STATUS_ACTIVE;
        }
        $row->value = (string) $value;
        $row->save(false);
    }

    // [doc:start] actionIndex - /admin/payments/index - Razorpay keys, joining fee, onboarding switch and the latest partner payments; saves the form.
    /**
     * Razorpay keys, joining fee, onboarding switch and the latest partner payments; saves the form.
     */
    public function actionIndex()
    {
        $setting = new WebSetting();
        $orders = (new Query())->from('payment_order')->orderBy(['id' => SORT_DESC])->limit(50)->all();

        return $this->render('index', [
            'keyId' => Razorpay::setting('key_id'),
            'secretSet' => Razorpay::setting('key_secret') !== '',
            'webhookSet' => Razorpay::setting('webhook_secret') !== '',
            'fromEnv' => trim((string) $setting->getSettingBykey('razorpay_key_secret')) === '' && Razorpay::setting('key_secret') !== '',
            'mode' => Razorpay::mode(),
            'fee' => (float) $setting->getSettingBykey('driver_joining_fee'),
            'enforce' => (string) $setting->getSettingBykey('enforce_driver_onboarding') === '1',
            'webhookUrl' => Yii::$app->urlManager->createAbsoluteUrl('/api/driver/razorpay-webhook'),
            'orders' => $orders,
        ]);
    }
    // [doc:end] actionIndex

}
