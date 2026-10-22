<?php

namespace app\modules\customerapp\controllers;

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
use app\modules\admin\models\DriverAadharCard;
use app\modules\admin\models\DriverBankDetails;
use app\modules\admin\models\DriverEmergencyContacts;
use app\modules\admin\models\DriverPanCards;
use app\modules\admin\models\DriverVehicleTypes;
use app\modules\admin\models\DriverWallet;
use app\modules\admin\models\DrivingLicense;
use app\modules\admin\models\FcmNotification;
use app\modules\admin\models\Notification;
use app\modules\admin\models\Services;
use app\modules\admin\models\UserEmergencyContacts;
use app\modules\admin\models\UserWallet;
use app\modules\admin\models\VehicleTypes;
use Exception;
use kartik\mpdf\Pdf; 

class UserWalletController extends BKController
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
                            'add-balance',
                            'transaction-list',
                            'available-balance',



                        ],

                        'allow' => true,
                        'roles' => [
                            '@'
                        ]
                    ],
                    [

                        'actions' => [
                            'add-balance',
                            'transaction-list',
                            'available-balance',



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


    // [doc:start] beforeAction - Before Action
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        if (!\app\modules\billing\services\CustomerWallet::enabled()) {
            Yii::$app->response->data = \app\modules\billing\services\CustomerWallet::unavailable();
            return false;
        }

        return true;
    }
    // [doc:end] beforeAction

    // [doc:start] actionIndex - /api/user-wallet/index - Index
    public function actionIndex()
    {
        $data['details'] =  ['hello'];
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionIndex









    
// [doc:start] actionAddBalance - /api/user-wallet/add-balance - Add Balance
public function actionAddBalance()
{



    $data = [];
    try {
        $post = \Yii::$app->request->post();
        if ($post) {
            $amount  = !empty($post['amount']) ? $post['amount'] : '';
            $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
            $auth = new AuthSettings();
            $user_id = $auth->getAuthSession($headers);
            if (!empty($user_id)) {
                $user = User::find()->where(['id' => $user_id])->one();
                if (!empty($user)) {
                    if(!empty($amount) && $amount>0) {
                        $UserWallet = new  UserWallet();
                        $UserWallet->user_id = $user_id;
                        $UserWallet->amount = $amount;
                        $UserWallet->payment_type = UserWallet::payment_type_credit;
                        $UserWallet->method_reason = "Amount credit ".$amount;
                        $UserWallet->status  = UserWallet::STATUS_SUCCESS;
                        if($UserWallet->save(false)) {
                            $data['status'] = self::API_OK;
                            $data['details'] = $UserWallet->asJson();
                        } else {
                            $data['status'] = self::API_NOK;
                            $data['error'] = 'Wallet added failed';
                        }


                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'minimum required amount 1/-';
                    }




                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = 'Wrong User';
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = "User Not Found";
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = "No data posted.";
        }
    } catch(Exception $e) {
        $data['status'] = self::API_NOK;
        $data['error'] =$e->getMessage();
    }
        return $this->sendJsonResponse($data);


}
// [doc:end] actionAddBalance







// [doc:start] actionAvailableBalance - /api/user-wallet/available-balance - Available Balance
public function actionAvailableBalance()
{

    $data = [];
    try {
        $post = \Yii::$app->request->post();

        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            $user = User::find()->where(['id' => $user_id])->one();
            if (!empty($user)) {
                $availableBalance = UserWallet::availableBalance($user->id);
                $data['status'] = self::API_OK;
                $data['details'] = $availableBalance;


            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = 'Wrong User';
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = "User Not Found";
        }

    } catch(Exception $e) {
        $data['status'] = self::API_NOK;
        $data['error'] =$e->getMessage();
    }
        return $this->sendJsonResponse($data);



}
// [doc:end] actionAvailableBalance


// [doc:start] actionTransactionList - /api/user-wallet/transaction-list - Transaction List
public function actionTransactionList(){

    $data = [];
    try {
        $post = \Yii::$app->request->post();
        $page = !empty($post['page'])?$post['page']:0;
        $start_date = !empty($post['start_date'])?$post['start_date']:'';
        $end_date = !empty($post['end_date'])?$post['end_date']:'';

        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            $user = User::find()->where(['id' => $user_id])->one();
            if (!empty($user)) {
            $user_wallet_query = UserWallet::find()->where(['user_id' => $user_id]);

            if(!empty($start_date) && !empty($end_date)){
                $user_wallet_query->andWhere(['between', 'created_on', $start_date, $end_date]);
            }

                $UserWallet = new ActiveDataProvider([
                    'query' => $user_wallet_query,
                    'sort' => [
                        'defaultOrder' => [
                            'created_on' => SORT_DESC,
                        ],
                    ],
                    'pagination' => [
                        'pageSize' => 20,
                        'page' => $page,
                    ],

                ]);

                if(!empty($UserWallet)){
                    foreach($UserWallet->models as $UserWalletData){
                        $list[] = $UserWalletData->asJson();
                    }
                    if(!empty($list)){
                        $data['status'] = self::API_OK;
                        $data['details'] = $list;

                    }else{
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'Transaction data not found';
                    }

                }else{
                    $data['status'] = self::API_NOK;
                    $data['error'] = 'Transaction data not found';
                }




            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = 'Wrong User';
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = "User Not Found";
        }

    } catch(Exception $e) {
        $data['status'] = self::API_NOK;
        $data['error'] =$e->getMessage();
    }
        return $this->sendJsonResponse($data);


    
}
// [doc:end] actionTransactionList






}
