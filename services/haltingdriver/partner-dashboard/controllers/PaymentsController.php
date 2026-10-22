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
        $post = Yii::$app->request->post();
        if ($post) {
            foreach (['company_upi_id' => 'Company UPI id for QR payments (fallback when the gateway is down)', 'company_upi_name' => 'Name shown with the company UPI QR'] as $k => $n) {
                if (array_key_exists($k, $post)) {
                    $this->save($k, $n, trim($post[$k]), 14);
                }
            }
            Yii::$app->session->setFlash('success', 'Saved.');

            return $this->refresh();
        }
        $setting = new WebSetting();
        $orders = (new Query())->from('payment_order')->orderBy(['id' => SORT_DESC])->limit(50)->all();
        $review = (new Query())->from('payment_order')->where(['gateway' => 'manual', 'status' => 'review'])->orderBy(['id' => SORT_ASC])->all();

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
            'review' => $review,
            'upiId' => (string) $setting->getSettingBykey('company_upi_id'),
            'upiName' => (string) $setting->getSettingBykey('company_upi_name'),
        ]);
    }
    // [doc:end] actionIndex

    // [doc:start] actionDecide - /admin/payments/decide - Approve (credits once) or reject a payment a partner made by the company UPI QR, after checking the bank statement.
    /**
     * Approve (credits once) or reject a payment a partner made by the company UPI QR, after checking the bank statement.
     * POST only: id and approve (1 or 0).
     */
    public function actionDecide()
    {
        $req = Yii::$app->request;
        if (!$req->isPost) {
            throw new \yii\web\MethodNotAllowedHttpException();
        }
        $result = \app\modules\payments\services\PaymentOrders::manualDecision((int) $req->post('id'), (int) $req->post('approve') === 1);
        Yii::$app->session->setFlash($result === 'missing' ? 'error' : 'success', $result === 'approved' ? 'Approved and credited.' : ($result === 'rejected' ? 'Rejected.' : 'That payment is not waiting for approval.'));

        return $this->redirect(['index']);
    }
    // [doc:end] actionDecide
}
