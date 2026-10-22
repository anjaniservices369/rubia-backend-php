<?php

namespace app\modules\haltingdrivercustomer\controllers;

use app\modules\api\controllers\BKController;
use yii;
use yii\filters\AccessControl;
use yii\filters\AccessRule;
use yii\helpers\ArrayHelper;
use app\modules\auth\services\AuthSettings;
use yii\data\ActiveDataProvider;
use yii\helpers\Url;
use app\models\User;
use app\modules\admin\models\Auth;
use app\modules\admin\models\WebSetting;
use app\modules\admin\models\AuthSession;
use app\modules\admin\models\PaymentTransaction;
use paytm\paytmchecksum\PaytmChecksum;
use app\modules\admin\models\Wallet;

class WalletController extends BKController
{
    // [doc:start] behaviors - Behaviors
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [

            'corsFilter' => [
                'class' => \yii\filters\Cors::className(),
                'cors' => [
                    // restrict access to
                    'Origin' => ['http://localhost:*', 'http://localhost:58600'],
                    // Allow only POST and PUT methods
                    'Access-Control-Request-Method' => ['POST', 'PUT'],
                    // Allow only headers 'X-Wsse'
                    'Access-Control-Request-Headers' => ['X-Wsse'],
                    // Allow credentials (cookies, authorization headers, etc.) to be exposed to the browser
                    'Access-Control-Allow-Credentials' => true,
                    // Allow OPTIONS caching
                    'Access-Control-Max-Age' => 3600,
                    // Allow the X-Pagination-Current-Page header to be exposed to the browser.
                    'Access-Control-Expose-Headers' => ['X-Pagination-Current-Page'],
                ],
            ],

            'access' => [
                'class' => AccessControl::className(),
                'ruleConfig' => [

                    'class' => AccessRule::className()
                ],

                'rules' => [
                    [
                        'actions' => [

                            'add-to-wallet',
                            'wallet-sum',
                            'wallet-transaction',
                            'paytm-add-money-transaction-api'



                        ],

                        'allow' => true,
                        'roles' => [
                            '@'
                        ]
                    ],
                    [

                        'actions' => [

                            'add-to-wallet',
                            'wallet-sum',
                            'wallet-transaction',
                            'paytm-add-money-transaction-api'




                        ],

                        'allow' => true,
                        'roles' => [

                            '?',
                            '*',

                        ]
                    ]
                ]
            ]

        ]);
    }
    // [doc:end] behaviors


    // [doc:start] actionAddToWallet - Add To Wallet
    public function actionAddToWallet()
    {
        $data = [];
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        //$userID = Yii::$app->request->post();
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        $setting = new WebSetting();
        $mid = $setting->getSettingBykey('paytm_mid');
        $key = $setting->getSettingBykey('paytm_key');

        if (!empty($user_id)) {
            if (!empty($user_id)) {
                $post = Yii::$app->request->post();
                $result = [];
                $data["mid"] = $mid;
                $data["orderId"] = $post['orderId'];

                $paytmParams['body'] = $data;
                // $checkSum = PaytmChecksum::generateSignature($data, 'hpDu5rPuyVdJ3Axz');
                $checkSum = PaytmChecksum::generateSignature(json_encode($paytmParams["body"], JSON_UNESCAPED_SLASHES), $key);

                $paytmParams['head']['signature'] = $checkSum;

                $post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);

                $url = "https://securegw.paytm.in/v3/order/status";

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                $response = curl_exec($ch);
                $results = json_decode(json_encode($response), true);
                $res = json_decode($results, true);
                // var_dump($res);exit;
                if ($res['body']['resultInfo']['resultStatus'] == 'TXN_SUCCESS') {

                    //check whether orderId is there or not
                    $checkOrderid = PaymentTransaction::find()->Where(['orderId' => $res['body']['orderId']])->one();
                    if (empty($checkOrderid)) {
                        //Explode Order id to get plan id
                        $order_id = explode("_", $res['body']['orderId']);
                        //  if (!empty($order_id)) {
                        //      $plan_id = $order_id[3];
                        //  }

                        $payment_transaction = new PaymentTransaction();
                        $payment_transaction->user_id = $user_id;
                        $payment_transaction->txnId = isset($res['body']['txnId']) ? $res['body']['txnId'] : 'No found';
                        $payment_transaction->resultStatus = isset($res['body']['resultInfo']['resultStatus']) ? $res['body']['resultInfo']['resultStatus'] : 'Not found';
                        $payment_transaction->resultMsg = isset($res['body']['resultInfo']['resultMsg']) ? $res['body']['resultInfo']['resultMsg'] : 'No found';
                        $payment_transaction->bankTxnId = isset($res['body']['bankTxnId']) ? $res['body']['bankTxnId'] : 'Not found';
                        $payment_transaction->orderId = isset($res['body']['orderId']) ? $res['body']['orderId'] : 'Not found';
                        $payment_transaction->txnAmount = isset($res['body']['txnAmount']) ? $res['body']['txnAmount'] : 'Not found';
                        $payment_transaction->txnType = isset($res['body']['txnType']) ? $res['body']['txnType'] : 'Not found';
                        $payment_transaction->gatewayName = isset($res['body']['gatewayName']) ? $res['body']['gatewayName'] : 'Not found';
                        $payment_transaction->mid = isset($res['body']['mid']) ? $res['body']['mid'] : 'Not found';
                        $payment_transaction->bankName = isset($res['body']['bankName']) ? $res['body']['bankName'] : 'Not found';
                        $payment_transaction->paymentMode = isset($res['body']['paymentMode']) ? $res['body']['paymentMode'] : 'Not found';
                        $payment_transaction->txnDate = isset($res['body']['txnDate']) ? $res['body']['txnDate'] : 'Not found';
                        if (!$payment_transaction->save(false)) {
                            $result['status'] = self::API_NOK;
                            $result['error'] = 'Failed to save payment Transaction';
                        } else {

                            // Add subscription plan to merchant
                            $model = new Wallet();
                            $model->user_id = $user_id;
                            $model->amount = $payment_transaction->txnAmount;
                            $model->payment_type = Wallet::STATUS_CREDITED;
                            $model->method_reason = "Amount of Rs " . $model->amount . " credited to your wallet with txn id#" . $payment_transaction->txnId;

                            $model->status = Wallet::STATUS_ACTIVE;
                            if ($model->save(false)) {
                                $title = "Amount added";
                                $body = "Amount added successfully";
                                $send_noti = Yii::$app->push->notifyUser("", $user_id, $title, $body);
                                $result['status'] = SELF::API_OK;
                                $result['details'] = $payment_transaction;
                            } else {
                                $result['error'] = Yii::t("app", "Failed to save wallet transaction");
                                $result['status'] = SELF::API_OK;
                            }
                        }
                    } else {
                        $checkOrderid->resultStatus = $res['body']['resultInfo']['resultStatus'];
                        $checkOrderid->save(false);
                        $result['status'] = self::API_OK;
                        $result['error'] = "Order already placed with this id";
                    }
                } else {
                    $result['status'] = self::API_NOK;
                    $result['error'] = 'Payment failed';
                }
            } else {
                $result['status'] = self::API_NOK;
                $result['error'] = 'No user found';
            }

            return $this->sendJsonResponse($result);
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = ["User Not Found"];
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionAddToWallet


    //Sum
    // [doc:start] actionWalletSum - Wallet Sum
    public function actionWalletSum()
    {

        $data = [];
        $post = Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        $page = isset($page) ? $page : '0';
        if (!empty($user_id)) {


            $walletSum = Wallet::getAvailableWallet($user_id);

            $data['status'] = SELF::API_OK;
            $data['details'] = round($walletSum, 2);
        } else {
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "User Not Found");
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionWalletSum

    // [doc:start] actionWalletTransaction - Wallet Transaction
    public function actionWalletTransaction($page = '', $start_date = '')
    {

        $data = [];
        $post = Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        $page = isset($page) ? $page : '0';
        if (!empty($user_id)) {


            $query = Wallet::find()->Where(['user_id' => $user_id]);
            if (!empty($start_date)) {
                $date_explode = explode(" - ", $start_date);
                $date1 = trim($date_explode[0]);
                $date2 = trim($date_explode[1]);
                $query->andFilterWhere(['between', 'created_on', $date1, $date2]);
            }
            $freeWallet = new ActiveDataProvider([
                'query' => $query,
                'sort' => [
                    'defaultOrder' => [
                        'id' => SORT_DESC,
                        // 'delivery_date' => SORT_ASC,
                    ],
                ],
                'pagination' => array(
                    'page' => $page,
                    'pageSize' => 10,
                ),
            ]);
            if (!empty($freeWallet->getModels())) {
                foreach ($freeWallet->getModels() as $free) {

                    $data['details'][] = $free->asJson();
                }
                $data['status'] = SELF::API_OK;
            } else {
                $data['status'] = SELF::API_NOK;
                $data['error'] = Yii::t("app", "No  transactions found");
            }
        } else {
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "User Not Found");
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionWalletTransaction

    // [doc:start] actionPaytmAddMoneyTransactionApi - Paytm Add Money Transaction Api
    public function actionPaytmAddMoneyTransactionApi()
    {
        $data = [];
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        $setting = new WebSetting();
        $mid = $setting->getSettingBykey('paytm_mid');
        $key = $setting->getSettingBykey('paytm_key');
        $post = Yii::$app->request->post();
        // print_r($mid);exit;
        if (!empty($user_id)) {


            $data["mid"] = $mid;
            $data["orderId"] = "Alladin" . rand(10000, 99999999) . time() . '_';
            $data["requestType"] = "Payment";
            $data["websiteName"] = "DEFAULT";
            $data["txnAmount"]["value"] = $post['amount'];
            $data["txnAmount"]["currency"] = "INR";

            $data["userInfo"]["custId"] = $user_id;

            $data["callbackUrl"] = "https://securegw.paytm.in/theia/paytmCallback?ORDER_ID=" . $data["orderId"];

            // $d['mode'] ='UPI';
            // // $d['channels'][] ='UPI';
            //  $data['enablePaymentMode'][]=$d;

            $paytmParams['body'] = $data;
            // $checkSum = PaytmChecksum::generateSignature($data, 'hpDu5rPuyVdJ3Axz');
            $checkSum = PaytmChecksum::generateSignature(json_encode($paytmParams["body"], JSON_UNESCAPED_SLASHES), $key);

            $paytmParams['head']['signature'] = $checkSum;

            $post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);
            // print_r($post_data);exit;
            $url = "https://securegw.paytm.in/theia/api/v1/initiateTransaction?mid=" . $data["mid"] . "&orderId=" . $data["orderId"];
            //    print_r($url);exit;
            /* for Production */
            // $url = "https://securegw.paytm.in/theia/api/v1/initiateTransaction?mid=YOUR_MID_HERE&orderId=ORDERID_98765";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
            $response = curl_exec($ch);
            // print_r($response);exit;

            $results = json_decode(json_encode($response), true);
            $res = json_decode($results, true);
            // var_dump($res['body']);exit;
            if ($res['body']['resultInfo']['resultStatus'] == 'S') {
                $result['status'] = self::API_OK;
                $result['details'] = $res;
                $result['orderId'] = $data['orderId'];
                // $result['details'] = $res;
            } else {
                $result['status'] = self::API_NOK;
                $result['error'] = 'Failed';
                // print_r($res);exit;
            }
        } else {
            $result['status'] = self::API_NOK;
            $result['error'] = 'User Not found';
        }

        return $this->sendJsonResponse($result);
    }
    // [doc:end] actionPaytmAddMoneyTransactionApi


  
}
