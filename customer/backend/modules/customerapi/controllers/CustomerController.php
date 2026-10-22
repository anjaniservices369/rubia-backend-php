<?php

namespace app\modules\customerapi\controllers;

use app\modules\api\controllers\BKController;
use app\modules\customerapi\services\CustomerClient;
use app\modules\customerapi\services\ServiceSignature;
use Yii;

/**
 * Gateway of the user-app system for the services.
 *
 *   GET  /api/customer/profile?id=NN                  name and phone of a customer
 *   POST /api/customer/notify   {user_id,title,body}  push + in-app message to a customer
 *   POST /api/customer/authenticate {auth_code}       the customer a login code belongs to
 *
 * Only signed calls from a configured service are answered (ServiceSignature); everything else is 403.
 * Nothing here returns password hashes, tokens or any field a service does not need.
 */
class CustomerController extends BKController
{
    /** @var string service key of the verified caller */
    private $caller;

    // [doc:start] behaviors - Behaviors
    public function behaviors()
    {
        // No login here: callers are services, identified by the signature checked in beforeAction().
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::class,
                'rules' => [
                    ['allow' => true, 'actions' => ['profile', 'notify', 'authenticate', 'rating', 'wallet-balance', 'wallet-debit', 'wallet-credit'], 'roles' => ['?', '@']],
                ],
            ],
        ];
    }
    // [doc:end] behaviors

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        $this->caller = ServiceSignature::verifyRequest();
        if ($this->caller === null) {
            Yii::$app->response->statusCode = 403;
            Yii::$app->response->data = ['status' => 'NOK', 'error' => 'Forbidden'];

            return false;
        }

        return true;
    }

    // [doc:start] actionProfile - /api/customer/profile - Name and phone of a customer.
    /**
     * Name and phone of a customer.
     */
    public function actionProfile($id = 0)
    {
        $p = CustomerClient::profile((int) $id);

        return $p ? $this->sendJsonResponse(['status' => self::API_OK, 'details' => $p])
            : $this->sendJsonResponse(['status' => self::API_NOK, 'error' => 'Customer not found']);
    }
    // [doc:end] actionProfile

    // [doc:start] actionNotify - /api/customer/notify - Push and in-app message to a customer, through the user app's own notifications.
    /**
     * Push and in-app message to a customer, through the user app's own notifications.
     */
    public function actionNotify()
    {
        $post = json_decode(Yii::$app->request->getRawBody(), true) ?: [];
        $uid = (int) ($post['user_id'] ?? 0);
        $title = trim((string) ($post['title'] ?? ''));
        $body = trim((string) ($post['body'] ?? ''));
        if ($uid <= 0 || $title === '') {
            return $this->sendJsonResponse(['status' => self::API_NOK, 'error' => 'user_id and title are required']);
        }
        CustomerClient::localNotify($uid, $title, $body, (string) ($post['ref'] ?? ''));

        return $this->sendJsonResponse(['status' => self::API_OK, 'details' => 'queued']);
    }
    // [doc:end] actionNotify

    // [doc:start] actionRating - /api/customer/rating - A service reports the average rating partners gave the customer; the customer app keeps it on the customer's record.
    /**
     * A service reports the average rating partners gave the customer; the customer app keeps it on the customer's record.
     */
    public function actionRating()
    {
        $p = json_decode(Yii::$app->request->getRawBody(), true) ?: [];
        $uid = (int) ($p['user_id'] ?? 0);
        $avg = (float) ($p['average'] ?? 0);
        if ($uid <= 0 || $avg < 0 || $avg > 5) {
            return $this->sendJsonResponse(['status' => self::API_NOK, 'error' => 'user_id and an average between 0 and 5 are required']);
        }
        $ok = CustomerClient::reportRating($uid, $avg, (int) ($p['count'] ?? 0));

        return $this->sendJsonResponse($ok ? ['status' => self::API_OK, 'details' => 'saved'] : ['status' => self::API_NOK, 'error' => 'Customer not found']);
    }
    // [doc:end] actionRating

    // [doc:start] actionWalletBalance - /api/customer/wallet-balance - The customer's one wallet balance.
    /**
     * The customer's one wallet balance.
     */
    public function actionWalletBalance($user_id = 0)
    {
        return $this->sendJsonResponse(['status' => self::API_OK, 'details' => \app\modules\customerapi\services\WalletLedger::balance((int) $user_id)]);
    }
    // [doc:end] actionWalletBalance

    // [doc:start] actionWalletDebit - /api/customer/wallet-debit - Takes money from the customer's wallet for a purchase in a service (idempotent per ref).
    /**
     * Takes money from the customer's wallet for a purchase in a service (idempotent per ref).
     */
    public function actionWalletDebit()
    {
        $p = json_decode(Yii::$app->request->getRawBody(), true) ?: [];
        $r = \app\modules\customerapi\services\WalletLedger::debit((int) ($p['user_id'] ?? 0), (float) ($p['amount'] ?? 0), (int) ($p['module'] ?? 0), (string) ($p['reason'] ?? 'Debit'), (string) ($p['ref'] ?? ''));

        return $this->sendJsonResponse(['status' => self::API_OK, 'details' => $r]);
    }
    // [doc:end] actionWalletDebit

    // [doc:start] actionWalletCredit - /api/customer/wallet-credit - Puts money in the customer's wallet (refund, reward; idempotent per ref).
    /**
     * Puts money in the customer's wallet (refund, reward; idempotent per ref).
     */
    public function actionWalletCredit()
    {
        $p = json_decode(Yii::$app->request->getRawBody(), true) ?: [];
        $r = \app\modules\customerapi\services\WalletLedger::credit((int) ($p['user_id'] ?? 0), (float) ($p['amount'] ?? 0), (int) ($p['module'] ?? 0), (string) ($p['reason'] ?? 'Credit'), (string) ($p['ref'] ?? ''));

        return $this->sendJsonResponse(['status' => self::API_OK, 'details' => $r]);
    }
    // [doc:end] actionWalletCredit

    // [doc:start] actionAuthenticate - /api/customer/authenticate - The customer a login code belongs to.
    /**
     * The customer a login code belongs to.
     */
    public function actionAuthenticate()
    {
        $post = json_decode(Yii::$app->request->getRawBody(), true) ?: [];
        $uid = (new \app\modules\auth\services\AuthSettings())->getAuthSession((string) ($post['auth_code'] ?? ''));
        $p = $uid ? CustomerClient::profile($uid) : null;

        return $p ? $this->sendJsonResponse(['status' => self::API_OK, 'details' => $p])
            : $this->sendJsonResponse(['status' => self::API_NOK, 'error' => 'Not logged in']);
    }
    // [doc:end] actionAuthenticate
}
