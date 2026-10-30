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

    // [doc:start] actionWithdrawals - /admin/payments/withdrawals - Withdrawal requests waiting to be paid, with the bank account each driver registered, and the latest settled ones.
    /**
     * Withdrawal requests waiting to be paid, with the bank account each driver registered, and the latest settled ones.
     */
    public function actionWithdrawals()
    {
        $waiting = (new Query())->select(['w.id', 'w.driver_id', 'w.amount', 'w.method_reason', 'w.created_on', 'u.first_name', 'u.last_name', 'u.contact_no',
                'b.account_holder_name', 'b.account_number', 'b.ifsc', 'b.bank_name', 'b.branch_name', 'b.upi'])
            ->from(['w' => 'driver_wallet'])
            ->leftJoin(['u' => 'user'], 'u.id = w.driver_id')
            ->leftJoin(['b' => 'driver_bank_details'], 'b.driver_id = w.driver_id')
            ->where(['w.payment_type' => 2, 'w.status' => 2])->andWhere(['or', ['like', 'w.method_reason', 'Withdrawal'], ['like', 'w.method_reason', 'Amount withdraw']])
            ->orderBy(['w.id' => SORT_ASC])->all();
        $done = (new Query())->select(['w.id', 'w.driver_id', 'w.amount', 'w.status', 'w.method_reason', 'w.updated_on', 'u.first_name', 'u.last_name'])
            ->from(['w' => 'driver_wallet'])->leftJoin(['u' => 'user'], 'u.id = w.driver_id')
            ->where(['w.payment_type' => 2])->andWhere(['in', 'w.status', [1, 4]])->andWhere(['or', ['like', 'w.method_reason', 'Withdrawal'], ['like', 'w.method_reason', 'Amount withdraw']])
            ->orderBy(['w.id' => SORT_DESC])->limit(30)->all();

        return $this->render('withdrawals', ['waiting' => $waiting, 'done' => $done]);
    }
    // [doc:end] actionWithdrawals

    // [doc:start] actionSettle - /admin/payments/settle - Mark a withdrawal as paid (with the bank reference) or reject it (the amount goes back to the driver's balance).
    /**
     * Mark a withdrawal as paid (with the bank reference) or reject it (the amount goes back to the driver's balance).
     * POST only: id, decision (paid | reject), reference (for paid).
     */
    public function actionSettle()
    {
        $req = Yii::$app->request;
        if (!$req->isPost) {
            throw new \yii\web\MethodNotAllowedHttpException();
        }
        $id = (int) $req->post('id');
        $decision = (string) $req->post('decision');
        $reference = trim((string) $req->post('reference'));
        $row = (new Query())->from('driver_wallet')->where(['id' => $id, 'payment_type' => 2, 'status' => 2])->andWhere(['or', ['like', 'method_reason', 'Withdrawal'], ['like', 'method_reason', 'Amount withdraw']])->one();
        $mark = '(waiting for the office to pay)';
        $note = function ($text, $replacement) use ($row, $mark) {
            return strpos($row['method_reason'], $mark) !== false ? str_replace($mark, $replacement, $row['method_reason']) : trim($row['method_reason'] . ' ' . $replacement);
        };
        if (!$row) {
            Yii::$app->session->setFlash('error', 'That withdrawal is not waiting any more.');
        } elseif ($decision === 'paid') {
            if (!preg_match('/^[A-Za-z0-9\-\/ ]{6,40}$/', $reference)) {
                Yii::$app->session->setFlash('error', 'Enter the bank reference (UTR / transaction id) of the transfer: 6 to 40 letters and digits.');
            } else {
                Yii::$app->db->createCommand()->update('driver_wallet', [
                    'status' => 1, 'updated_on' => date('Y-m-d H:i:s'),
                    'method_reason' => $note(null, '(paid, ref ' . $reference . ')'),
                ], ['id' => $id, 'status' => 2])->execute();
                Yii::$app->session->setFlash('success', 'Marked as paid.');
            }
        } elseif ($decision === 'reject') {
            Yii::$app->db->createCommand()->update('driver_wallet', [
                'status' => 4, 'updated_on' => date('Y-m-d H:i:s'),
                'method_reason' => $note(null, '(rejected, amount returned to the balance)'),
            ], ['id' => $id, 'status' => 2])->execute();
            Yii::$app->session->setFlash('success', 'Rejected: the amount is back in the driver\'s balance.');
        }

        return $this->redirect(['withdrawals']);
    }
    // [doc:end] actionSettle

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
