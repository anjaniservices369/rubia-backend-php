<?php

namespace app\modules\taxicore\rider\controllers;

use app\modules\api\controllers\BKController;
use yii;
use yii\filters\AccessControl;
use yii\filters\AccessRule;
use yii\helpers\ArrayHelper;
use app\modules\auth\services\AuthSettings;
use app\modules\geo\services\DrivingDistance;
use app\modules\payments\services\OpenMoney;
use app\modules\dispatch\services\OrderDispatch;
use yii\data\ActiveDataProvider;
use yii\helpers\Url;
use app\models\User;
use app\modules\admin\models\Auth;
use app\modules\admin\models\WebSetting;
use app\modules\admin\models\AuthSession;
use app\modules\admin\models\Temples;
use app\modules\admin\models\Notification;
use app\modules\admin\models\FamilyDetails;
use app\modules\admin\models\Likes;
use app\modules\admin\models\AvailablePincode;
use app\modules\admin\models\base\RideCommision;
use app\modules\admin\models\base\TaxiRating;
use app\modules\admin\models\Coupon;
use app\modules\admin\models\CouponsApplied;
use app\modules\admin\models\EmergencyContact;
use app\modules\admin\models\FavouriteLocation;
use app\modules\admin\models\FcmNotification;
use app\modules\admin\models\Pincode;
use app\modules\admin\models\RideCompletionLog;
use app\modules\admin\models\RideEarnings;
use app\modules\admin\models\RideRequest;
use app\modules\admin\models\UserWallet;
use app\modules\admin\models\Vehicals;
use app\modules\admin\models\Wallet;
use Exception;
use kartik\mpdf\Pdf;

/**
 * Taxi CUSTOMER app API: /api/taxi-user/*  - the TAXI SERVICE (bike / auto / car) seen from the rider.
 *
 * Book a ride (ride_request), watch its status, pay, rate. An unanswered ride is expired by
 * OrderDispatch::expireIfUnanswered() and by `php yii taxi/expire-stale`.
 */
class TaxiUserController extends BKController
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
                            'check',
                            'index',
                            'my-profile',
                            'verify-otp',
                            'send-otp',
                            'update-lat-lng',
                            'update-profile',
                            'add-family-details',
                            'add-to-fav',
                            'logout',
                            'check-delivery',
                            'profile-detail',
                            'ride-request',
                            'get-vehicles',
                            'auto-assign',
                            'ride-request-detail',
                            'choose-payment-method',
                            'add-to-wallet',
                            'wallet-amount',
                            'list-coupons',
                            'generate-payment-token',
                            'update-payment-status',
                            'my-rides',
                            'my-rides-detail',
                            'add-rating',
                            'my-notification',
                            'clear-notification',
                            'add-to-favourite',
                            'my-favourite-location',
                            'remove-favourite',
                            'add-to-emergency-contact',
                            'my-emergency-contacts',
                            'generate-wallet-token',
                            'wallet-transaction',
                            'cancel-ride',
                            'ride-completion-log',
                            'ride-completion-detail',
                            'refer-user',
                            'invoice',
                            'refer-user',
                            'my-referal'

                        ],

                        'allow' => true,
                        'roles' => [
                            '@'
                        ]
                    ],
                    [

                        'actions' => [
                            'check',
                            'index',
                            'send-otp',
                            'resend-otp',
                            'verify-otp',
                            'my-profile',
                            'update-profile',
                            'add-family-details',
                            'add-to-fav',
                            'check-delivery',
                            'saved-address',
                            'ride-request',
                            'profile-detail',
                            'get-vehicles',
                            'auto-assign',
                            'update-lat-lng',
                            'ride-request-detail',
                            'choose-payment-method',
                            'add-to-wallet',
                            'wallet-amount',
                            'list-coupons',
                            'generate-payment-token',
                            'update-payment-status',
                            'my-rides',
                            'my-rides-detail',
                            'add-rating',
                            'my-notification',
                            'clear-notification',
                            'add-to-favourite',

                            'my-favourite-location',
                            'remove-favourite',
                            'add-to-emergency-contact',
                            'my-emergency-contacts',
                            'generate-wallet-token',
                            'wallet-transaction',
                            'cancel-ride',
                            'ride-completion-log',
                            'ride-completion-detail',
                            'refer-user',
                            'invoice',
                            'refer-user',
                            'my-referal'

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


    // [doc:start] actionIndex - Index
    public function actionIndex()
    {
        $data['details'] =  ['hello'];
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionIndex

    //Check Address or Pin code deliverable or not


    // [doc:start] actionLogout - Logout
    public function actionLogout()
    {
        $data = [];
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        //$userID = Yii::$app->request->post();
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            $model = AuthSession::find()->where(['create_user_id' => $user_id])->one();
            if (!empty($model)) {
                $model->delete();
                if (Yii::$app->user->logout(false)) {
                    $data['status'] = self::API_OK;
                }
                $data['details'] = array("Successfully Logged Out");
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = array();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = ["User Not Found"];
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionLogout

    // [doc:start] actionUpdateLatLng - Update Lat Lng
    public function actionUpdateLatLng()
    {
        $data = [];
        //     $model = new User();
        //    $model->scenario = 'update-latlong';
        $post = \Yii::$app->request->post();
        if ($post) {
            // var_dump(\Yii::$app->request->headers['auth_code']);exit;
            $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
            //$headers = Yii::$app->request->headers;
            $auth = new AuthSettings();
            $user_id = $auth->getAuthSession($headers);

            if (!empty($user_id)) {
                $user = User::find()->where([
                    'id' => $user_id,

                ])->one();
                if ($user) {
                    $user->lat = $post['latitude'];
                    $user->lng = $post['longitude'];
                    //var_dump($model->latitude);exit;
                    if ($user->save(false)) {
                        $data['status'] = self::API_OK;
                        $data['detail'] = $user;
                    } else {
                        $data['status'] = self::API_NOK;
                        // $data['error'] = $user->getErrors();
                        $data['error'] = 'Lat & long not updated';
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
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionUpdateLatLng

    // [doc:start] actionCheck - Check
    public function actionCheck()
    {
        $data = [];

        $headers = getallheaders();
        $auth_code = isset($headers['auth_code']) ? $headers['auth_code'] : null;
        if ($auth_code == null) {
            $auth_code = \Yii::$app->request->get('auth_code');
        }
        if ($auth_code) {
            $auth_session = AuthSession::find()->where([
                'auth_code' => $auth_code,
            ])->one();
            if ($auth_session) {
                $user = $auth_session->createUser;
                $data['status'] = self::API_OK;
                $data['detail'] = $user;
                if (isset($_POST['AuthSession'])) {
                    $auth_session->device_token = $_POST['AuthSession']['device_token'];
                    if ($auth_session->save()) {
                        $data['auth_session'] = Yii::t("app", 'Auth Session updated');
                    } else {
                        $data['error'] = $auth_session->flattenErrors;
                    }
                }
            } else {
                $data['error'] = Yii::t("app", 'session not found');
            }
        } else {
            $data['error'] = Yii::t("app", 'Auth code not found');
            $data['auth'] = isset($auth_code) ? $auth_code : '';
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionCheck

    // [doc:start] actionSendOtp - Send Otp
    public function actionSendOtp()
    {
        return $this->sendJsonResponse(Yii::$app->otp->send(Yii::$app->request->post()));
    }
    // [doc:end] actionSendOtp

    // [doc:start] actionResendOtp - Resend Otp
    public function actionResendOtp()
    {
        return $this->sendJsonResponse(Yii::$app->otp->resend(Yii::$app->request->post()));
    }
    // [doc:end] actionResendOtp

    // [doc:start] actionVerifyOtp - Verify Otp
    public function actionVerifyOtp()
    {
        $data = [];
        $post = Yii::$app->request->post();

        if (!empty($post)) {

            $contact_no = $post['contact_no'];
            $session_code = $post['session_code'];
            $otp_code = $post['otp_code'];

            // OTP check (provider, or the dev-only test code): see OtpService::verify().
                $send_otp = Yii::$app->otp->verify($contact_no, $session_code, $otp_code);
if (($send_otp['Status'] ?? '') == 'Success') {

                $providerId = "phone";

                $number = $post['contact_no'];
                $auth_id = $post['contact_no'];

                $auth = Auth::find()->where([
                    'source' => $providerId,
                    'source_id' => $auth_id,
                ])->one();

                if ($auth) {

                    $user = $auth->user;
                    $user->device_token = ($post['device_token'] ?? '');
                    $user->device_type = ($post['device_type'] ?? 0);
                    Yii::$app->user->login($user);

                    $data['status'] = self::API_OK;
                    $data['details'] = $user;
                    $data['auth_code'] = AuthSession::newSession($user)->auth_code;
                } else {
                    //if($isNewUser == "true"){
                    $check = User::findOne(['username' => $number]);
                    if (empty($check)) {
                        $model = new User();
                        $model->username = $number;
                        $model->contact_no = $number;
                        $model->device_token = ($post['device_token'] ?? '');
                        $model->device_type = ($post['device_type'] ?? 0);
                        $model->referal_code = 'ESYGO' . rand(1234, 9999);
                        $model->user_role =  User::ROLE_USER;

                        if ($model->validate()) {
                            // $model->roles = array($model->user_role);
                            if ($model->save()) {
                                $auth = new Auth();
                                $auth->user_id = $model->id;
                                $auth->source = $providerId;
                                $auth->source_id = $auth_id;
                                if ($auth->save(false)) {
                                    // //Find User 
                                    // $getUser = User::find()->Where(['username' =>$number,'contact_no'=>$number ])->one();
                                    // $data['status'] = self::API_OK; 
                                    // $data['details'] = $getUser;
                                    // $data['auth_code'] = AuthSession::newSession($model)->auth_code;
                                    $user = $auth->user;
                                    $user->device_token = ($post['device_token'] ?? '');
                                    $user->device_type = ($post['device_type'] ?? 0);
                                    Yii::$app->user->login($user);

                                    $data['status'] = self::API_OK;
                                    $data['details'] = $user;
                                    $data['auth_code'] = AuthSession::newSession($user)->auth_code;



                                    /* $emailtemplate = new EmailTemplate();
                                        $mailcontent = [
                                        'USER' => $model->first_name,
                                        'type_id' => EmailTemplate::SIGNUP,
                                        'EMAIL' => $model->email,
                                        'USERNAME' => $model->username,
                                        ];
                                        $emailtemplate->sendEmail($mailcontent); */

                                    //Check is cashback enable or not
                                    /* $setting = new WebSetting();
                                        $enable_signup_bonus = $setting->getSettingBykey('enable_signup_bonus');
                                        $signup_bonus = $setting->getSettingBykey('signup_bonus');
                    
                                        if ($enable_signup_bonus == 1) {
                                            $transaction = new CashbackTransaction();
                                            $transaction->reference_id = $transaction->getToken(12);
                                            $transaction->user_id = $model->id;
                                            $transaction->model_type = get_class($model);
                                            $transaction->payment_type = 'Signup Bonus';
                                            $transaction->amount = $signup_bonus;
                                            $transaction->created_date = date('Y-m-d');
                                            $transaction->payment_status = CashbackTransaction::STATUS_APPROVED;
                    
                                            //$transaction->save();
                                            if (!$transaction->save(false)) {
                    
                                                Yii::error('wallet entry not saved: ' . json_encode($transaction->getErrors()), 'api');
                                throw new \RuntimeException('Could not save the entry');
                                            }  
                                        } */
                                } else {
                                    $data['status'] = self::API_NOK;
                                    $data['error'] = $auth->getErrors();
                                }
                            } else {
                                $data['status'] = self::API_NOK;
                                $data['error'] = $model->getErrors();
                            }
                        } else {
                            $data['status'] = self::API_NOK;
                            $data['error'] = $model->getErrors();
                        }
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = Yii::t("app", 'This number is already registered with us.Please Contact Support');
                    }
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t("app", "OTP failed");
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = Yii::t("app", "No  Data Posted");
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionVerifyOtp

    // [doc:start] actionMyProfile - My Profile
    public function actionMyProfile()
    {
        $data = [];
        $post = Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            $model = User::findOne($user_id);
            $d['user_details'] = $model->asJson();
            $data['status'] = self::API_OK;
            $data['details'] = $d;
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = Yii::t("app", "User Not Found");
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionMyProfile

    // [doc:start] actionUpdateProfile - Update Profile
    public function actionUpdateProfile()
    {
        $data = [];
        $param = [];
        $post = Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            if (!empty($post)) {
                $model = User::find()->where(['id' => $user_id])->andWhere(['user_role' => User::ROLE_USER])->one();

                if (!empty($model)) {
                    $model->first_name =  $post['User']['first_name'];
                    $model->last_name =  $post['User']['last_name'];
                    $model->gender =  $post['User']['gender'];
                    $model->date_of_birth =  $post['User']['date_of_birth'];
                    if (!empty($post['User']['profile_image'])) {
                        // $profile_image = $model->profileImage($post['User']['profile_image'], $model->first_name);
                        $model->profile_image = $post['User']['profile_image'];
                    }
                    if (!empty($post['User']['email'])) {
                        $model->email = $post['User']['email'];
                    }
                    $model->username = $model->email;

                    if ($model->save(false)) {

                        $data['status'] = self::API_OK;
                        $data['details'] = $model->asJson();
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = Yii::t("app", 'Something Went Wrong');
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = Yii::t("app", "User Not Found");
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t("app", "Data Not Posted");
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = Yii::t("app", "User Not Found");
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionUpdateProfile



    // Add Profile detail on boarding

    // Update add Profile Detail
    // public function actionProfileDetail()
    // {
    //     $data = [];
    //     $param = [];
    //     $post = Yii::$app->request->post();
    //     $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
    //     $auth = new AuthSettings();
    //     $user_id = $auth->getAuthSession($headers);
    //     if (!empty($user_id)) {
    //         if (!empty($post)) {
    //             $user = User::find()->where(['id' => $user_id])->andWhere(['user_role' => User::ROLE_USER])->one();
    //             $check = User::find()->where(['email' => $post['email']])->one();
    //             if (!empty($check)) {
    //                 $data['status'] = self::API_NOK;
    //                 $data['error'] = "Email Alredy Used";
    //                 return $this->sendJsonResponse($data);
    //             }
    //             if (!empty($user)) {
    //                 $user->first_name = !empty($post['first_name']) ? $post['first_name'] : $error = "Enter First Name";
    //                 $user->last_name = !empty($post['last_name']) ? $post['last_name'] : $error = "Enter Last Name";
    //                 $user->email = !empty($post['email']) ? $post['email'] : $error = "Enter Email Name";
    //                 $user->date_of_birth = !empty($post['date_of_birth']) ? $post['date_of_birth'] : $error = "Enter DOB";
    //                 $user->gender = !empty($post['gender']) ? $post['gender'] : $error = "Enter Gender";

    //                 if ($user->save(false)) {

    //                     // $param['name'] = $user->first_name . $user->last_name;
    //                     // $param['display_name'] = $user->first_name;
    //                     // $param['contact_type'] = "Customer";
    //                     // $param['primary_contact'] = $user->first_name;
    //                     // $param['email_id'] = $user->email;
    //                     // $param['mobile_number'] = $user->contact_no;
    //                     // $createContact = (new OpenMoney())->CreateContact([$param], $user_id);
    //                     $user->form_steps = User::PERSONAL_DETAIL;
    //                     $user->save(false);
    //                     $data['status'] = self::API_OK;
    //                     $data['detail'] = "Data Saved";
    //                 } else {
    //                     $data['status'] = self::API_NOK;
    //                     $data['message'] = $error;
    //                 }
    //             } else {
    //                 $data['status'] = self::API_NOK;
    //                 $data['message'] = Yii::t("app", "User not found");
    //             }
    //         } else {
    //             $data['status'] = self::API_NOK;
    //             $data['message'] = Yii::t("app", "No Data Post");
    //         }
    //     } else {
    //         $data['status'] = self::API_NOK;
    //         $data['error'] = "No User found.";
    //     }
    //     return $this->sendJsonResponse($data);
    // }

    // Ride Request



    // [doc:start] actionGetVehicles - Get Vehicles
    public function actionGetVehicles()
    {
        $data = [];
        $post = Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        try {


            $user_id = $auth->getAuthSession($headers);

            $pickup_pincode = $post['pickup_pincode'];
            $drop_pincode = $post['drop_pincode'];
            $pickup_latitude = $post['pickup_latitude'];
            $pickup_longitude = $post['pickup_longitude'];
            $drop_latitude = $post['drop_latitude'];
            $drop_longitude = $post['drop_longitude'];
            $vehicle_type = !empty($post['vehicle_type']) ? $post['vehicle_type'] : '';

            $coupon_code = !empty($post['coupon_code']) ? $post['coupon_code'] : "";

            if (!empty($user_id)) {
                $pickupPincode = Pincode::find()->where(['name' => $pickup_pincode])->andWhere(['status' => Pincode::STATUS_ACTIVE])->one();
                $dropPincode = Pincode::find()->where(['name' => $drop_pincode])->andWhere(['status' => Pincode::STATUS_ACTIVE])->one();
                if (!empty($pickupPincode) && !empty($dropPincode)) {
                    if ($pickupPincode->city_id == $dropPincode->city_id) {
                        $city_id = $pickupPincode->city_id;
                    }
                }
                $getVehicles = (new RideRequest())->getVehicles($pickup_pincode, $drop_pincode);



                if (is_array($getVehicles)) {



                    $vehicalName = Vehicals::find()->where(['in', 'id', $getVehicles])->andWhere(['vehicle_type' => $vehicle_type])->andWhere(['status' => Vehicals::STATUS_ACTIVE])->all();
                    if (!empty($vehicalName)) {
                        foreach ($vehicalName as $vehicalName_data) {
                            $list[] = $vehicalName_data->asJson($pickup_latitude, $pickup_longitude, $drop_latitude, $drop_longitude, $coupon_code, $user_id, $city_id);
                        }

                        if (!empty($list)) {
                            $data['status'] = self::API_OK;
                            $data['details'] = $list;
                        } else {
                            $data['status'] = self::API_NOK;
                            $data['error'] = "vehicle data not found.";
                        }
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = "vehicle data not found.";
                    }



                    // return  $vehicalName;
                    // exit;
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = $getVehicles;
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = "No User found.";
            }
        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionGetVehicles


    // Ride Request

    // [doc:start] actionRideRequest - The rider asks for a taxi (bike / auto / car). Creates a ride_request and tells the rider to wait.
    /**
     * The rider asks for a taxi (bike / auto / car). Creates a ride_request and tells the rider to wait.
     *
     * POST /api/taxi-user/ride-request   vehical_id, ride_fare, pickup_* and drop_* (address, pincode, lat, lng),
     *                                    payment_method, cgst, sgst, coupon_code (optional)
     * The ride starts as "New Request"; the dispatcher (OrderDispatch) then offers it to nearby taxi drivers. The city
     * is found from the pickup pincode. NOTE: the fare is sent by the app; it is re-computed at completion
     * (TaxiController::actionChangeStatus) from real distance and time.
     */
    public function actionRideRequest()
    {
        $data = [];
        $post = Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        // WHO IS CALLING: the app sends its login code (auth_code); it is turned into a user id here. A blocked, inactive
        // or logged-out account gets no id.
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);

        try {


            if (!empty($user_id)) {
                if (!empty($post)) {
                    // Every field below is required; a missing one is refused instead of being stored as an error text.
                    foreach (['vehical_id', 'ride_fare', 'pickup_address', 'pickup_pincode', 'pickup_latitude', 'pickup_longitude',
                              'drop_address', 'drop_pincode', 'drop_latitude', 'drop_longitude', 'payment_method', 'cgst', 'sgst'] as $field) {
                        if (!isset($post[$field]) || $post[$field] === '') {
                            return $this->sendJsonResponse(['status' => self::API_NOK, 'error' => $field . " can't be empty"]);
                        }
                    }
                    if ((float) $post['ride_fare'] <= 0) {
                        return $this->sendJsonResponse(['status' => self::API_NOK, 'error' => 'Invalid ride fare']);
                    }
                    // BUILD THE RIDE from the posted values (all required fields were checked just above).
                    // The module's own service area (when one was saved for the pickup's city): outside it nobody is served.
                    $earlyModule = \app\modules\geo\services\ServiceModules::ofVehicle((int) $post['vehical_id']);
                    $earlyCity = \app\modules\geo\services\MapDesigner::cityOfPincode($earlyModule, $post['pickup_pincode']);
                    if ($earlyCity) {
                        $earlyArea = \app\modules\geo\services\ServiceModules::boundary($earlyModule, $earlyCity);
                        if ($earlyArea && !\app\modules\geo\services\ServiceModules::inside((float) $post['pickup_latitude'], (float) $post['pickup_longitude'], $earlyArea)) {
                            return $this->sendJsonResponse(['status' => self::API_NOK, 'error' => Yii::t('app', 'Sorry, we do not serve this pickup location yet.')]);
                        }
                    }
                    $rideRequest = new RideRequest();
                    $rideRequest->user_id = $user_id;
                    $rideRequest->vehical_type = isset($post['vehical_id']) ? (int)$post['vehical_id'] : $error = "Vehical Id can't be empty";
                    $rideRequest->estimated_ride_fare = isset($post['ride_fare']) ? (float)$post['ride_fare'] : $error = "Ride fare can't be empty";
                    $rideRequest->total_fare = isset($post['ride_fare']) ? ceil((float)$post['ride_fare']) : $error = "Ride fare can't be empty";
                    $rideRequest->pickup_address = isset($post['pickup_address']) ? $post['pickup_address'] : $error = "Pickup Address can't be empty";
                    $rideRequest->pickup_pincode = isset($post['pickup_pincode']) ? $post['pickup_pincode'] : $error = "Pickup Pincode can't be empty";
                    $rideRequest->pickup_latitude = isset($post['pickup_latitude']) ? $post['pickup_latitude'] : $error = "Pickup latitude can't be empty";
                    $rideRequest->pickup_longitude = isset($post['pickup_longitude']) ? $post['pickup_longitude'] : $error = "Pickup longitude can't be empty";
                    $rideRequest->drop_address = isset($post['drop_address']) ? $post['drop_address'] : $error = "Drop Address can't be empty";
                    $rideRequest->drop_pincode = isset($post['drop_pincode']) ? $post['drop_pincode'] : $error = "Drop Pincode can't be empty";
                    $rideRequest->drop_latitude = isset($post['drop_latitude']) ? $post['drop_latitude'] : $error = "Drop Latitude can't be empty";
                    $rideRequest->drop_longitude = isset($post['drop_longitude']) ? $post['drop_longitude'] : $error = "Drop longitude can't be empty";
                    $rideRequest->payment_method = isset($post['payment_method']) ? $post['payment_method'] : $error = "Payment Method can't be empty";
                    $rideRequest->cgst = isset($post['cgst']) ? $post['cgst'] : $error = "cgst can't be empty";
                    $rideRequest->sgst = isset($post['sgst']) ? $post['sgst'] : $error = "sgst can't be empty";
                    $rideRequest->coupon_code = !empty($post['coupon_code']) ? $post['coupon_code'] : "";
                    // $rideRequest->estimated_time = isset($post['estimated_time']) ? $post['estimated_time'] : $error = "Estimated Time can't be empty";
                    // The OTP the rider tells the driver to start the ride (verified in TaxiController::actionVerifyRideOtp).
                    $rideRequest->otp = rand(1234, 9999);

                    // New requests are picked up by the dispatcher and offered to drivers.
                    $rideRequest->status = RideRequest::STATUS_NEW_REQUEST;

                    if ($rideRequest->save(false)) {
                        if (!empty($post['coupon_code'])) {
                            $applyCoupon =  $rideRequest->applyCoupon($post['coupon_code'], $rideRequest->id);
                            //    var_dump($applyCoupon);exit;
                        }
                        $distance = (new DrivingDistance())->getDrivingDistanceGoogleMap($rideRequest->pickup_latitude, $rideRequest->pickup_longitude, $rideRequest->drop_latitude, $rideRequest->drop_longitude);
                        //    var_dump( $distance["distance"]);
                        $rideRequest->estimated_distance =  $distance["distvalue"] / 1000;
                        $rideRequest->estimated_time =  $distance["time"];
                        // The city (and so the rate card and commission) comes from the pickup pincode; unknown pincode = city 0.
                        // Each module (bike, auto, taxi) has its own map: the city comes from the pincode in THIS ride's module.
                        $rideRequest->city_id = \app\modules\geo\services\MapDesigner::cityOfPincode(\app\modules\geo\services\ServiceModules::ofVehicle($rideRequest->vehical_type), $rideRequest->pickup_pincode) ?: 0;

                        $rideRequest->save(false);
                        $title = "Ride Requested Succesfully";
                        $body = "Your ride request has been created please wait until our driver accepts the ride";
                        $send_noti = Yii::$app->push->notifyUser($rideRequest->id, $user_id, $title, $body);
                        // var_dump($send_noti);exit;
                        $data['status'] = self::API_OK;
                        $data['details'] = $rideRequest->asJson();
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = $error;
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = Yii::t("app", "No Data Post");
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = "No User found.";
            }
        // Anything unexpected is answered as NOK (the text is cleaned of SQL/stack details by sendJsonResponse).
        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }


        // REPLY: every path above ends here with {status: OK|NOK, details|error}.
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionRideRequest

    // Get Vehicales
    // [doc:start] actionAutoAssign - Auto Assign
    public function actionAutoAssign($ride_id)
    {
        $data = [];
        $post = Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        // $ride_id = $post['ride_id'];

        if (!empty($user_id)) {
            $rideRequest = RideRequest::find()->where(['id' => $ride_id])->one();

            if (!empty($rideRequest)) {
                $autoAssign = new OrderDispatch();
                $assign = $autoAssign->assignAuto($ride_id);
                // var_dump($assign);exit;
                if ($assign['status'] == 'OK') {
                    $data['status'] = self::API_OK;
                    $data['details'] = "Driver assigned successfully";
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = $assign['error'];
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = "Invalid Ride Id.";
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = "No User found.";
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionAutoAssign


    // Ride request detail


    // [doc:start] actionRideRequestDetail - Ride Request Detail
    public function actionRideRequestDetail($rideRequestId)
    {
        $data = [];
        $post = Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);

        if (!empty($user_id)) {
            try {
                $rideRequest = RideRequest::find()->where(['id' => $rideRequestId])->one();
                if (!empty($rideRequest)) {
                    // A ride nobody accepted within the offer timeout is cancelled here (see OrderDispatch).
                    Yii::$app->orderDispatch->expireIfUnanswered($rideRequest);
                    $data['status'] = self::API_OK;
                    $data['details'] = $rideRequest->asJson();
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = "Invalid Id.";
                }
            } catch (Exception $e) {
                return $e->getMessage();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = "No User found.";
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionRideRequestDetail



    // Choose Payment Method


    // [doc:start] actionChoosePaymentMethod - Choose Payment Method
    public function actionChoosePaymentMethod($rideRequestId)
    {
        $data = [];
        $post = Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        $payment_method = $post['payment_method'];
        if (!empty($user_id)) {
            try {
                $rideRequest = RideRequest::find()->where(['id' => $rideRequestId])->one();
                if (!empty($rideRequest)) {
                    if ($post['payment_method'] == RideRequest::PAYMENT_METHOD_WALLET && !\app\modules\billing\services\CustomerWallet::enabled()) {
                        return $this->sendJsonResponse(\app\modules\billing\services\CustomerWallet::unavailable());
                    }
                    if ($post['payment_method'] == RideRequest::PAYMENT_METHOD_WALLET) {
                        // the customer's wallet is the customer app's: asked through the gateway
                        $walletAmount = (float) \app\modules\customerapi\services\CustomerClient::walletBalance($user_id)['balance'];
                        if ($walletAmount < $rideRequest->estimated_ride_fare) {
                            $data['status'] = self::API_NOK;
                            $data['details'] = "Wallet Amount Is Less Than Estimated Price Please Add Amount In Your Wallet";
                            return $this->sendJsonResponse($data);
                        } else {
                            $rideRequest->payment_method = $payment_method;
                        }
                    } else {
                        $rideRequest->payment_method = $payment_method;
                    }
                    $rideRequest->payment_status = RideRequest::PAYMENT_STATUS_PENDING;
                    if ($rideRequest->save(false)) {
                        $data['status'] = self::API_OK;
                        $data['details'] = "Payment Method Updated Successfully";
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = "Data not saved";
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = "Invalid Id.";
                }
            } catch (Exception $e) {
                return $e->getMessage();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = "No User found.";
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionChoosePaymentMethod

    // Ride request detail

    //  Add Balance to Wallet

    // [doc:start] actionAddToWallet - Add To Wallet
    public function actionAddToWallet()
    {
        $data = [];
        $post = Yii::$app->request->post();

        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);

        /* Banners */
        $payment_token = $post['payment_token'];

        if (!empty($user_id)) {
            if (!empty($payment_token)) {

                $generateToken = (new OpenMoney())->paymentStatus($payment_token);
                $decodeGenerateToken = json_decode($generateToken);
                // var_dump($decodeGenerateToken);exit;
                if ($decodeGenerateToken->status == "captured") {
                    $wallet = new Wallet();
                    $wallet->user_id = $user_id;
                    $wallet->amount = (float)$decodeGenerateToken->amount;
                    $wallet->method_reason = "Added To Wallet";
                    $wallet->payment_type = Wallet::STATUS_CREDITED;
                    $wallet->is_reffred = Wallet::NOT_REFFER_BONUS;
                    $wallet->status = Wallet::STATUS_ACTIVE;
                    if (!empty($wallet->save(false))) {
                        $data['status'] = self::API_OK;
                        $data['detail'] = $wallet->asJson();
                        $data['open_money'] = $decodeGenerateToken;
                    } else {
                        $data['status'] = self::API_OK;
                        $data['detail'] = $wallet->getErrors();
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = $decodeGenerateToken;
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = "Enter Payment tokens";
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = "User Not Found";
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionAddToWallet
    // Available Amount
    // [doc:start] actionGenerateWalletToken - Generate Wallet Token
    public function actionGenerateWalletToken()
    {
        $data = [];
        $post = Yii::$app->request->post();

        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);

        /* Banners */
        if (!empty($user_id)) {
            $user = User::find()->where(['id' => $user_id])->one();

            $dd['final_price'] = $post["amount"];
            $dd['contact_no'] = !empty($user->contact_no) ? $user->contact_no : "999999999";
            $dd['email_id'] = !empty($user->email) ? $user->email : "test@gmail.com";


            $generateToken = (new OpenMoney())->generateToken($dd);

            $decodeToken = json_decode($generateToken);
            $data['status'] = self::API_OK;
            $data['detils'] = $decodeToken;
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = "User Not Found";
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionGenerateWalletToken

    // 
    // [doc:start] actionWalletAmount - Wallet Amount
    public function actionWalletAmount()
    {
        $data = [];
        $post = Yii::$app->request->post();

        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);

        /* Banners */
        if (!empty($user_id)) {
            $walletAmount = (new Wallet())->getAvailableWallet($user_id);

            if (!empty($walletAmount)) {
                $data['status'] = self::API_OK;
                $data['details'] =  $walletAmount;
            } else {
                $data['status'] = self::API_NOK;
                $data['detail'] = "Wallet not exsts";
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = "User Not Found";
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionWalletAmount



    // Apply Coupon

    // [doc:start] actionApplyCoupon - Apply Coupon
    public function actionApplyCoupon()
    {
        $data = [];
        $post = Yii::$app->request->post();
        $headers = isset(Yii::$app->request->headers['auth_code']) ? Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            $rideRequest = RideRequest::find()->Where(['id' => $post['ride_id']])->one();

            // the coupon must be one of the ride's own service
            $couponModule = $rideRequest ? \app\modules\geo\services\ServiceModules::ofVehicle($rideRequest->vehical_type) : 0;
            $globalCoupon = Coupon::find()
                ->Where(['status' => Coupon::STATUS_ACTIVE, 'code' => $post['coupon_code'], 'coupon.module' => $couponModule])
                ->one();
            if (empty($globalCoupon)) {
                $data['status'] = self::API_NOK;
                $data['error'] = "Coupon does not exist";
                return $this->sendJsonResponse($data);
            } else {
                //check for coupon Global or Store wise
                if ($globalCoupon['is_global'] == 1 || $globalCoupon['is_global'] == 0) {
                    $coupon = $globalCoupon;
                }
            }
            if ($coupon['min_ride_amount'] <= $rideRequest['estimated_ride_fare']) {
                if (!empty($coupon) && !empty($rideRequest)) {
                    // Check User already used or not
                    $check_apply1 = CouponsApplied::find()->where(['ride_id' => $post['ride_id']])
                        ->one();

                    if (empty($check_apply1)) {
                        // Check User already used or not
                        $check_user_count = CouponsApplied::find()->where([
                            'coupon_id' => $coupon['id'],
                            'user_id' => $rideRequest['user_id'],
                        ])->count();

                        $check_coupon_count = CouponsApplied::find()->where(
                            [
                                'coupon_id' => $coupon['id'],
                            ]
                        )->count();

                        //  echo  $check_user_count->createCommand()->getRawSql();exit;
                        //  var_dump( (int)$check_coupon_count < (int)$coupon->max_use_of_coupon);exit;
                        $today = Date('Y-m-d');
                        if ((int) $check_user_count < (int) $coupon->max_use && (int) $check_coupon_count < (int) $coupon->max_use_of_coupon) {
                            //Apply Discount to Cart
                            //$discount = ($coupon->discount / 100) * $cart->amount;
                            //       if($today >= $coupon['end_date'] && $today <= $coupon['start_date'] ){
                            $check_apply = new CouponsApplied();
                            $check_apply->ride_id = $rideRequest['id'];
                            $check_apply->coupon_id = $coupon['id'];
                            $check_apply->status = CouponsApplied::STATUS_ACTIVE;

                            if ($check_apply->save(false)) {
                                //Calculate Coupon Discount

                                if ($coupon['type_id'] == Coupon::PERCENTAGE) {
                                    $amt = ($coupon['discount'] / 100) * $rideRequest->estimated_ride_fare;
                                    if ($amt >= $coupon['max_discount']) {
                                        // $voucher_amount = $coupon['max_discount'];
                                        $voucher_amount = ($coupon['max_discount'] / 100) * $rideRequest->estimated_ride_fare;
                                    } else {
                                        $voucher_amount = $amt;
                                    }
                                } else {
                                    $voucher_amount =  $coupon['max_discount'];
                                }


                                //Update Cart
                                $rideRequest->coupon_applied_id = $check_apply->id;
                                $rideRequest->coupon_code = $post['coupon_code'];
                                $rideRequest->coupon_discount = $voucher_amount;
                                $rideRequest->save(false);

                                $data['status'] = self::API_OK;
                                //$data['discount'] = $coupon->max_discount;
                                $data['coupon_apply_id'] = $check_apply->id;
                                $data['coupon_discount'] = $voucher_amount;
                                $data['coupon_details'] = $coupon;
                            }
                            // }else{
                            //     $data['status'] = self::API_NOK;
                            //     $data['details'] = array("Coupon Expired");
                            // }
                        } else {
                            $data['status'] = self::API_NOK;
                            $data['error'] = "Reached maximum usage of coupon";
                        }
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'Coupon Already applied or this coupon does not work';
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = 'This coupon does not work for this store';
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = 'For applying coupon your cart value should be ₹' . $coupon['min_ride_amount'];
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['details'] = 'NO session found';
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionApplyCoupon


    // List Coupon

    // [doc:start] actionListCoupons - List Coupons
    public function actionListCoupons()
    {
        $data = [];
        $list = [];
        $listStore = [];
        $listCity = [];
        $post = Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            $globalCoupons = Coupon::find()->Where(['status' => Coupon::STATUS_ACTIVE])
                ->andWhere(['is_global' => 1])
                ->all();



            if (!empty($globalCoupons)) {
                foreach ($globalCoupons as $globalCoupon) {
                    $list[] = $globalCoupon;
                }
            }



            if (!empty($list)) {
                $data['status'] = self::API_OK;
                $data['details'] = $list;
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t("app", "No Coupon found");
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = Yii::t("app", "No User found");
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionListCoupons

    // Generate Access Token

    // [doc:start] actionGeneratePaymentToken - Generate Payment Token
    public function actionGeneratePaymentToken()
    {
        $data = [];
        $list = [];
        $listStore = [];
        $listCity = [];
        $dd =  [];
        $post = Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);

        if (!empty($user_id)) {
            try {
                $ride_id = $post['ride_id'];
                $rideRequest = RideRequest::find()->where(['id' => $ride_id])->one();

                if (!empty($rideRequest)) {
                    $dd['final_price'] = $rideRequest->final_price;
                    $dd['contact_no'] = !empty($rideRequest->customer['phone']) ? $rideRequest->customer['phone'] : "999999999";
                    $dd['email_id'] = !empty($rideRequest->customer['email']) ? $rideRequest->customer['email'] : "test@gmail.com";


                    $generateToken = (new OpenMoney())->generateToken($dd);

                    $decodeToken = json_decode($generateToken);
                    $rideRequest->payment_token = $decodeToken->id;
                    $rideRequest->save(false);

                    $title = "Payment Processing";
                    $body = "Your Payment For Ride Id-" . $rideRequest->id . " Is in process by  " . ($rideRequest->customer['name'] ?: $rideRequest->customer['phone']);
                    $send_noti = Yii::$app->push->notifyDriver('', $rideRequest->driver_id, $title, $body);
                    // var_dump($decodeToken->id);exit;
                    $data['status'] = self::API_OK;
                    $data['detils'] = $decodeToken;
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = Yii::t("app", "No Ride Found");
                }
            } catch (Exception $e) {
                return $e->getMessage();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = Yii::t("app", "No User found");
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionGeneratePaymentToken

    // Update Payment Status

    // [doc:start] actionUpdatePaymentStatus - Update Payment Status
    public function actionUpdatePaymentStatus()
    {
        $data = [];
        $list = [];
        $listStore = [];
        $listCity = [];
        $dd =  [];
        $post = Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        $settings = new WebSetting();
        $cgst = $settings->getSettingBykey('cgst');
        $sgst = $settings->getSettingBykey('sgst');
        if (!empty($user_id)) {
            try {
                $ride_id = $post['ride_id'];
                $payment_token = $post['payment_token'];


                if (!empty($payment_token)) {
                    $generateToken = (new OpenMoney())->paymentStatus($payment_token);
                    // var_dump($generateToken);exit;
                    $decodeGenerateToken = json_decode($generateToken);
                    if (!empty($decodeGenerateToken->status)) {
                        $RideRequest = RideRequest::find()->where(['id' => $ride_id])->one();
                        if (!empty($RideRequest)) {
                            if ($decodeGenerateToken->status == "captured") {
                                $RideRequest->status = RideRequest::STATUS_RIDE_COMPLETED_PAID;
                                $RideRequest->payment_status = RideRequest::PAYMENT_STATUS_PAID;

                                $title = "Payment Status";
                                $body = "Your Payment For Ride Id-" . $RideRequest->id . " Is Sucesfull";
                            } else if ($decodeGenerateToken->status == "failed") {
                                $RideRequest->status = RideRequest::STATUS_RIDE_COMPLETED;
                                $RideRequest->payment_status = RideRequest::PAYMENT_STATUS_FAILED;
                                $title = "Payment Status";
                                $body = "Your Payment For Ride Id-" . $RideRequest->id . " Is Failed";
                            } else {
                                $data['status'] = self::API_NOK;
                                $data['error'] = Yii::t("app", "Payment Status Not Found");
                            }
                            $send_noti = Yii::$app->push->notifyUser('', $user_id, $title, $body);

                            if ($RideRequest->save(false)) {

                                if ($RideRequest->status == RideRequest::STATUS_RIDE_COMPLETED_PAID) {
                                    $rideEarnings = RideEarnings::find()->where(['ride_id' => $ride_id])->andWhere(['status' => RideEarnings::STATUS_PENDING])->one();
                                    $final_lat = isset($post['final_lat']) ? $post['final_lat'] : $RideRequest->drop_latitude;
                                    $final_lng = isset($post['final_lng ']) ? $post['final_lng'] : $RideRequest->drop_longitude;
                                    if (!empty($rideEarnings)) {
                                        $rideCommission = RideCommision::find()->where(['city_id' => $RideRequest->city_id, 'vehicle_id' => \app\modules\geo\services\ServiceModules::ofVehicle($RideRequest->vehical_type), 'status' => RideCommision::STATUS_ACTIVE])->one();
                                        if (!empty($rideCommission)) {
                                            $commisionPercentage = $rideCommission->commision;
                                        } else {
                                            $commisionPercentage = 0;
                                        }
                                        $adminEarnings = ($RideRequest->final_price * $commisionPercentage) / 100;
                                        $driverEarnings = $RideRequest->final_price - $adminEarnings;
                                        $cgstAmount = ($driverEarnings * $cgst) / 100;
                                        $sgstAmount = ($driverEarnings * $sgst) / 100;
                                        $adminEarningsnew = $adminEarnings + $cgstAmount + $sgstAmount;
                                        $rideEarnings->total_ditance_km = $RideRequest->final_distance;
                                        $rideEarnings->admin_earning  = $adminEarningsnew;
                                        $rideEarnings->total_amount  = $RideRequest->final_price;
                                        $rideEarnings->driver_earning  = $driverEarnings - $cgstAmount - $sgstAmount;
                                        $rideEarnings->status  = RideEarnings::STATUS_APPROVED;
                                        if ($rideEarnings->save(false)) {
                                            $title = "Payment Status";
                                            $body = "Your Payment For Ride Id-" . $RideRequest->id . " Is Sucesfull !! You Have Earned Rs. " . $driverEarnings;
                                            $send_noti = Yii::$app->push->notifyDriver('', $RideRequest->driver_id, $title, $body);
                                        } else {
                                            $data['status'] = self::API_NOK;
                                            $data['error'] = Yii::t("app", "Failed To Save Ride Earning");
                                        }
                                        $rideCompletionLog = (new RideCompletionLog())->saveCompletionLog($RideRequest->id, $final_lat, $final_lng, $RideRequest->updated_on, $RideRequest->ride_start_time, $RideRequest->final_price, $RideRequest->final_distance);
                                        $data['status'] = self::API_OK;
                                        $data['details'] = Yii::t("app", "Payment Status Updated");
                                        $data['payment_status'] =  $RideRequest->payment_status;
                                        $data['open_money'] =  $decodeGenerateToken;
                                    } else {
                                        $data['status'] = self::API_NOK;
                                        $data['error'] = Yii::t("app", "Data Not Saved");
                                    }
                                } else {
                                    $data['status'] = self::API_NOK;
                                    $data['error'] = Yii::t("app", "No Ride Found");
                                }
                            } else {
                                $data['status'] = self::API_NOK;
                                $data['error'] = Yii::t("app", "data not saved");
                            }
                        } else {
                            $data['status'] = self::API_NOK;
                            $data['error'] = Yii::t("app", "Invalid Ride id");
                        }
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = Yii::t("app", "Failed to generate payment status");
                    }
                }
            } catch (Exception $e) {
                $data['status'] = self::API_NOK;
                $data['error'] = $e->getMessage();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = Yii::t("app", "No User found");
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionUpdatePaymentStatus


    // My Rides
    // [doc:start] actionMyRides - My Rides
    public function actionMyRides()
    {
        $data = [];
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        $post = Yii::$app->request->post();

        $start_date = $post['start_date'];
        $end_date = $post['end_date'];
        if (!empty($user_id)) {

            try {
                $page = isset($post['page']) ? $post['page'] : 0;


                $query = RideRequest::find()->Where(['user_id' => $user_id]);

                if (!empty($start_date) && !empty($end_date)) {
                    $query->andWhere(['between', 'updated_on', $start_date, $end_date]);
                }


                // if ($post['status'] == RideRequest::STATUS_RIDE_COMPLETED) {




                // } else if ($post['status'] == RideRequest::STATUS_CANCEL_BY_USER) {
                //     $query = RideRequest::find()    
                //         ->Where(['user_id' => $user_id])
                //         ->andWhere(['between', 'updated_on', $post['start_date'], $post['end_date']])->andWhere(['status' => RideRequest::STATUS_CANCEL_BY_USER]);
                // } else if ($post['status'] == RideRequest::STATUS_RIDE_COMPLETED_PAID) {
                //     $query = RideRequest::find()
                //         ->Where(['user_id' => $user_id])
                //         ->andWhere(['between', 'updated_on', $post['start_date'], $post['end_date']])->andWhere(['status' => RideRequest::STATUS_RIDE_COMPLETED_PAID]);
                // } else {
                //     $query = RideRequest::find()
                //         ->Where(['user_id' => $user_id])

                //         ->andWhere(['between', 'updated_on', $post['start_date'], $post['end_date']])
                //         ->andWhere(
                //             [
                //                 'and',
                //                 ['!=', 'status', RideRequest::STATUS_NEW_REQUEST],

                //             ]
                //         );
                // }
                $driver_orders = new ActiveDataProvider([
                    'query' => $query,
                    'sort' => [
                        'defaultOrder' => [
                            'id' => SORT_DESC,
                        ],
                    ],
                    'pagination' => array(
                        'page' => $page,
                        'pageSize' => 20,
                    ),
                ]);
                if (!empty($driver_orders->getModels())) {
                    foreach ($driver_orders->getModels() as $driver_order) {

                        $list[] = $driver_order->MyRideUserJson();
                    }
                    $data['status'] = self::API_OK;

                    $data['details'] = $list;
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = "No Rides found";
                }
            } catch (Exception $e) {

                $data['status'] = self::API_NOK;
                $data['error'] = $e->getMessage();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = "No user found";
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionMyRides

    // Ride Detail

    // [doc:start] actionMyRidesDetail - My Rides Detail
    public function actionMyRidesDetail($ride_id)
    {
        $data = [];
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        $post = Yii::$app->request->post();


        // var_dump($driver_id);exit; 
        if (!empty($user_id)) {

            try {

                // var_dump($post['status']);exit;


                $query = RideRequest::find()
                    ->Where(['user_id' => $user_id])->andWhere(['id' => $ride_id])->one();



                // ->andWhere(['gc_orders.delivery_date' => $today]);


                if (!empty($query)) {
                    $data['status'] = self::API_OK;
                    $data['detail'] = $query->asRideRequestDetailJsonUser();
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = "No Rides found";
                }
            } catch (Exception $e) {
                return $e->getMessage();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = "No user found";
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionMyRidesDetail

    // Refer User

    // [doc:start] actionReferUser - Refer User
    public function actionReferUser($code)
    {

        // dd('dasds');

        $post = Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);

        $data = [];
        // Check mac id exist or not 

        if ((!User::isAdmin()) && !empty($user_id)) {

            $profile = User::find()->where([
                'id' => $user_id,

                'referal_id' => 0
            ])->one();

            // var_dump($profile->createCommand()->getRawSql());exit;

            if ($profile) {



                $referUser = User::find()->Where(['referal_code' => $code])->one();

                if (!empty($referUser && $referUser->id != $user_id)) {

                    $profile->referal_id = $referUser->id;

                    // referral cashback is paid into the customer wallet: none while it is off (the link is still recorded)
                    if ($profile->save(false)) {
                        if (!empty($profile->referal_id) && \app\modules\billing\services\CustomerWallet::enabled()) {

                            $setting = new WebSetting();

                            $refer_bonus = $setting->getSettingBykey('referral_bonus');
                            $referral_bonus_user = $setting->getSettingBykey('referral_bonus_user');

                            $transaction = new UserWallet();
                            $transaction->user_id = $profile->referal_id;
                            $transaction->payment_type = UserWallet::payment_type_credit;
                            $transaction->amount = isset($refer_bonus) ? $refer_bonus : 0; //rand(1,3);//$refer_bonus->value;
                            $transaction->status = UserWallet::STATUS_SUCCESS;
                            $transaction->type = UserWallet::type_referral_earning;
                            $transaction->method_reason = 'Congratulations..!You earned cashback on your referral user' . $profile->referal_id;


                            if (!$transaction->save(false)) {


                                Yii::error('wallet entry not saved: ' . json_encode($transaction->getErrors()), 'api');
                                throw new \RuntimeException('Could not save the entry');
                            } else {

                                $title = 'Congratulations...!!!You earned Cashback';

                                $body = 'Congratulations..!You earned Cashback on your referral.';
                            }
                            // Add cashback to user who using referal code
                            $transaction = new WebSetting();


                            $transaction->user_id = $user_id;
                            $transaction->payment_type = UserWallet::payment_type_credit;
                            $transaction->amount = isset($referral_bonus_user) ? $referral_bonus_user : 0; //rand(1,3);//$refer_bonus->value;
                            $transaction->status = UserWallet::STATUS_SUCCESS;
                            $transaction->type = UserWallet::type_referral_earning;
                            $transaction->method_reason = 'Congratulations..!You earned cashback on for using referal code of user #' . $profile->referal_id;


                            if (!$transaction->save(false)) {


                                Yii::error('wallet entry not saved: ' . json_encode($transaction->getErrors()), 'api');
                                throw new \RuntimeException('Could not save the entry');
                            } else {

                                $title = 'Congratulations...!!!You earned Referral Bonus';

                                $body = 'Congratulations..!You earned Referral Bonus for applying referal code';

                                //Send Notifciation 

                            }
                        }
                    }

                    $data['status'] = SELF::API_OK;

                    $data['details'] = $referUser->asJson();
                } else {

                    $data['status'] = SELF::API_NOK;

                    $data['error'] = 'Invalid Referral Code';
                }
            } else {
                $data['status'] = self::API_NOK;

                $data['error'] = 'Referral code already added';
            }
        } else {
            $data['status'] = self::API_NOK;

            $data['error'] = 'User id not found';
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionReferUser

    // Add taxi driver rating
    // [doc:start] actionAddRating - Add Rating
    public function actionAddRating()
    {
        $data = [];
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        $post = Yii::$app->request->post();


        // var_dump($driver_id);exit; 
        if (!empty($user_id)) {

            $ride_id = $post['ride_id'] ?? '';
            $rating = $post['rating'] ?? '';
            $user_discription = $post['user_discription'] ?? ($post['review'] ?? '');
            // Required: a ride and a rating of 1 to 5.
            if ($ride_id === '' || !is_numeric($rating) || $rating < 1 || $rating > 5) {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t('app', 'ride_id and a rating between 1 and 5 are required');
                return $this->sendJsonResponse($data);
            }
            // Only the rider of a finished ride can rate it; the driver is the ride's own driver, whatever the app sends.
            $ratedRide = RideRequest::find()->where(['id' => $ride_id, 'user_id' => $user_id])
                ->andWhere(['status' => [RideRequest::STATUS_RIDE_COMPLETED, RideRequest::STATUS_RIDE_COMPLETED_PAID]])->one();
            if (empty($ratedRide) || empty($ratedRide->driver_id)) {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t('app', 'Invalid Ride Id');
                return $this->sendJsonResponse($data);
            }
            $taxiDriverId = $ratedRide->driver_id;
            try {
                // one rating per ride: a second call updates it
                $taxiRating = TaxiRating::find()->where(['ride_id' => $ride_id, 'user_id' => $user_id])->one() ?: new TaxiRating();
                $taxiRating->taxi_driver_id = $taxiDriverId;
                $taxiRating->user_id = $user_id;
                $taxiRating->ride_id = $ride_id;
                $taxiRating->user_discription = $user_discription;
                $taxiRating->rating = $rating;
                $taxiRating->status = TaxiRating::STATUS_ACTIVE;

                // var_dump($post['status']);exit;

                if ($taxiRating->save(false)) {
                    $data['status'] = self::API_OK;
                    $data['details'] = $taxiRating;
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = "Data not saved";
                }
            } catch (Exception $e) {
                return $e->getMessage();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = "No user found";
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionAddRating

    // My Notification

    // [doc:start] actionMyNotification - My Notification
    public function actionMyNotification()
    {
        $data = [];
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        $post = Yii::$app->request->post();
        $page = isset($post['page']) ? $post['page'] : 0;


        // var_dump($driver_id);exit; 
        if (!empty($user_id)) {

            try {
                //get store 

                // print_r($store_id)
                $query = FcmNotification::find()->where(['user_id' => $user_id])->andWhere(['status' => FcmNotification::STATUS_ACTIVE]);


                $newOrders = new ActiveDataProvider([
                    'query' => $query,
                    'sort' => [
                        'defaultOrder' => [
                            'id' => SORT_DESC,
                        ],
                    ],
                    'pagination' => [
                        'pageSize' => 20,
                        'page' => $page,
                    ],
                ]);

                foreach ($newOrders->models as $order) {
                    $list[] = $order->asJson();
                }

                if (!empty($order)) {
                    $data['status'] = self::API_OK;
                    $data['details'] = $list;
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = Yii::t("app", " No order found");
                }
            } catch (Exception $e) {
                return $e->getMessage();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = "No user found";
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionMyNotification

    // Clear Notification

    // [doc:start] actionClearNotification - Clear Notification
    public function actionClearNotification()
    {
        $data = [];
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        $post = Yii::$app->request->post();


        // var_dump($driver_id);exit; 
        if (!empty($user_id)) {
            try {
                $notifications = FcmNotification::find()->where(['user_id' => $user_id])->andWhere(['status' => FcmNotification::STATUS_ACTIVE])->all();
                if (!empty($notifications)) {
                    foreach ($notifications as $noti) {
                        $noti->status = FcmNotification::STATUS_DELETE;
                        if ($noti->save(false)) {
                            $data['status'] = self::API_OK;
                            $data['detail'] = "notification cleared";
                        } else {
                            $data['status'] = self::API_NOK;
                            $data['error'] = "data not saved";
                        }
                    }
                }
            } catch (Exception $e) {
                return $e->getMessage();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = "No user found";
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionClearNotification

    // Add Address to favourite

    // [doc:start] actionAddToFavourite - Add To Favourite
    public function actionAddToFavourite()
    {
        $data = [];
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        $post = Yii::$app->request->post();

        $favId = isset($post['fav_id']) ? $post['fav_id'] : "";
        // var_dump($driver_id);exit; 
        if (!empty($user_id)) {
            try {
                if (empty($favId)) {
                    $favourite = new FavouriteLocation();
                    $favourite->user_id = $user_id;
                    $favourite->latitude = $post['latitude'];
                    $favourite->logitude = $post['logitude'];
                    $favourite->address_type = $post['address_type'];
                    $favourite->address = $post['address'];
                    $favourite->pincode = $post['pincode'];
                    $favourite->status = FavouriteLocation::STATUS_ACTIVE;
                } else {
                    $favourite = FavouriteLocation::find()->where(['id' => $favId])->one();
                    $favourite->user_id = $user_id;
                    $favourite->latitude = $post['latitude'];
                    $favourite->logitude = $post['logitude'];
                    $favourite->address_type = $post['address_type'];
                    $favourite->address = $post['address'];
                    $favourite->pincode = $post['pincode'];
                    $favourite->status = FavouriteLocation::STATUS_ACTIVE;
                }

                if ($favourite->save(false)) {
                    $data['status'] = self::API_OK;
                    $data['detail'] = "Location Added to Favourite";
                    $data['location'] =  $favourite;
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = "Data not saved";
                }
            } catch (Exception $e) {
                return $e->getMessage();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = "No user found";
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionAddToFavourite

    // My Favourite Location

    // [doc:start] actionMyFavouriteLocation - My Favourite Location
    public function actionMyFavouriteLocation()
    {
        $data = [];
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        $post = Yii::$app->request->post();


        // var_dump($driver_id);exit; 
        if (!empty($user_id)) {
            try {

                $favourite = FavouriteLocation::find()->where(['user_id' => $user_id])->andWhere(['status' => FavouriteLocation::STATUS_ACTIVE])->all();
                if (!empty($favourite)) {
                    foreach ($favourite as $fav) {
                        $data['status'] = self::API_OK;
                        $data['details'][] = $fav->asJson();
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = "No Favourite Location Found";
                }
            } catch (Exception $e) {
                return $e->getMessage();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = "No user found";
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionMyFavouriteLocation

    // Remove from favourite location

    // [doc:start] actionRemoveFavourite - Remove Favourite
    public function actionRemoveFavourite($id)
    {
        $data = [];
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        $post = Yii::$app->request->post();


        // var_dump($driver_id);exit; 
        if (!empty($user_id)) {
            try {
                $favourite = FavouriteLocation::find()->where(['id' => $id])->andWhere(['status' => FavouriteLocation::STATUS_ACTIVE])->one();
                if (!empty($favourite)) {
                    $favourite->status = FavouriteLocation::STATUS_DELETE;
                    if ($favourite->save(false)) {
                        $data['status'] = self::API_OK;
                        $data['detail'] = "Removed Succesfully";
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['detail'] = "data not saved";
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = "No Favourite Location Found";
                }
            } catch (Exception $e) {
                return $e->getMessage();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = "No user found";
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionRemoveFavourite

    // Add to Emergency Contact
    // [doc:start] actionAddToEmergencyContact - Add To Emergency Contact
    public function actionAddToEmergencyContact()
    {
        $data = [];
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        $post = Yii::$app->request->post();

        $contact_id = isset($post['contact_id']) ? $post['contact_id'] : "";
        // var_dump($driver_id);exit; 
        if (!empty($user_id)) {
            try {
                if (empty($favId)) {
                    $favourite = new EmergencyContact();
                    $favourite->user_id = $user_id;
                    $favourite->contact_number = $post['contact_number'];
                    $favourite->contact_name = $post['contact_name'];
                    $favourite->status  = EmergencyContact::STATUS_ACTIVE;
                } else {
                    $favourite = EmergencyContact::find()->where(['id' => $contact_id])->one();
                    $favourite->user_id = $user_id;
                    $favourite->contact_number = $post['contact_number'];
                    $favourite->contact_name = $post['contact_name'];
                    $favourite->status  = EmergencyContact::STATUS_ACTIVE;
                }

                if ($favourite->save(false)) {
                    $data['status'] = self::API_OK;
                    $data['detail'] = "Location Added to Favourite";
                    $data['location'] =  $favourite;
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = "Data not saved";
                }
            } catch (Exception $e) {
                return $e->getMessage();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = "No user found";
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionAddToEmergencyContact

    // Emergency Contact List

    // [doc:start] actionMyEmergencyContacts - My Emergency Contacts
    public function actionMyEmergencyContacts()
    {
        $data = [];
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        $post = Yii::$app->request->post();


        // var_dump($driver_id);exit; 
        if (!empty($user_id)) {
            try {

                $favourite = EmergencyContact::find()->where(['user_id' => $user_id])->andWhere(['status' => EmergencyContact::STATUS_ACTIVE])->all();
                if (!empty($favourite)) {
                    foreach ($favourite as $fav) {
                        $data['status'] = self::API_OK;
                        $data['details'][] = $fav->asJson();
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = "No Favourite Location Found";
                }
            } catch (Exception $e) {
                return $e->getMessage();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = "No user found";
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionMyEmergencyContacts


    // Wallet Transaction


    // [doc:start] actionWalletTransaction - Wallet Transaction
    public function actionWalletTransaction()
    {
        $data = [];
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        $post = Yii::$app->request->post();
        $page = isset($post['page']) ? $post['page'] : 0;
        $startDate = isset($post['start_date']) ? $post['start_date'] : "";
        $endDate = isset($post['end_date']) ? $post['end_date'] : "";


        // var_dump($driver_id);exit; 
        if (!empty($user_id)) {

            try {
                //get store 

                // print_r($store_id)
                if (!empty($startDate) && !empty($endDate)) {
                    $query = Wallet::find()->where(['user_id' => $user_id])->andWhere(['status' => Wallet::STATUS_ACTIVE])->andWhere(['between', 'updated_on', $startDate, $endDate]);
                } else {
                    $query = Wallet::find()->where(['user_id' => $user_id])->andWhere(['status' => Wallet::STATUS_ACTIVE]);
                }


                $newWallets = new ActiveDataProvider([
                    'query' => $query,
                    'sort' => [
                        'defaultOrder' => [
                            'id' => SORT_DESC,
                        ],
                    ],
                    'pagination' => [
                        'pageSize' => 20,
                        'page' => $page,
                    ],
                ]);

                foreach ($newWallets->models as $order) {
                    $list[] = $order->asJson();
                }

                if (!empty($order)) {
                    $data['status'] = self::API_OK;
                    $data['details'] = $list;
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = Yii::t("app", " No order found");
                }
            } catch (Exception $e) {
                return $e->getMessage();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = "No user found";
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionWalletTransaction
    // public function actionWalletTransaction()
    // {
    //     $data = [];
    //     $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
    //     $auth = new AuthSettings();
    //     $user_id = $auth->getAuthSession($headers);
    //     $post = Yii::$app->request->post();
    //     $page = isset($post['page']) ? $post['page'] : 0;
    //     $startDate = isset($post['start_date']) ? $post['start_date'] : "";
    //     $endDate = isset($post['end_date']) ? $post['end_date'] : "";


    //     // var_dump($driver_id);exit; 
    //     if (!empty($user_id)) {

    //         try {
    //             //get store 

    //             // print_r($store_id)
    //             if (!empty($startDate) && !empty($endDate)) {
    //                 $query = Wallet::find()->where(['user_id' => $user_id])->andWhere(['status' => Wallet::STATUS_ACTIVE])->andWhere(['between', 'updated_on', $startDate, $endDate]);
    //             } else {
    //                 $query = Wallet::find()->where(['user_id' => $user_id])->andWhere(['status' => Wallet::STATUS_ACTIVE]);
    //             }


    //             $newWallets = new ActiveDataProvider([
    //                 'query' => $query,
    //                 'sort' => [
    //                     'defaultOrder' => [
    //                         'id' => SORT_DESC,
    //                     ],
    //                 ],
    //                 'pagination' => [
    //                     'pageSize' => 20,
    //                     'page' => $page,
    //                 ],
    //             ]);

    //             foreach ($newWallets->models as $order) {
    //                 $list[] = $order->asJson();
    //             }

    //             if (!empty($order)) {
    //                 $data['status'] = self::API_OK;
    //                 $data['details'] = $list;
    //             } else {
    //                 $data['status'] = self::API_NOK;
    //                 $data['error'] = Yii::t("app", " No order found");
    //             }
    //         } catch (Exception $e) {
    //             return $e->getMessage();
    //         }
    //     } else {
    //         $data['status'] = self::API_NOK;
    //         $data['error'] = "No user found";
    //     }
    //     return $this->sendJsonResponse($data);
    // }

    // Cancel Ride

    // [doc:start] actionCancelRide - The rider cancels their taxi ride before it starts.
    /**
     * The rider cancels their taxi ride before it starts.
     *
     * POST /api/taxi-user/cancel-ride   ride_id
     * Only the rider's own ride, and only while it is New / Booked / Accepted / On the way / Arrived. The driver, if any,
     * is notified.
     */
    public function actionCancelRide()
    {
        $data = [];
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        // WHO IS CALLING: the app sends its login code (auth_code); it is turned into a user id here. A blocked, inactive
        // or logged-out account gets no id.
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        $post = Yii::$app->request->post();
        $rideId = $post['ride_id'];



        // var_dump($driver_id);exit; 
        if (!empty($user_id)) {

            try {
                $rideRequest = RideRequest::find()
                    // OWNERSHIP AND STATE: someone else's ride, or a ride already started/finished, is never found.
                    ->where(['id' => $rideId, 'user_id' => $user_id])
                    ->andWhere(['status' => [RideRequest::STATUS_NEW_REQUEST, RideRequest::STATUS_RIDE_BOOKED, RideRequest::STATUS_ACCEPTED_BY_DRIVER, RideRequest::STATUS_ON_THE_WAY, RideRequest::STATUS_ARRIVED]])
                    ->one();
                if (!empty($rideRequest)) {
                    $rideRequest->status = RideRequest::STATUS_CANCEL_BY_USER;
                    if ($rideRequest->save(false)) {
                        $data['status'] = self::API_OK;
                        $data['detail'] = "Ride cancelled Successfully";
                        if (!empty($rideRequest->driver)) {
                            $title = "Ride Status";
                            $body = "Ride is cancelled by user";
                            $send_noti = Yii::$app->push->notifyDriver($rideRequest->id, $rideRequest->driver_id, $title, $body);
                        }
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = "Please try again";
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = "Invalid Ride Id";
                }
            // Anything unexpected is answered as NOK (the text is cleaned of SQL/stack details by sendJsonResponse).
            } catch (Exception $e) {
                $data['status'] = self::API_NOK;
                $data['error'] = $e->getMessage();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = "No user found";
        }
        // REPLY: every path above ends here with {status: OK|NOK, details|error}.
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionCancelRide

    // [doc:start] actionRideCompletionLog - Ride Completion Log
    public function actionRideCompletionLog()
    {
        $data = [];
        $dd = [];
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        $post = Yii::$app->request->post();


        // var_dump($driver_id);exit; 
        if (!empty($user_id)) {

            try {

                $rideId = $post['ride_id'];
                $rideCompletionLog = RideCompletionLog::find()->where(['ride_id' => $rideId])->one();
                if (!empty($rideCompletionLog)) {
                    $data['status'] = self::API_OK;
                    $data['details'] = $rideCompletionLog->asJson();
                } else {
                    $data['status'] = self::API_OK;
                    $data['error'] = "No Log Found";
                }
            } catch (Exception $e) {
                return $e->getMessage();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = "No user found";
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionRideCompletionLog
    // Ride Detail 
    // [doc:start] actionRideCompletionDetail - Ride Completion Detail
    public function actionRideCompletionDetail()
    {
        $data = [];
        $dd = [];
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        $post = Yii::$app->request->post();


        // var_dump($driver_id);exit; 
        if (!empty($user_id)) {

            try {

                $rideId = $post['ride_id'];
                $rideCompletionLog = RideRequest::find()->where(['id' => $rideId])->one();
                if (!empty($rideCompletionLog)) {
                    $data['status'] = self::API_OK;
                    if ($rideCompletionLog->final_distance == 0) {
                        $data['details']['distance'] = $rideCompletionLog->final_distance;
                    } else {

                        $data['details']['distance'] = $rideCompletionLog->final_distance;
                    }
                    $data['details']['time'] = $rideCompletionLog->final_time;
                    $data['details']['final_price'] = $rideCompletionLog->final_price;
                } else {
                    $data['status'] = self::API_OK;
                    $data['error'] = "No Log Found";
                }
            } catch (Exception $e) {
                return $e->getMessage();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = "No user found";
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionRideCompletionDetail


    // 


    // [doc:start] actionInvoice - Invoice
    public function actionInvoice($ride_id)
    {
        $data = [];
        $dd = [];
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        $post = Yii::$app->request->post();


        // var_dump($driver_id);exit; 
        if (!empty($user_id)) {

            try {


                $rideRequest = RideRequest::find()->where(['id' => $ride_id])->one();
                // $content = $this->render('_printpage', ['ride_request' => $rideRequest]);
                $content = $this->renderPartial('_printpage', ['ride_request' => $rideRequest]);
                // return $content;
                // setup kartik\mpdf\Pdf component
                $pdf = new Pdf([
                    // set to use core fonts only
                    'mode' => Pdf::MODE_CORE,
                    // A4 paper format
                    'format' => Pdf::FORMAT_A4,
                    // portrait orientation
                    'orientation' => Pdf::ORIENT_PORTRAIT,
                    // stream to browser inline
                    'destination' => Pdf::DEST_DOWNLOAD,
                    // your html content input
                    'content' => $content,
                    // format content from your own css file if needed or use the
                    // enhanced bootstrap css built by Krajee for mPDF formatting 
                    'cssFile' => '@vendor/kartik-v/yii2-mpdf/src/assets/kv-mpdf-bootstrap.min.css',
                    // any css to be embedded if required
                    'cssInline' => '',
                    // set mPDF properties on the fly
                    'options' => ['title' => false],
                    // call mPDF methods on the fly
                    'methods' => [
                        'SetHeader' => false,
                        'SetFooter' => false,
                    ]
                ]);

                // return the pdf output as per the destination setting

                $file = $pdf->render();
                $filename = 'fee_recept.pdf';
                return Yii::$app->response->sendContentAsFile($file, $filename)->send();
                $data['status'] = self::API_OK;

                // return $content;

            } catch (Exception $e) {
                return $e->getMessage();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = "No user found";
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionInvoice


    // [doc:start] actionMyReferal - My Referal
    public function actionMyReferal()
    {
        $data = [];
        $post = Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);

        if (!empty($user_id)) {
            $referals = User::find()->where(['referal_id' => $user_id])->all();

            // print_r($referals->createCommand()->getRawSql());exit;

            if (!empty($referals)) {

                $data['status'] = SELF::API_OK;
                foreach ($referals as $referal) {

                    $data['detail'][] = $referal->asJson();
                }
            } else {
                $data['status'] = SELF::API_NOK;
                $data['error'] = Yii::t("app", "No Referals");
            }
        } else {
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "User Not Found");
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionMyReferal
}
