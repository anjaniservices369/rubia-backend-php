<?php

namespace app\modules\taxicore\partner\controllers;

use app\modules\api\controllers\BKController;
use yii;
use yii\filters\AccessControl;
use yii\filters\AccessRule;
use yii\helpers\ArrayHelper;
use app\modules\auth\services\AuthSettings;
use app\modules\geo\services\DrivingDistance;
use app\modules\payments\services\OpenMoney;
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
use app\modules\admin\models\BankDetail;
use app\modules\admin\models\base\RideCommision;
use app\modules\admin\models\base\RideRequest;
use app\modules\admin\models\base\Wallet;
use app\modules\admin\models\DriverBankDetails;
use app\modules\admin\models\DriverDetails;
use app\modules\admin\models\TaxiDriverRequest;
use app\modules\admin\models\OpenMoneyContacts;
use app\modules\admin\models\RejectLog;
use app\modules\admin\models\RideCompletionLog;
use app\modules\admin\models\RideEarnings;
use app\modules\admin\models\RideStatuses;
use app\modules\admin\models\TaxiPayout;
use app\modules\admin\models\TaxiRating;
use app\modules\admin\models\Vehicals;
use app\modules\admin\models\UserWallet;
use app\modules\admin\models\City;

use DateTime;
use Exception;
use app\modules\payments\services\CashFree;
use app\modules\admin\models\base\DriverEmergencyContacts;
use app\modules\admin\models\DriverWallet;

/**
 * Taxi PARTNER app API: /api/taxi/*  (alias /api/skipper/*, deprecated) - the TAXI SERVICE (bike / auto / car).
 *
 * The driver here is called a "taxi driver" (user role `taxi`). Offers come from OrderDispatch; accepting is atomic
 * in TaxiOfferService; position and online state are kept by TaxiPresenceService. Money: taxi_payout,
 * ride_earnings, ride_commision.
 */
class TaxiController extends BKController

{
    /** Stored in uth.source for taxi drivers; existing rows use this value, do not change it. */
    const AUTH_SOURCE = 'taxi';
    /** Suffix of the generated username (<number>@taxi) of taxi drivers. */
    const USERNAME_SUFFIX = '@taxi';

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
                            'send-otp',
                            'verify-otp',
                            'resend-otp',
                            'update-profile',
                            'add-family-details',
                            'add-to-fav',
                            'add-rating',
                            'logout',
                            'check-delivery',
                            'profile-detail',
                            'vehicals',
                            'update-vehicals',
                            'update-profile-image',
                            'update-driving-license',
                            'update-vehical-registration',
                            'upload-adhaar',
                            'upload-pan',
                            'add-bank-details',
                            'generate-payment-token',
                            'update-payment-status',
                            'taxi-status',
            'skipper-status',
                            'update-lat-lng',
                            'update-online-status',
                            'new-request',
                            'accept-or-reject',
                            'change-status',
                            'my-rides',
                            'my-earnings',
                            'my-rides-detail',
                            'payout',
                            'verify-ride-otp',
                            'ride-completion-log',
                            'update-chassis-number',
                            'driver-detail',
                            'my-reviews',
                            'my-earnings-details',
                            'cancel-ride',
                            'withdraw-request',
                            'withdraw-request-list',
                            'refer-user',
                            'my-referal',
                            'create-order',
                            'add-balance',
                            'available-balance',
                            'transaction-list',
                            'withdraw',
                            'add-driver-emergency-contacts',
                            'update-taxi-city',
            'update-skipper-city',
                            'list-city',
                            'get-vehicle'

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
                            'my-profile', 'generate-payment-token',
                            'update-payment-status',
                            'update-profile',
                            'add-rating',
                            'add-family-details',
                            'add-to-fav',
                            'check-delivery',
                            'saved-address',
                            'profile-detail',
                            'vehicals',
                            'update-vehicals',
                            'update-profile-image',
                            'update-driving-license',
                            'update-vehical-registration',
                            'upload-adhaar',
                            'upload-pan',
                            'add-bank-details',
                            'taxi-status',
            'skipper-status',
                            'update-lat-lng',
                            'update-online-status',
                            'new-request',
                            'accept-or-reject',
                            'change-status',
                            'my-rides',
                            'my-earnings',
                            'my-rides-detail',
                            'payout',
                            'verify-ride-otp',
                            'update-chassis-number',
                            'driver-detail',
                            'my-reviews',
                            'my-earnings-details',
                            'withdraw-request',
                            'withdraw-request-list',
                            'refer-user',
                            'my-referal',
                            'create-order',
                            'add-balance',
                            'available-balance',
                            'transaction-list',
                            'withdraw',
                            'add-driver-emergency-contacts',
                            'update-taxi-city',
            'update-skipper-city',
                            'list-city',
                            'get-vehicle'

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
        $data['details'] =  ['hi'];
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionIndex

    //Check Address or Pin code deliverable or not

    // [doc:start] actionLogout - Logout
    public function actionLogout()
    {
        $data = [];
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
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
                $data['detail'] = $user->asTaxiJson();

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

                $providerId = self::AUTH_SOURCE;

                $number = $post['contact_no'];
                $auth_id = $post['contact_no'];

                $auth = Auth::find()->where([
                    'source' => $providerId,

                ])->andWhere(['source_id' => $auth_id])->one();

                if ($auth) {

                    $user = $auth->user;
                    $user->device_token = ($post['device_token'] ?? '');
                    $user->device_type = ($post['device_type'] ?? 0);
                    Yii::$app->user->login($user);

                    $data['status'] = self::API_OK;
                    $data['details'] = $user;
                    $data['auth_code'] = AuthSession::newSession($user)->auth_code;
                } else {
                    $check = User::find()->where(['username' => $number . self::USERNAME_SUFFIX])->one();
                    if (empty($check)) {
                        $model = new User();
                        $model->username = $number . self::USERNAME_SUFFIX;
                        $model->contact_no = $number;
                        $model->device_token = ($post['device_token'] ?? '');
                        $model->device_type = ($post['device_type'] ?? 0);
                        $model->referal_code = 'ESYGO' . rand(1234, 9999);

                        $model->user_role =  User::ROLE_TAXI;

                        if ($model->validate()) {
                            if ($model->save()) {
                                $auth = new Auth();
                                $auth->user_id = $model->id;
                                $auth->source = $providerId;
                                $auth->source_id = $auth_id;
                                if ($auth->save(false)) {
                                    // //Find User
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
        $user_id = $this->authUserId();
        if (!empty($user_id)) {
            $model = User::findOne($user_id);
            $d['user_details'] = $model->asJson();
            $data['status'] = SELF::API_OK;
            $data['details'] = $d;
        } else {
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "User Not Found");
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionMyProfile

    // [doc:start] actionUpdateProfile - Update Profile
    public function actionUpdateProfile()
    {
        $data = [];
        $post = Yii::$app->request->post();
        $user_id = $this->authUserId();
        if (!empty($user_id)) {
            if (!empty($post)) {
                $model = User::find()->where(['id' => $user_id])->andWhere(['user_role' => User::ROLE_USER])->one();

                if (!empty($model)) {
                    $model->first_name =  $post['User']['username'];
                    if (!empty($post['User']['profile_image'])) {
                        $profile_image = $model->profileImage($post['User']['profile_image'], $model->first_name);
                        $model->profile_image = $profile_image;
                    }
                    if (!empty($post['User']['email'])) {
                        $model->email = $post['User']['email'];
                    }
                    $model->username = $model->email;

                    if ($model->update(false)) {
                        $data['status'] = self::API_OK;
                        $data['details'] = $model->asJson();
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = Yii::t("app", 'Something Went Wrong');
                    }
                } else {
                    $data['status'] = SELF::API_NOK;
                    $data['error'] = Yii::t("app", "User Not Found");
                }
            } else {
                $data['status'] = SELF::API_NOK;
                $data['error'] = Yii::t("app", "Data Not Posted");
            }
        } else {
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "User Not Found");
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionUpdateProfile

    // Update add Profile Detail
    // [doc:start] actionProfileDetail - Profile Detail
    public function actionProfileDetail()
    {

        $data = [];
        $post = Yii::$app->request->post();
        $user_id = $this->authUserId();

        if (!empty($user_id)) {

            if (!empty($post)) {
                $check = User::find()->where(['email' => $post['email']])->one();
                if (!empty($check)) {
                    $data['status'] = SELF::API_NOK;
                    $data['error'] = "Email Already Used";
                    return $this->sendJsonResponse($data);
                }
                $taxi = User::find()->where(['id' => $user_id])->andWhere(['user_role' => User::ROLE_TAXI])->one();

                if (!empty($taxi)) {
                    $taxi->first_name = !empty($post['first_name']) ? $post['first_name'] : $error = "Enter First Name";
                    $taxi->last_name = !empty($post['last_name']) ? $post['last_name'] : $error = "Enter Last Name";
                    $taxi->email = !empty($post['email']) ? $post['email'] : $taxi->username;
                    $taxi->date_of_birth = !empty($post['date_of_birth']) ? $post['date_of_birth'] : $error = "Enter DOB";
                    $taxi->gender = !empty($post['gender']) ? $post['gender'] : $error = "Enter Gender";
                    if ($taxi->save(false)) {
                        $driverDetail = DriverDetails::find()->where(['user_id' => $user_id])->one();
                        if (!empty($driverDetail)) {
                            $driverDetail->address = $post['address'];

                            $driverDetail->status = DriverDetails::STATUS_ACTIVE;
                            if ($driverDetail->save(false)) {
                                $taxi->form_steps = User::PERSONAL_DETAIL;
                                $taxi->save(false);
                                $data['status'] = SELF::API_OK;
                                $data['message'] = "Data Saved";
                            } else {
                                $data['status'] = SELF::API_NOK;
                                $data['message'] = Yii::t("app", "Data not saved");
                            }
                        } else {

                            $driverDetails = new DriverDetails();
                            $driverDetails->user_id = $taxi->id;
                            $driverDetails->address = $post['address'];

                            if ($driverDetails->save(false)) {
                                $taxi->form_steps = User::PERSONAL_DETAIL;
                                $taxi->save(false);
                                $data['status'] = SELF::API_OK;
                                $data['message'] = "Data Saved";
                            } else {
                                $data['status'] = SELF::API_NOK;
                                $data['message'] = Yii::t("app", "Data not saved");
                            }
                        }
                    } else {
                        $data['status'] = SELF::API_NOK;
                        $data['message'] = $error;
                    }
                } else {
                    $data['status'] = SELF::API_NOK;
                    $data['message'] = Yii::t("app", "User not found");
                }
            } else {
                $data['status'] = SELF::API_NOK;
                $data['message'] = Yii::t("app", "No Data Post");
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = "No User found.";
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionProfileDetail

    // Vehicals

    // [doc:start] actionVehicals - Vehicals
    public function actionVehicals()
    {
        $data = [];
        $post = Yii::$app->request->post();
        $user_id = $this->authUserId();
        if (!empty($user_id)) {

            $vehicals = Vehicals::find()->Where(['status' => Vehicals::STATUS_ACTIVE])->all();

            if (!empty($vehicals)) {
                foreach ($vehicals as $vehical) {
                    $data['status'] = SELF::API_OK;
                    $data['details'][] = $vehical->asVehicleListJson();
                }
            } else {
                $data['status'] = SELF::API_NOK;
                $data['error'] = Yii::t("app", "Data Not Found");
            }
        } else {
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "User Not Found");
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionVehicals

    // [doc:start] actionGetVehicle - Get Vehicle
    public function actionGetVehicle()
    {
        $data = [];
        $post = Yii::$app->request->post();
        $user_id = $this->authUserId();
        if (!empty($user_id)) {
            $vehicals = Vehicals::find()->Where(['status' => Vehicals::STATUS_ACTIVE])->andWhere(['vehicle_type' => \app\modules\geo\services\ServiceModules::TAXI])->all();
            if (!empty($vehicals)) {
                foreach ($vehicals as $vehical) {
                    $data['status'] = SELF::API_OK;
                    $data['details'][] = $vehical->asVehicleListJson();
                }
            } else {
                $data['status'] = SELF::API_NOK;
                $data['error'] = Yii::t("app", "Data Not Found");
            }
        } else {
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "User Not Found");
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionGetVehicle

    // Update Vehical

    // Vehicals

    // [doc:start] actionUpdateVehicals - Update Vehicals
    public function actionUpdateVehicals()
    {
        $data = [];
        $post = Yii::$app->request->post();
        $user_id = $this->authUserId();
        if (!empty($user_id)) {

            $taxi = User::find()->where(['id' => $user_id])->one();
            if (!empty($taxi)) {
                if ($taxi->form_steps == User::PERSONAL_DETAIL) {
                    $driverDetail = DriverDetails::find()->where(['user_id' => $user_id])->one();
                    if (!empty($driverDetail)) {
                        $driverDetail->vehical_id = $post['vehical_id'];
                        $driverDetail->vehical_type = $post['vehical_type'];
                        $driverDetail->vehical_speed = $post['vehical_speed'];
                        $driverDetail->vehical_owner = $post['vehical_own'];
                        if ($driverDetail->save(false)) {
                            $taxi->form_steps = User::VEHICAL_SELECTION;
                            $taxi->save(false);
                            $data['status'] = SELF::API_OK;
                            $data['details'] = Yii::t("app", "Vehicals Updated");
                        } else {
                            $data['status'] = SELF::API_NOK;
                            $data['error'] = Yii::t("app", "Vehicals Not Updated");
                        }
                    } else {
                        $data['status'] = SELF::API_NOK;
                        $data['error'] = Yii::t("app", "Details not found");
                    }
                } else {
                    $data['status'] = SELF::API_NOK;
                    $data['error'] = Yii::t("app", "Submit Personal Details First");
                }
            } else {
                $data['status'] = SELF::API_NOK;
                $data['error'] = Yii::t("app", "User Not Found");
            }
        } else {
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "User Not Found");
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionUpdateVehicals

    // Update Profile Image

    // [doc:start] actionUpdateProfileImage - Update Profile Image
    public function actionUpdateProfileImage()
    {
        $data = [];
        $post = Yii::$app->request->post();
        $user_id = $this->authUserId();
        if (!empty($user_id)) {

            $taxiDriver = User::find()->where(['id' => $user_id])->andWhere(['user_role' => User::ROLE_TAXI])->one();

            if (!empty($taxiDriver)) {
                $taxiDriver->profile_image = $post['profile_image'];
                if ($taxiDriver->save(false)) {
                    $taxiDriver->document_steps = User::SELFIE_ADDED;
                    $taxiDriver->save(false);
                    $data['status'] = SELF::API_OK;
                    $data['details'] = Yii::t("app", "Image uploaded succesfully");
                } else {
                    $data['status'] = SELF::API_NOK;
                    $data['error'] = Yii::t("app", "Not Updated");
                }
            } else {
                $data['status'] = SELF::API_NOK;
                $data['error'] = Yii::t("app", "Data Not Found");
            }
        } else {
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "User Not Found");
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionUpdateProfileImage

    // Update Profile Image

    // [doc:start] actionUpdateDrivingLicense - Update Driving License
    public function actionUpdateDrivingLicense()
    {
        $data = [];
        $post = Yii::$app->request->post();
        $user_id = $this->authUserId();
        if (!empty($user_id)) {

            $taxiDriver = User::find()->where(['id' => $user_id])->andWhere(['user_role' => User::ROLE_TAXI])->one();

            if (!empty($taxiDriver)) {
                $driverDetails = DriverDetails::find()->where(['user_id' => $user_id])->one();
                if (!empty($driverDetails)) {
                    $driverDetails->license_no = $post['license_no'];
                    $driverDetails->proof_of_license = $post['proof_of_license'];
                    $driverDetails->proof_of_license_back = $post['proof_of_license_back'];
                    $driverDetails->license_expiery_date = $post['license_expiery_date'];
                    if ($driverDetails->save(false)) {
                        $taxiDriver->document_steps = User::DRIVING_LICENCE;
                        $taxiDriver->save(false);
                        $data['status'] = SELF::API_OK;
                        $data['details'] = Yii::t("app", "Document Uploaded succesflly");
                    } else {
                        $data['status'] = SELF::API_NOK;
                        $data['error'] = Yii::t("app", "Not Updated");
                    }
                } else {
                    $data['status'] = SELF::API_NOK;
                    $data['error'] = Yii::t("app", "No Details Found");
                }
            } else {
                $data['status'] = SELF::API_NOK;
                $data['error'] = Yii::t("app", "Data Not Found");
            }
        } else {
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "User Not Found");
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionUpdateDrivingLicense

    // [doc:start] actionUpdateTaxiCity - Update Taxi City
    public function actionUpdateTaxiCity()
    {
        $data = [];
        $post = Yii::$app->request->post();
        $user_id = $this->authUserId();
        if (!empty($user_id)) {

            $taxiDriver = User::find()->where(['id' => $user_id])->andWhere(['user_role' => User::ROLE_TAXI])->one();

            if (!empty($taxiDriver)) {
                $driverDetails = DriverDetails::find()->where(['user_id' => $user_id])->one();
                if (!empty($driverDetails)) {
                    $driverDetails->city_id = $post['city_id'];

                    if ($driverDetails->save(false)) {
                        $taxiDriver->form_steps = User::TAXI_LOCATION;
                        $taxiDriver->save(false);
                        $data['status'] = SELF::API_OK;
                        $data['details'] = Yii::t("app", "City updated succesflly");
                    } else {
                        $data['status'] = SELF::API_NOK;
                        $data['error'] = Yii::t("app", "Not Updated");
                    }
                } else {
                    $data['status'] = SELF::API_NOK;
                    $data['error'] = Yii::t("app", "No Details Found");
                }
            } else {
                $data['status'] = SELF::API_NOK;
                $data['error'] = Yii::t("app", "Data Not Found");
            }
        } else {
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "User Not Found");
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionUpdateTaxiCity

    // [doc:start] actionListCity - List City
    public function actionListCity()
    {
        $data = [];
        $post = Yii::$app->request->post();
        $user_id = $this->authUserId();
        if (!empty($user_id)) {
            $cities = City::find()->all();
            if($cities){
            $data['status'] = SELF::API_OK;
            $data['details'] = $cities;
            }else{
                $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "No City found");
            }

        } else {
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "User Not Found");
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionListCity

    // Vehical Reg

    // Update Profile Image

    // [doc:start] actionUpdateVehicalRegistration - Update Vehical Registration
    public function actionUpdateVehicalRegistration()
    {
        $data = [];
        $post = Yii::$app->request->post();
        $user_id = $this->authUserId();
        if (!empty($user_id)) {

            $taxiDriver = User::find()->where(['id' => $user_id])->andWhere(['user_role' => User::ROLE_TAXI])->one();

            if (!empty($taxiDriver)) {
                $driverDetails = DriverDetails::find()->where(['user_id' => $user_id])->one();
                if (!empty($driverDetails)) {
                    $driverDetails->rc_number = $post['rc_number'];
                    $driverDetails->vehical_number = $post['vehicle_number'];
                    $driverDetails->rc_proof = $post['rc_proof'];
                    $driverDetails->rc_proof_back = $post['rc_proof_back'];
                    if ($driverDetails->save(false)) {
                        $taxiDriver->document_steps = User::RC_UPLOAD;
                        $taxiDriver->save(false);
                        $data['status'] = SELF::API_OK;
                        $data['details'] = Yii::t("app", "Document Uploaded succesflly");
                    } else {
                        $data['status'] = SELF::API_NOK;
                        $data['error'] = Yii::t("app", "Not Updated");
                    }
                } else {
                    $data['status'] = SELF::API_NOK;
                    $data['error'] = Yii::t("app", "No Details Found");
                }
            } else {
                $data['status'] = SELF::API_NOK;
                $data['error'] = Yii::t("app", "Data Not Found");
            }
        } else {
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "User Not Found");
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionUpdateVehicalRegistration

    // Chasis Number

    // [doc:start] actionUpdateChassisNumber - Update Chassis Number
    public function actionUpdateChassisNumber()
    {
        $data = [];
        $post = Yii::$app->request->post();
        $user_id = $this->authUserId();
        if (!empty($user_id)) {

            $taxiDriver = User::find()->where(['id' => $user_id])->andWhere(['user_role' => User::ROLE_TAXI])->one();

            if (!empty($taxiDriver)) {
                $driverDetails = DriverDetails::find()->where(['user_id' => $user_id])->one();
                if (!empty($driverDetails)) {
                    $driverDetails->chassis_number = $post['chassis_number'];
                    $driverDetails->chassis_image = $post['chassis_image'];
                    $driverDetails->model_name = $post['model_name'];
                    if ($driverDetails->save(false)) {
                        $taxiDriver->document_steps = User::CHASSIS_NUMBER;
                        $taxiDriver->save(false);
                        $data['status'] = SELF::API_OK;
                        $data['details'] = Yii::t("app", "Document Uploaded succesflly");
                    } else {
                        $data['status'] = SELF::API_NOK;
                        $data['error'] = Yii::t("app", "Not Updated");
                    }
                } else {
                    $data['status'] = SELF::API_NOK;
                    $data['error'] = Yii::t("app", "No Details Found");
                }
            } else {
                $data['status'] = SELF::API_NOK;
                $data['error'] = Yii::t("app", "Data Not Found");
            }
        } else {
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "User Not Found");
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionUpdateChassisNumber
    // Adhaar Upload

    // [doc:start] actionUploadAdhaar - Upload Adhaar
    public function actionUploadAdhaar()
    {
        $data = [];
        $post = Yii::$app->request->post();
        $user_id = $this->authUserId();
        if (!empty($user_id)) {

            $taxiDriver = User::find()->where(['id' => $user_id])->andWhere(['user_role' => User::ROLE_TAXI])->one();

            if (!empty($taxiDriver)) {
                $driverDetails = DriverDetails::find()->where(['user_id' => $user_id])->one();
                if (!empty($driverDetails)) {
                    $driverDetails->adhaar_number = $post['adhaar_number'];
                    $driverDetails->adhaar_front = $post['adhaar_front'];
                    $driverDetails->adhaar_back = $post['adhaar_back'];
                    if ($driverDetails->save(false)) {
                        $taxiDriver->document_steps = User::ADHAAR_CARD;
                        $taxiDriver->save(false);
                        $data['status'] = SELF::API_OK;
                        $data['details'] = Yii::t("app", "Document Uploaded succesflly");
                    } else {
                        $data['status'] = SELF::API_NOK;
                        $data['error'] = Yii::t("app", "Not Updated");
                    }
                } else {
                    $data['status'] = SELF::API_NOK;
                    $data['error'] = Yii::t("app", "No Details Found");
                }
            } else {
                $data['status'] = SELF::API_NOK;
                $data['error'] = Yii::t("app", "Data Not Found");
            }
        } else {
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "User Not Found");
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionUploadAdhaar

    // Pan Upload

    // [doc:start] actionUploadPan - Upload Pan
    public function actionUploadPan()
    {
        $data = [];
        $post = Yii::$app->request->post();
        $user_id = $this->authUserId();
        if (!empty($user_id)) {

            $taxiDriver = User::find()->where(['id' => $user_id])->andWhere(['user_role' => User::ROLE_TAXI])->one();

            if (!empty($taxiDriver)) {
                $driverDetails = DriverDetails::find()->where(['user_id' => $user_id])->one();
                if (!empty($driverDetails)) {
                    $driverDetails->pan_number = $post['pan_number'];
                    $driverDetails->pan_front = $post['pan_front'];
                    if ($driverDetails->save(false)) {
                        $taxiDriver->document_steps = User::PAN_CARD;
                        $taxiDriver->form_steps = User::DOCUMENT_UPLOADS;
                        $taxiDriver->save(false);
                        $data['status'] = SELF::API_OK;
                        $data['details'] = Yii::t("app", "Document Uploaded succesflly");
                    } else {
                        $data['status'] = SELF::API_NOK;
                        $data['error'] = Yii::t("app", "Not Updated");
                    }
                } else {
                    $data['status'] = SELF::API_NOK;
                    $data['error'] = Yii::t("app", "No Details Found");
                }
            } else {
                $data['status'] = SELF::API_NOK;
                $data['error'] = Yii::t("app", "Data Not Found");
            }
        } else {
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "User Not Found");
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionUploadPan
    // Add Bank Details

    // [doc:start] actionAddBankDetails - Add Bank Details
    public function actionAddBankDetails()
    {
        $data = [];
        $param = [];
        $post = Yii::$app->request->post();
        $user_id = $this->authUserId();
        if (!empty($user_id)) {

            $taxiDriver = User::find()->where(['id' => $user_id])->andWhere(['user_role' => User::ROLE_TAXI])->one();

            if (!empty($taxiDriver)) {
                $bankDetail = DriverBankDetails::find()->where(['driver_id' => $user_id])->one();
                if (empty($bankDetail)) {
                    $bankDet = new DriverBankDetails();
                    $bankDet->driver_id = $user_id;
                    $bankDet->bank_name = $post['bank_name'];
                    $bankDet->account_holder_name = $post['account_holder_name'];
                    $bankDet->account_number = $post['account_number'];
                    $bankDet->ifsc = $post['ifsc_code'];
                    $bankDet->upi = $post['upi_id'];
                    $bankDet->branch_name = $post['branch_name'];
                    $bankDet->status = DriverBankDetails::STATUS_ACTIVE;
                    if ($bankDet->save(false)) {

                        $taxiDriver->form_steps = User::BANK_DETAILS;
                        $taxiDriver->save(false);
                        $data['status'] = SELF::API_OK;
                        $data['details'] = Yii::t("app", "Document Uploaded succesflly");
                    } else {
                        $data['status'] = SELF::API_NOK;
                        $data['error'] = Yii::t("app", "Not Saved");
                    }
                } else {
                    $data['status'] = SELF::API_NOK;
                    $data['error'] = Yii::t("app", "Bank Details Already Added");
                }
            } else {
                $data['status'] = SELF::API_NOK;
                $data['error'] = Yii::t("app", "User Not Found");
            }
        } else {
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "User Not Found");
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionAddBankDetails

    // Verification Status

    // [doc:start] actionTaxiStatus - Taxi Status
    public function actionTaxiStatus()
    {
        $data = [];
        $post = Yii::$app->request->post();
        $user_id = $this->authUserId();
        if (!empty($user_id)) {

            $taxiDriver = User::find()->where(['id' => $user_id])->andWhere(['user_role' => User::ROLE_TAXI])->one();

            if (!empty($taxiDriver)) {
                $taxiDetail = DriverDetails::find()->where(['user_id' => $user_id])->one();
                if (!empty($taxiDetail)) {
                    $data['status'] = SELF::API_OK;
                    $data['details'] = $taxiDetail->is_verified;
                } else {
                    $data['status'] = SELF::API_NOK;
                    $data['error'] = "No Driver Detail Found";
                }
            } else {
                $data['status'] = SELF::API_NOK;
                $data['error'] = Yii::t("app", "User Not Found");
            }
        } else {
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "User Not Found");
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionTaxiStatus

    // Update lat Lng

    // [doc:start] actionUpdateLatLng - Update Lat Lng
    public function actionUpdateLatLng()
    {
        $driverId = $this->authUserId();
        if (empty($driverId)) {
            return $this->sendJsonResponse(['status' => self::API_NOK, 'error' => 'User Not Found']);
        }

        $post = Yii::$app->request->post();
        if (empty($post)) {
            return $this->sendJsonResponse(['status' => self::API_NOK, 'error' => 'No data posted.']);
        }

        try {
            $position = $this->module->presence->updateLocation(
                $driverId,
                $post['latitude'] ?? null,
                $post['longitude'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return $this->sendJsonResponse(['status' => self::API_NOK, 'error' => $e->getMessage()]);
        }

        return $position === null
            ? $this->sendJsonResponse(['status' => self::API_NOK, 'error' => 'Wrong User'])
            : $this->sendJsonResponse(['status' => self::API_OK, 'detail' => $position]);
    }
    // [doc:end] actionUpdateLatLng

    // Online Status

    // [doc:start] actionUpdateOnlineStatus - Update Online Status
    /**
     * @param int|string $status 1 = online, 0 = offline (query string parameter)
     */
    public function actionUpdateOnlineStatus($status)
    {
        $driverId = $this->authUserId();
        if (empty($driverId)) {
            return $this->sendJsonResponse(['status' => self::API_NOK, 'error' => Yii::t('app', 'Session Not Found')]);
        }

        $online = $this->module->presence->setOnline($driverId, $status);
        if ($online === null) {
            return $this->sendJsonResponse(['status' => self::API_NOK, 'error' => Yii::t('app', 'User Not Found')]);
        }

        return $this->sendJsonResponse([
            'status' => self::API_OK,
            'details' => 'Online Status Updated Successfully',
            'online_status' => $online,
        ]);
    }
    // [doc:end] actionUpdateOnlineStatus

    // Accept and Reject

    // [doc:start] actionNewRequest - The taxi driver's inbox: ride offers waiting for an answer.
    /**
     * The taxi driver's inbox: ride offers waiting for an answer.
     *
     * POST /api/taxi/new-request
     * Offers are created by the dispatcher (OrderDispatch) for drivers who are online and near the pickup.
     */
    public function actionNewRequest()
    {
        $driverId = $this->authUserId();
        if (empty($driverId)) {
            return $this->sendJsonResponse(['status' => self::API_NOK, 'error' => Yii::t('app', 'Session Not Found')]);
        }

        $rides = $this->module->offers->pendingFor($driverId);
        if (empty($rides)) {
            return $this->sendJsonResponse(['status' => self::API_NOK, 'error' => Yii::t('app', 'No new request Found')]);
        }

        $details = [];
        foreach ($rides as $ride) {
            $details[] = $ride->asTaxiRideListJson();
        }

        return $this->sendJsonResponse(['status' => self::API_OK, 'details' => $details]);
    }
    // [doc:end] actionNewRequest

    // Accept or reject

    // [doc:start] actionAcceptOrReject - The taxi driver answers an offer.
    /**
     * The taxi driver answers an offer.
     *
     * POST /api/taxi/accept-or-reject   rideRequestId, status = 4 (accept) | 7 (reject)
     * Accepting is atomic (TaxiOfferService::accept): only the first driver gets the ride, the others are told it is
     * gone. Rejecting only affects this driver's offer.
     */
    public function actionAcceptOrReject()
    {
        $driverId = $this->authUserId();
        if (empty($driverId)) {
            return $this->sendJsonResponse(['status' => self::API_NOK, 'error' => 'No User found.']);
        }

        $post = Yii::$app->request->post();
        $rideId = $post['rideRequestId'] ?? null;
        $status = $post['status'] ?? null;
        $offers = $this->module->offers;

        if (!$rideId || !$offers->isOpen($rideId)) {
            return $this->sendJsonResponse([
                'status' => self::API_NOK,
                'error' => 'Another driver has accepted this ride. Wait for the next ride',
            ]);
        }

        if ((int) $status === RideRequest::STATUS_ACCEPTED_BY_DRIVER) {
            $result = $offers->accept($driverId, $rideId);
            if (!$result['ok']) {
                return $this->sendJsonResponse(['status' => self::API_NOK, 'error' => $result['error']]);
            }
            $ride = $result['ride'];

            return $this->sendJsonResponse([
                'status' => self::API_OK,
                'details' => [
                    'id' => $ride->driver_id,
                    'name' => $ride->driver->first_name,
                    'profile_image' => $ride->driver->profile_image,
                    'vehicle_number' => $ride->driverDetail->vehical_number ?? '',
                    'vehicle_type' => $ride->driverDetail->vehical->title ?? '',
                ],
                'ride_status' => $ride->status,
            ]);
        }

        if ((int) $status === RideRequest::STATUS_CANCEL_BY_DRIVER) {
            return $offers->reject($driverId, $rideId)
                ? $this->sendJsonResponse(['status' => self::API_OK, 'details' => 'Ride Rejected Successfully'])
                : $this->sendJsonResponse(['status' => self::API_NOK, 'error' => 'data not saved']);
        }

        return $this->sendJsonResponse([]);
    }
    // [doc:end] actionAcceptOrReject

    // Verify Ride OTP
    // [doc:start] actionVerifyRideOtp - The driver starts the taxi ride by entering the OTP the rider shows.
    /**
     * The driver starts the taxi ride by entering the OTP the rider shows.
     *
     * POST /api/taxi/verify-ride-otp   rideRequestId, status = 8 (start ride), otp
     * Only this driver's ride. Right OTP: ride -> Start Ride and the start time is recorded (fare is based on it).
     */
    public function actionVerifyRideOtp()
    {

        $data = [];

        $post = Yii::$app->request->post();
        $user_id = $this->authUserId();
        $status = $post['status'];
        $rideRequestId = $post['rideRequestId'];

        if (!empty($user_id)) {
            try {
                $rideRequest = RideRequest::find()->where(['id' => $rideRequestId])->andWhere(['driver_id' => $user_id])->one();
                if (empty($rideRequest)) {
                    $data['status'] = SELF::API_NOK;
                    $data['error'] = Yii::t("app", "Ride not found for this driver");
                    return $this->sendJsonResponse($data);
                }
                if ($status == RideRequest::STATUS_START_RIDE) {
                    $otp = $post['otp'];
                    if (!empty($otp)) {
                        // THE OTP CHECK: compared with the code created when the rider booked.
                        if ($rideRequest->otp == $otp) {
                            $rideRequest->status = (int)$status;
                            // START CLOCK: the real duration is measured from here.
                            $rideRequest->ride_start_time = date('Y-m-d H:i:s');
                            if ($rideRequest->save(false)) {
                                $title = "OTP Verified";
                                $body = "Your OTP is verified enjoy your ride!!";
                                $send_noti = Yii::$app->push->notifyUser('', $user_id, $title, $body);
                                $data['status'] = SELF::API_OK;
                                $data['error'] = Yii::t("app", "OTP Verified succesfully.");
                            }
                        } else {
                            $data['status'] = SELF::API_NOK;
                            $data['error'] = Yii::t("app", "Incorrect OTP.");
                            return $this->sendJsonResponse($data);
                        }
                    } else {
                        $data['status'] = SELF::API_NOK;
                        $data['error'] = Yii::t("app", "Please Enter OTP To Start Ride");
                        return $this->sendJsonResponse($data);
                    }
                }
            } catch (Exception $e) {
                return $e->getMessage();
            }
        } else {
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "Session Not Found");
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionVerifyRideOtp

    // Change Status
    // [doc:start] actionChangeStatus - Moves a taxi ride forward: on the way, arrived, completed, paid.
    /**
     * Moves a taxi ride forward: on the way, arrived, completed, paid.
     *
     * POST /api/taxi/change-status   rideRequestId, status, (final_lat, final_lng when completing)
     *   RIDE_COMPLETED      -> the real distance (Google) and time are measured, the final fare is computed from the
     *                          city's ride_commision (base + distance beyond the minimum + extra minutes + GST) and
     *                          a ride_earnings row is created (pending);
     *   RIDE_COMPLETED_PAID -> commission and GST are split; a cash ride debits the admin share from the driver's
     *                          payout balance (taxi_payout); the earnings row is approved.
     * Only the driver the ride belongs to.
     */
    public function actionChangeStatus()
    {

        $data = [];

        $post = Yii::$app->request->post();
        $user_id = $this->authUserId();
        $status = $post['status'];
        $rideRequestId = $post['rideRequestId'];
        $settings = new WebSetting();
        $cgst = $settings->getSettingBykey('cgst');
        $sgst = $settings->getSettingBykey('sgst');
        if ($status == RideRequest::STATUS_START_RIDE) {
            $pickupTime = date('Y-m-d H:i:s');
        } else {
            $pickupTime = "";
        }
        if (!empty($user_id)) {
            // OWNERSHIP: only this driver's own ride is found.
            $rideRequest = RideRequest::find()->where(['id' => $rideRequestId])->andWhere(['driver_id' => $user_id])->one();
            if (empty($rideRequest)) {
                $data['status'] = SELF::API_NOK;
                $data['error'] = Yii::t("app", "Ride not found for this driver");
                return $this->sendJsonResponse($data);
            }
            // COMPLETING THE RIDE: measure, price, record.
            if ($status == RideRequest::STATUS_RIDE_COMPLETED) {

                $final_lat = !empty($post['final_lat']) ? $post['final_lat'] : "";
                $final_lng = !empty($post['final_lng']) ? $post['final_lng'] : "";

                // The fare is calculated from the drop position, so refuse before the ride is marked completed.
                if ($final_lat === "" || $final_lng === "") {
                    $data['status'] = SELF::API_NOK;
                    $data['error'] = Yii::t("app", "Require final lat lng");
                    return $this->sendJsonResponse($data);
                }

                $rideRequest->status = (int)$status;
                $rideRequest->ride_end_time = date('Y-m-d H:i:s');
                if ($rideRequest->save(false)) {

                    $date1 = new DateTime($rideRequest->ride_start_time);
                    $date2 = new DateTime($rideRequest->ride_end_time);
                    $estimatedTimeStr = explode(' ', $rideRequest->estimated_time);
                    $estimatedTime = $rideRequest->estimated_time;
                     $interval = $date1->diff($date2);

                    $format = $interval->format('%i');

                    $total_fare = 0;
                    $rideRequest->final_time = (float)$format;
                    $rideCommision = RideCommision::find()->where(['city_id' => $rideRequest->city_id, 'vehicle_id' => \app\modules\geo\services\ServiceModules::ofVehicle($rideRequest->vehical_type), 'status' => RideCommision::STATUS_ACTIVE])->one();

                    if (!empty($final_lat) && !empty($final_lng)) {
                        $drivingDistance = (new DrivingDistance())->getDrivingDistanceGoogleMap($rideRequest->pickup_latitude, $rideRequest->pickup_longitude, $final_lat, $final_lng);

                        $distanceKm = $drivingDistance['distvalue'] / 1000;

                        $rideRequest->final_distance = $distanceKm;

                        $rideRequest->final_time = (float)$format;
                        $rideRequest->save(false);
                        if ((float) $distanceKm > $rideRequest->estimated_distance || (float)$format > (float)$estimatedTime) {
                            if ((float) $distanceKm > $rideRequest->estimated_distance) {
                                if ((float) $distanceKm > (float) $rideCommision->min_distance) {
                                    $distance = (float) $distanceKm - $rideCommision->min_distance;
                                } else {
                                    $distance = 0;
                                }
                            } else {
                                if ((float)$rideRequest->estimated_distance > (float) $rideCommision->min_distance) {
                                    $distance = (float) $rideRequest->estimated_distance - $rideCommision->min_distance;
                                } else {
                                    $distance = 0;
                                }
                            }
                            if ((float)$format > (float)$estimatedTime) {
                                $timeDiff = (float)$format - $estimatedTime;
                                 $diffMinPrice = $timeDiff * $rideCommision->fare_per_minute;

                            } else {
                                $diffMinPrice = $rideRequest->estimated_time;
                            }
                            $distance_cost = (float) $rideCommision->fare_per_distance * $distance;

                            $rideRequest->final_distance = $distanceKm;

                            $rideRequest->final_time =  $format;
                            $total_fare += (float) $rideCommision->base_fare + (float) $distance_cost + $diffMinPrice;
                            $cgstAmount = ((float)$cgst * $total_fare) / 100;
                            $sgstAmount = ((float)$sgst * $total_fare) / 100;
                            $total_fare = $total_fare + $cgstAmount + $sgstAmount;
                            $rideRequest->final_price = $total_fare;

                            $rideRequest->save(false);

                        } else {
                            $rideRequest->final_price = $rideRequest->estimated_ride_fare;
                            $rideRequest->final_distance = $rideRequest->estimated_distance;
                            $rideRequest->final_time = $rideRequest->estimated_time;
                        }
                    } else {
                        // if emplty final lat lng
                        $data['status'] = SELF::API_NOK;
                        $data['error'] = Yii::t("app", "Require final lat lng");
                        return $this->sendJsonResponse($data);
                    }

                    $rideRequest->save(false);

                    $rideCompletionLog = (new RideCompletionLog())->saveCompletionLog($rideRequestId, $final_lat, $final_lng, $rideRequest->updated_on,  $pickupTime, $rideRequest->final_price, $rideRequest->final_distance);
                } else {
                    $data['status'] = SELF::API_NOK;
                    $data['error'] = Yii::t("app", "Status not changed for ride completion");
                }
            // PAYMENT RECEIVED: split the fare between the company and the driver.
            } else if ($status == RideRequest::STATUS_RIDE_COMPLETED_PAID) {
                $rideEarnings = RideEarnings::find()->where(['ride_id' => $rideRequestId])->andWhere(['status' => RideEarnings::STATUS_PENDING])->one();
                $rideRequest->status = RideRequest::STATUS_RIDE_COMPLETED_PAID;
                if (!empty($rideEarnings)) {
                    $rideCommission = RideCommision::find()->where(['city_id' => $rideRequest->city_id, 'vehicle_id' => \app\modules\geo\services\ServiceModules::ofVehicle($rideRequest->vehical_type), 'status' => RideCommision::STATUS_ACTIVE])->one();
                    if (!empty($rideCommission)) {
                        $commisionPercentage = $rideCommission->commision;
                    } else {
                        $commisionPercentage = 0;
                    }

                    $adminEarnings = (($rideRequest->final_price * $commisionPercentage) / 100);
                    $driverEarnings = $rideRequest->final_price - $adminEarnings;
                    $cgstAmount = ($driverEarnings * $cgst) / 100;
                    $sgstAmount = ($driverEarnings * $sgst) / 100;
                    $adminEarningsnew = $adminEarnings + $cgstAmount + $sgstAmount;
                    $rideEarnings->total_ditance_km = $rideRequest->final_distance;
                    $rideEarnings->admin_earning  = $adminEarningsnew;
                    $rideEarnings->total_amount  = $rideRequest->final_price;
                    $rideEarnings->driver_earning  = $driverEarnings - $cgstAmount - $sgstAmount;
                    $rideEarnings->status  = RideEarnings::STATUS_APPROVED;
                    if ($rideEarnings->save(false)) {

                        if ($rideRequest->payment_method == RideRequest::PAYMENT_METHOD_CASH) {
                            $payout = new TaxiPayout();
                            $payout->amount = $adminEarningsnew;
                            $payout->driver_id = $user_id;
                            $payout->payment_type = TaxiPayout::PAYMENT_TYPE_DEBIT;
                            $payout->method_reason = "Amount of Rs " . $adminEarnings . " debited for cash ride of id #" . $rideRequest->id;
                            $payout->status = TaxiPayout::STATUS_APPROVED;
                            $payout->refrence_id = $rideRequest->id;
                            $payout->save(false);
                        }

                        if ($rideRequest->payment_method == RideRequest::PAYMENT_METHOD_WALLET) {

                            $rideModule = \app\modules\geo\services\ServiceModules::ofVehicle($rideRequest->vehical_type);
                            // the customer's wallet is the customer app's: asked and charged through the gateway
                            $walletAmount = (float) \app\modules\customerapi\services\CustomerClient::walletBalance($rideRequest->user_id)['balance'];

                            if ($walletAmount < $rideRequest->final_price) {
                                $data['status'] = SELF::API_NOK;
                                $data['error'] = Yii::t("app", "Low wallet amount please add to wallet");
                                return $this->sendJsonResponse($data);
                            } else {
                                $charged = \app\modules\customerapi\services\CustomerClient::walletDebit($rideRequest->user_id, $rideRequest->final_price, $rideModule,
                                    "Rs " . $rideRequest->final_price . " Debited from wallet for your ride " . $rideRequest->id, 'ride-' . $rideRequest->id);
                                if (!$charged['ok']) {
                                    $data['status'] = SELF::API_NOK;
                                    $data['error'] = Yii::t("app", "Low wallet amount please add to wallet");
                                    return $this->sendJsonResponse($data);
                                }
                                if ($charged['ok']) {
                                    $rideEarnings = RideEarnings::find()->where(['ride_id' => $rideRequestId])->andWhere(['status' => RideEarnings::STATUS_PENDING])->one();
                                    $final_lat = isset($post['final_lat']) ? $post['final_lat'] : $rideRequest->drop_latitude;
                                    $final_lng = isset($post['final_lng ']) ? $post['final_lng'] : $rideRequest->drop_longitude;
                                }
                                if (!empty($rideEarnings)) {
                                    $rideCommission = RideCommision::find()->where(['city_id' => $rideRequest->city_id, 'vehicle_id' => \app\modules\geo\services\ServiceModules::ofVehicle($rideRequest->vehical_type), 'status' => RideCommision::STATUS_ACTIVE])->one();
                                    if (!empty($rideCommission)) {
                                        $commisionPercentage = $rideCommission->commision;
                                    } else {
                                        $commisionPercentage = 0;
                                    }
                                    $adminEarnings = ($rideRequest->final_price * $commisionPercentage) / 100;
                                    $driverEarnings = $rideRequest->final_price - $adminEarnings;
                                    $cgstAmount = ($driverEarnings * $cgst) / 100;
                                    $sgstAmount = ($driverEarnings * $sgst) / 100;
                                    $adminEarningsnew = $adminEarnings + $cgstAmount + $sgstAmount;
                                    $rideEarnings->total_ditance_km = $rideRequest->final_distance;
                                    $rideEarnings->admin_earning  = $adminEarningsnew;
                                    $rideEarnings->total_amount  = $rideRequest->final_price;
                                    $rideEarnings->driver_earning  = $driverEarnings - $cgstAmount - $sgstAmount;
                                    $rideEarnings->status  = RideEarnings::STATUS_APPROVED;
                                    if ($rideEarnings->save(false)) {
                                        $title = "Payment Status";
                                        $body = "Your Payment For Ride Id-" . $rideRequest->id . " Is Sucesfull !! You Have Earned Rs. " . $driverEarnings;
                                        $send_noti = Yii::$app->push->notifyDriver('', $rideRequest->driver_id, $title, $body);
                                    }
                                    $rideCompletionLog = (new RideCompletionLog())->saveCompletionLog($rideRequest->id, $final_lat, $final_lng, $rideRequest->updated_on, $rideRequest->ride_start_time, $rideRequest->final_price);
                                }
                            }
                        }

                        $rideRequest->payment_status = RideRequest::PAYMENT_STATUS_PAID;
                        $rideRequest->status = RideRequest::STATUS_RIDE_COMPLETED_PAID;
                        $rideRequest->save(false);
                    }
                }
            } else {
                $rideRequest->status = (int)$status;
            }

            if ($rideRequest->save(false)) {
                $title = "Ride Status";
                $body = "Your Ride Is " . strip_tags($rideRequest->getStateOptionsBadges());
                $send_noti = Yii::$app->push->notifyUser('', $rideRequest->user_id, $title, $body);
                $updateRideStatus = (new RideRequest())->updateStatus($rideRequest->id, $rideRequest->status, "Ride Status Changed to " .  strip_tags($rideRequest->getStateOptionsBadges()));
                //         // $send_noti = Yii::$app->push->notifyVendor("", '207', $title, $body);
                if ($rideRequest->status == RideRequest::STATUS_ARRIVED) {
                    $title = "Ride Arrived";
                    $body = "Driver " . $rideRequest->driver->first_name . " has arrived to your location your OTP is" . $rideRequest->otp;
                    $send_noti = Yii::$app->push->notifyUser('', $rideRequest->user_id, $title, $body);
                }
                $data['status'] = SELF::API_OK;
                $data['details'] = Yii::t("app", "Status Updated Succesfully");
                $data['ride_status'] = $rideRequest->status;
            } else {
            }
        } else {
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "Session Not Found");
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionChangeStatus

    // My Rides

    // [doc:start] actionMyRides - My Rides
    public function actionMyRides()
    {
        $data = [];
        $driver_id = $this->authUserId();
        $post = Yii::$app->request->post();

        $today = date('Y-m-d 00:00:00');
        $end_date = date('Y-m-d 00:00:00', strtotime($today . ' +1 day'));
        $status = (int)$post['status'];
        if (!empty($driver_id)) {

            try {
                $page = isset($post['page']) ? $post['page'] : 0;
                if ($status == 10 || $status == "10") {

                    $query = RideRequest::find()
                        ->Where(['driver_id' => $driver_id])
                        ->andWhere(['between', 'created_on', $post['start_date'], $post['end_date']])->andWhere(['status' => RideRequest::STATUS_RIDE_COMPLETED_PAID]);

                } else if ($status == 7 || $status == "7") {

                    $query = RideRequest::find()
                        ->leftJoin('reject_log', 'ride_request.id = reject_log.ride_id')
                        ->Where(['or',['ride_request.driver_id' => $driver_id],['reject_log.taxi_driver_id' => $driver_id]])
                        ->andWhere(['or', ['ride_request.status' => RideRequest::STATUS_CANCEL_BY_USER], ['ride_request.status' => RideRequest::STATUS_CANCEL_BY_DRIVER],['reject_log.status' => 1]])->andWhere(['between', 'ride_request.updated_on', date('Y-m-d H:i:s', strtotime($post['start_date'])), date('Y-m-d H:i:s', strtotime($post['end_date']))]);
                } else if (empty($status) || $status == "") {
                    $query = RideRequest::find()
                        ->Where(['driver_id' => $driver_id])
                        ->andWhere(['between', 'created_on', $post['start_date'], $post['end_date']])
                        ->andWhere(
                            [
                                'and',
                                // ['!=', 'status', RideRequest::STATUS_RIDE_COMPLETED],
                                ['!=', 'status', RideRequest::STATUS_NEW_REQUEST],
                                // ['!=', 'status', RideRequest::STATUS_ACCEPTED_BY_DRIVER],

                            ]
                        );
                }

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

                        $list[] = $driver_order->myRidesTaxiJson();
                    }
                    $data['status'] = self::API_OK;

                    $data['details'] = $list;
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
    // [doc:end] actionMyRides
    // Ride Detail

    // [doc:start] actionMyRidesDetail - My Rides Detail
    public function actionMyRidesDetail($ride_id)
    {
        $data = [];
        $driver_id = $this->authUserId();
        $post = Yii::$app->request->post();

        if (!empty($driver_id)) {

            try {

                $query = RideRequest::find()
                    ->Where(['id' => $ride_id])->one();

                if (!empty($query)) {
                    $data['status'] = self::API_OK;
                    $data['detail'] = $query->asRideRequestDetailJson();
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

    // My Earnings

    // [doc:start] actionMyEarnings - My Earnings
    public function actionMyEarnings()
    {
        $data = [];
        $post = Yii::$app->request->post();
        $user_id = $this->authUserId();
        $page = isset($post['page']) ? $post['page'] : 0;

        $start_date = $post['start_date'];
        $end_date = $post['end_date'];

        if (!empty($user_id)) {

            $query = RideEarnings::find()->Where(['driver_id' => $user_id])->andWhere(['status' => RideEarnings::STATUS_APPROVED]);
            $rideQuery = RideEarnings::find()->Where(['driver_id' => $user_id])->andWhere(['status' => RideEarnings::STATUS_APPROVED]);

            $newRides = new ActiveDataProvider([
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

            foreach ($newRides->models as $rides) {
                $list[] = $rides->asJson();
            }
            if (!empty($list)) {
                if (!empty($start_date) && !empty($end_date)) {
                    $earnings = RideEarnings::find()->where(['driver_id' => $user_id])->andWhere(['between', 'created_on', $start_date, $end_date])->one();
                } else {
                    $earnings = RideEarnings::find()->where(['driver_id' => $user_id])->one();
                }
                if (!empty($earnings)) {

                    if (!empty($start_date) || !empty($end_date)) {

                        $earningsSum = RideEarnings::find()->where(['driver_id' => $user_id])->andWhere(['or',['!=', 'ride_earnings.type', RideEarnings::MANUL],['ride_earnings.type' => NULL]])->andWhere(['between', 'created_on', $post['start_date'], $post['end_date']])->sum('driver_earning');
                        $cashCollectionSum = RideRequest::find()->where(['driver_id' => $user_id])->andWhere(['payment_method' => RideRequest::PAYMENT_METHOD_CASH])->andWhere(['status' => RideRequest::STATUS_RIDE_COMPLETED_PAID])->andWhere(['between', 'created_on', $post['start_date'], $post['end_date']])->sum('final_price');

                        $rideRequest = RideRequest::find()->where(['driver_id' => $user_id])->andWhere(['between', 'created_on', $post['start_date'], $post['end_date']])->count();
                        $rideRequestcount = RideRequest::find()->where(['driver_id' => $user_id])->andWhere(['between', 'created_on', $post['start_date'], $post['end_date']])->andWhere(['status' => RideRequest::STATUS_RIDE_COMPLETED_PAID])->count();
                        $completedRideRequestcount = RideRequest::find()->where(['driver_id' => $user_id])->andWhere(['between', 'created_on', $post['start_date'], $post['end_date']])->andWhere(['status' => RideRequest::STATUS_RIDE_COMPLETED_PAID])->count();
                        $totalKm = RideRequest::find()->where(['driver_id' => $user_id])->andWhere(['between', 'created_on', $post['start_date'], $post['end_date']])->sum('final_distance');
                        $totalhours = RideRequest::find()->where(['driver_id' => $user_id])->andWhere(['between', 'created_on', $post['start_date'], $post['end_date']])->sum('final_time');
                        $rejectRides = RejectLog::find()->where(['taxi_driver_id' => $user_id])->andWhere(['between', 'created_on', $post['start_date'], $post['end_date']])->count();
                        $rideQuery->andWhere(['between', 'created_on', $post['start_date'], $post['end_date']]);
                    } else {
                        $earningsSum = RideEarnings::find()->where(['driver_id' => $user_id])->andWhere(['or',['!=', 'ride_earnings.type', RideEarnings::MANUL],['ride_earnings.type' => NULL]])->sum('driver_earning');
                        $cashCollectionSum = RideRequest::find()->where(['driver_id' => $user_id])->andWhere(['payment_method' => RideRequest::PAYMENT_METHOD_CASH])->andWhere(['status' => RideRequest::STATUS_RIDE_COMPLETED_PAID])->sum('final_price');

                        $rideRequest = RideRequest::find()->where(['driver_id' => $user_id])->count();
                        $rideRequestcount = RideRequest::find()->where(['driver_id' => $user_id])->andWhere(['status' => RideRequest::STATUS_RIDE_COMPLETED_PAID])->count();
                        $completedRideRequestcount = RideRequest::find()->where(['driver_id' => $user_id])->andWhere(['status' => RideRequest::STATUS_RIDE_COMPLETED_PAID])->count();
                        $totalKm = RideRequest::find()->where(['driver_id' => $user_id])->sum('final_distance');
                        $totalhours = RideRequest::find()->where(['driver_id' => $user_id])->sum('final_time');
                        $rejectRides = RejectLog::find()->where(['taxi_driver_id' => $user_id])->count();
                    }

                    $totalEarnings = round(RideEarnings::find()->where(['driver_id' => $user_id])->andWhere(['or',['!=', 'ride_earnings.type', RideEarnings::MANUL],['ride_earnings.type' => NULL]])->sum('driver_earning'), 2);

                    $sumRating = TaxiRating::find()->where(['taxi_driver_id' => $user_id])->sum('rating');
                    $countRating = TaxiRating::find()->where(['taxi_driver_id' => $user_id])->count();
                    if ($sumRating != 0 && $countRating != 0) {
                        $avgRating = $sumRating / $countRating;
                    } else {
                        $avgRating = 0;
                    }

                    $data['earnings']['today_earning'] = round($earningsSum, 2);
                    $data['earnings']['cash_colledcted'] = round($cashCollectionSum ?? '0', 2);
                    $data['earnings']['rides'] = $rideRequestcount;
                    $data['earnings']['completed_rides'] = $completedRideRequestcount;
                    $data['earnings']['total_km'] = round($totalKm, 2);
                    $data['earnings']['total_earnings'] = round($totalEarnings);
                    $data['earnings']['total_hours'] = round($totalhours, 2);
                    $data['earnings']['avg_rating'] = round($avgRating, 2);
                    $data['earnings']['reject_rides'] = $rejectRides;
                } else {
                    $rideQuery->andWhere(['between', 'created_on', '1790-01-01 00:00:00', '1790-01-01 00:00:00']);
                    $data['earnings']['today_earning'] = 0;
                    $data['earnings']['cash_colledcted'] = 0;
                    $data['earnings']['completed_rides'] = 0;
                    $data['earnings']['rides'] = 0;
                    $data['earnings']['total_km'] = 0;
                    $data['earnings']['total_earnings'] = 0;

                    $data['earnings']['total_hours'] = 0;
                    $data['earnings']['avg_rating'] = 0;
                    $data['earnings']['reject_rides'] = 0;
                }
                $newRidesupdate = new ActiveDataProvider([
                    'query' => $rideQuery,
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
                $rideList= [];
                foreach ($newRidesupdate->models as $ridesdata) {
                    $rideList[] = $ridesdata->asJson();
                }

                $data['status'] = self::API_OK;
                $data['details'] = $rideList;
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t("app", " No Earnings found");
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = Yii::t("app", " No user found");
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionMyEarnings

    // Driver Payout
    // [doc:start] actionPayout - Payout
    public function actionPayout()
    {
        $data = [];
        $dd = [];
        $user_id = $this->authUserId();
        $post = Yii::$app->request->post();

        if (!empty($user_id)) {

            try {
                $rideEarnings = RideEarnings::find()->where(['driver_id' => $user_id])->one();
                $start_date = $post['start_date'];
                $end_date = $post['end_date'];

                if (!empty($rideEarnings)) {
                    if (empty($start_date) && empty($end_date)) {
                        $dd['totalOnlineCollection'] = RideEarnings::find()->joinWith(['ride as rid'])->where(['ride_earnings.driver_id' => $user_id])->andWhere(['or',['!=', 'ride_earnings.type', RideEarnings::MANUL],['ride_earnings.type' => NULL]])->andWhere(['or', ['rid.payment_method' => RideRequest::PAYMENT_METHOD_ONLINE], ['rid.payment_method' => RideRequest::PAYMENT_METHOD_WALLET]])->sum('total_amount') ?? 0;
                        $dd['totalAdminCommissionByOnline'] = RideEarnings::find()->joinWith(['ride as rid'])->where(['ride_earnings.driver_id' => $user_id])->andWhere(['or',['!=', 'ride_earnings.type', RideEarnings::MANUL],['ride_earnings.type' => NULL]])->andWhere(['or', ['rid.payment_method' => RideRequest::PAYMENT_METHOD_ONLINE], ['rid.payment_method' => RideRequest::PAYMENT_METHOD_WALLET]])->sum('admin_earning') ?? 0;
                        $dd['totalDriverEarningByOnline'] = RideEarnings::find()->joinWith(['ride as rid'])->where(['ride_earnings.driver_id' => $user_id])->andWhere(['or',['!=', 'ride_earnings.type', RideEarnings::MANUL],['ride_earnings.type' => NULL]])->andWhere(['or', ['rid.payment_method' => RideRequest::PAYMENT_METHOD_PAY_ON_DROP], ['rid.payment_method' => RideRequest::PAYMENT_METHOD_WALLET]])->sum('driver_earning');
                        // Cash Earnings
                        $dd['totalCashCollection'] = round(RideEarnings::find()->joinWith(['ride as rid'])->where(['ride_earnings.driver_id' => $user_id])->andWhere(['or',['!=', 'ride_earnings.type', RideEarnings::MANUL],['ride_earnings.type' => NULL]])->andWhere(['rid.payment_method' => RideRequest::PAYMENT_METHOD_CASH])->sum('total_amount'), 2);

                        $dd['totalAdminCommissionByCash'] = round(RideEarnings::find()->joinWith(['ride as rid'])->where(['ride_earnings.driver_id' => $user_id])->andWhere(['or',['!=', 'ride_earnings.type', RideEarnings::MANUL],['ride_earnings.type' => NULL]])->andWhere(['rid.payment_method' => RideRequest::PAYMENT_METHOD_CASH])->sum('admin_earning'), 2);
                        $dd['totalDriverEarningByCash'] = round(RideEarnings::find()->joinWith(['ride as rid'])->where(['ride_earnings.driver_id' => $user_id])->andWhere(['or',['!=', 'ride_earnings.type', RideEarnings::MANUL],['ride_earnings.type'=> null]])->andWhere(['or',['!=', 'ride_earnings.type', RideEarnings::MANUL],['ride_earnings.type' => NULL]])->andWhere(['rid.payment_method' => RideRequest::PAYMENT_METHOD_CASH])->sum('driver_earning'), 2);
                        $dd['totalRideCollecetion'] = round(RideEarnings::find()->joinWith(['ride as rid'])->where(['ride_earnings.driver_id' => $user_id])->andWhere(['or',['!=', 'ride_earnings.type', RideEarnings::MANUL],['ride_earnings.type' => NULL]])->andWhere(['rid.status' => RideRequest::STATUS_RIDE_COMPLETED_PAID])->sum('total_amount'), 2);
                        $dd['availableBalance'] = (string)(new RideEarnings())->availableAmount($user_id, $start_date, $end_date);
                        $dd['total_earnings'] = round(RideEarnings::find()->where(['driver_id' => $user_id])->andWhere(['or',['!=', 'ride_earnings.type', RideEarnings::MANUL],['ride_earnings.type' => NULL]])->sum('driver_earning'), 2);

                        if ($dd['availableBalance'] < 0) {
                            $dd['is_negative'] = true;
                        } else {
                            $dd['is_negative'] = false;
                        }
                        $dd['tax_charges'] = 0;
                        $dd['transaction_fee'] = 0;
                        $dd['payable_amount'] = 0;
                        $settings = new WebSetting();
                        $minWithdrawAmount = $settings->getSettingBykey('min_withdraw');
                        $dd['min_payout_limit'] =  $minWithdrawAmount;
                        $dd['min_pending_amount'] =  0;
                        $dd['availableBalance'] = (string)(new RideEarnings())->availableAmount($user_id, $start_date, $end_date);
                    } else {
                        $dd['totalOnlineCollection'] = round(RideEarnings::find()->joinWith(['ride as rid'])->where(['ride_earnings.driver_id' => $user_id])->andWhere(['or',['!=', 'ride_earnings.type', RideEarnings::MANUL],['ride_earnings.type' => NULL]])->andWhere(['or', ['rid.payment_method' => RideRequest::PAYMENT_METHOD_PAY_ON_DROP], ['rid.payment_method' => RideRequest::PAYMENT_METHOD_WALLET]])->andWhere(['between', 'ride_earnings.updated_on', $start_date, $end_date])->sum('total_amount'), 2);
                        $dd['totalAdminCommissionByOnline'] = RideEarnings::find()->joinWith(['ride as rid'])->where(['ride_earnings.driver_id' => $user_id])->andWhere(['or',['!=', 'ride_earnings.type', RideEarnings::MANUL],['ride_earnings.type' => NULL]])->andWhere(['or', ['rid.payment_method' => RideRequest::PAYMENT_METHOD_PAY_ON_DROP], ['rid.payment_method' => RideRequest::PAYMENT_METHOD_WALLET]])->andWhere(['between', 'ride_earnings.updated_on', $start_date, $end_date])->sum('admin_earning');
                        $dd['totalDriverEarningByOnline'] = RideEarnings::find()->joinWith(['ride as rid'])->where(['ride_earnings.driver_id' => $user_id])->andWhere(['or',['!=', 'ride_earnings.type', RideEarnings::MANUL],['ride_earnings.type' => NULL]])->andWhere(['or', ['rid.payment_method' => RideRequest::PAYMENT_METHOD_PAY_ON_DROP], ['rid.payment_method' => RideRequest::PAYMENT_METHOD_WALLET]])->andWhere(['between', 'ride_earnings.updated_on', $start_date, $end_date])->sum('driver_earning');

                        // Cash Earnings
                        $dd['totalCashCollection'] = round(RideEarnings::find()->joinWith(['ride as rid'])->where(['ride_earnings.driver_id' => $user_id])->andWhere(['or',['!=', 'ride_earnings.type', RideEarnings::MANUL],['ride_earnings.type' => NULL]])->andWhere(['rid.payment_method' => RideRequest::PAYMENT_METHOD_CASH])->andWhere(['between', 'ride_earnings.updated_on', $start_date, $end_date])->sum('total_amount'), 2);

                        $dd['totalAdminCommissionByCash'] = round(RideEarnings::find()->joinWith(['ride as rid'])->where(['ride_earnings.driver_id' => $user_id])->andWhere(['or',['!=', 'ride_earnings.type', RideEarnings::MANUL],['ride_earnings.type' => NULL]])->andWhere(['rid.payment_method' => RideRequest::PAYMENT_METHOD_CASH])->andWhere(['between', 'ride_earnings.updated_on', $start_date, $end_date])->sum('admin_earning'), 2);

                        $totalDriverEarningByCash = RideEarnings::find()->joinWith(['ride as rid'])->where(['ride_earnings.driver_id' => $user_id])->andWhere(['rid.status' => RideRequest::STATUS_RIDE_COMPLETED_PAID])->andWhere(['ride_earnings.payment_method' => RideRequest::PAYMENT_METHOD_CASH])->sum('driver_earning');
                        $totalRideCollecetion = RideEarnings::find()->joinWith(['ride as rid'])->where(['ride_earnings.driver_id' => $user_id])->andWhere(['rid.status' => RideRequest::STATUS_RIDE_COMPLETED_PAID])->sum('total_amount');

                        $dd['totalDriverEarningByCash'] =round($totalDriverEarningByCash,2);
                        $dd['totalRideCollecetion'] =round($totalRideCollecetion,2);

                        $dd['availableBalance'] = (string)(new RideEarnings())->availableAmount($user_id, $start_date, $end_date);
                        $dd['total_earnings'] = round(RideEarnings::find()->where(['driver_id' => $user_id])->andWhere(['or',['!=', 'ride_earnings.type', RideEarnings::MANUL],['ride_earnings.type' => NULL]])->andWhere(['between', 'ride_earnings.updated_on', $start_date, $end_date])->sum('driver_earning'), 2);

                        if ((float)$dd['availableBalance'] < 0) {
                            $dd['is_negative'] = true;
                        } else {
                            $dd['is_negative'] = false;
                        }
                        $dd['tax_charges'] = 0;
                        $dd['transaction_fee'] = 0;
                        $dd['payable_amount'] = 0;
                        $settings = new WebSetting();
                        $minWithdrawAmount = $settings->getSettingBykey('min_withdraw');
                        $dd['min_payout_limit'] =  $minWithdrawAmount;
                        $dd['min_pending_amount'] =  0;
                    }
                    // Online Earnings

                    $data['status'] = self::API_OK;
                    $data['details'] = $dd;
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = "No earnings found";
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
    // [doc:end] actionPayout

    // Ride Completion log

    // [doc:start] actionRideCompletionLog - Ride Completion Log
    public function actionRideCompletionLog()
    {
        $data = [];
        $dd = [];
        $user_id = $this->authUserId();
        $post = Yii::$app->request->post();
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

                $data['status'] = self::API_NOK;
                $data['error'] = $e->getMessage();

            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = "No user found";
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionRideCompletionLog

    // Driver Detail

    // [doc:start] actionDriverDetail - Driver Detail
    public function actionDriverDetail()
    {
        $data = [];
        $dd = [];
        $user_id = $this->authUserId();
        $post = Yii::$app->request->post();

        if (!empty($user_id)) {

            try {

                $taxiDetails = DriverDetails::find()->where(['user_id' => $user_id])->one();
                if (!empty($taxiDetails)) {
                    $data['status'] = self::API_OK;
                    $data['details'] = $taxiDetails->ProfileDetailsJson();
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
    // [doc:end] actionDriverDetail

    //

    // [doc:start] actionAddRating - Add Rating
    public function actionAddRating()
    {
        $data = [];
        $user_id = $this->authUserId();
        $post = Yii::$app->request->post();

        if (!empty($user_id)) {

            $ride_id = $post['ride_id'];
            $user_ids = $post['user_id'];
            $rating = $post['rating'];
            $user_message = $post['user_message'];

            try {
                $taxiRating = TaxiRating::find()->where(['user_id' => $user_id])->orWhere(['taxi_driver_id' => $user_id])->one();
                if (empty($taxiRating)) {
                    $taxiRating = new TaxiRating();
                    $taxiRating->taxi_driver_id = $rating;
                    $taxiRating->user_id = $user_id;
                    $taxiRating->ride_id = $ride_id;
                    $taxiRating->user_rating = $rating;
                    $taxiRating->user_message = $user_message;
                    $taxiRating->status = TaxiRating::STATUS_ACTIVE;
                } else {
                    $taxiRating->taxi_driver_id = $user_id;
                    $taxiRating->user_id = $user_ids;
                    $taxiRating->ride_id = $ride_id;
                    $taxiRating->user_message = $user_message;
                    $taxiRating->user_rating = $rating;
                    $taxiRating->status = TaxiRating::STATUS_ACTIVE;
                }

                if ($taxiRating->save(false)) {
                    $data['status'] = self::API_OK;
                    $data['details'] = "Review Added Succesfully";
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

    //

    // [doc:start] actionMyReviews - My Reviews
    public function actionMyReviews()
    {
        $data = [];
        $driver_id = $this->authUserId();
        $post = Yii::$app->request->post();

        $today = date('Y-m-d 00:00:00');
        $end_date = date('Y-m-d 00:00:00', strtotime($today . ' +1 day'));
        if (!empty($driver_id)) {

            try {
                $page = isset($post['page']) ? $post['page'] : 0;

                $query = TaxiRating::find()
                    ->Where(['taxi_driver_id' => $driver_id])->andWhere(['status' => TaxiRating::STATUS_ACTIVE]);

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

                        $list[] = $driver_order->asJson();
                    }
                    $data['status'] = self::API_OK;

                    $data['details'] = $list;
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
    // [doc:end] actionMyReviews

    // Pay admin Charges

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
        $user_id = $this->authUserId();

        if (!empty($user_id)) {
            try {
                $amount = $post['amount'];

                if (!empty($amount)) {
                    $user = User::find()->where(['id' => $user_id])->one();
                    $dd['contact_no'] = !empty($user->contact_no) ? $user->contact_no : "999999999";
                    $dd['email_id'] = !empty($user->email) ? $user->email : "test@gmail.com";
                    $dd['final_price'] = $amount;

                    $generateToken = (new OpenMoney())->generateToken($dd);

                    $decodeToken = json_decode($generateToken);
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
        $user_id = $this->authUserId();

        if (!empty($user_id)) {
            try {

                $orderId = $post['orderId'];

                $CashFree = new CashFree();
                $CreateOrders =   $CashFree->verifyPayment($orderId);
                $verifyPayment =  json_decode($CreateOrders);
                if(!empty($verifyPayment->order_status) && $verifyPayment->order_status=='PAID') {

                    $taxiPayout = new TaxiPayout();
                    $taxiPayout->driver_id = $user_id;
                    $taxiPayout->amount = $verifyPayment->order_amount;
                    $taxiPayout->payment_type = TaxiPayout::PAYMENT_TYPE_CREDIT;
                    $taxiPayout->method_reason = "Payment of outstanding amount to easygo";
                    $taxiPayout->status = TaxiPayout::STATUS_APPROVED;
                    if ($taxiPayout->save(false)) {
                        $data['status'] = self::API_OK;
                        $data['details'] = Yii::t("app", "Payment Success");
                        $data['payment_status'] = $taxiPayout->status;
                    }

                  }else{
                  $data['status'] = self::API_NOK;
                  $data['error'] = $verifyPayment;
                }

            } catch (Exception $e) {

                $data['status'] = self::API_NOK;
                $data['error'] =$e->getMessage();

            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = Yii::t("app", "No User found");
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionUpdatePaymentStatus

    // My Earnings Details
    // [doc:start] actionMyEarningsDetails - My Earnings Details
    public function actionMyEarningsDetails($ride_id)
    {
        $data = [];
        $post = Yii::$app->request->post();
        $user_id = $this->authUserId();

        if (!empty($user_id)) {
            try {
                $rideRequesst = RideRequest::find()->where(['id' => $ride_id])->one();
                if (!empty($rideRequesst)) {
                    $data['status'] = self::API_NOK;
                    $data['details'] = $rideRequesst->earningDetailsJson();
                } else {

                    $data['status'] = self::API_NOK;
                    $data['error'] = Yii::t("app", " Invalid ride Id");
                }
            } catch (Exception $e) {
                return $e->getMessage();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = Yii::t("app", " No user found");
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionMyEarningsDetails

    // [doc:start] actionCancelRide - The taxi driver gives up a ride they accepted.
    /**
     * The taxi driver gives up a ride they accepted.
     *
     * POST /api/taxi/cancel-ride   ride_id, reject_reason
     * Only this driver's own accepted ride. The rider is told, a reject_log row is written and the driver's wallet is
     * debited the taxi cancellation charge (Web Settings: taxi_cancellation_charge).
     */
    public function actionCancelRide()
    {
        $data = [];
        $user_id = $this->authUserId();
        $post = Yii::$app->request->post();
        $rideId = $post['ride_id'];

        if (!empty($user_id)) {

            try {
                $rideRequest = RideRequest::find()
                    // OWNERSHIP AND STATE: only this driver's own, still-accepted ride.
                    ->where(['id' => $rideId, 'driver_id' => $user_id])
                    ->andWhere(['status' => RideRequest::STATUS_ACCEPTED_BY_DRIVER])
                    ->one();
                if (!empty($rideRequest)) {
                    $rideRequest->status = RideRequest::STATUS_CANCEL_BY_DRIVER;
                    if ($rideRequest->save(false)) {
                        $rejectLog = new RejectLog();
                        $rejectLog->taxi_driver_id = $user_id;
                        $rejectLog->ride_id = $rideId;
                        $rejectLog->reject_reason = $post['reject_reason'];
                        $rejectLog->status = RejectLog::STATUS_ACTIVE;
                        $rejectLog->save(false);
                        $title = "Ride Cancelled";
                        $body = "Ride Cancelled By Driver";
                        $send_noti = Yii::$app->push->notifyUser('', $rideRequest->user_id, $title, $body);
                        $data['status'] = self::API_OK;
                        $data['detail'] = "Ride cancelled Successfully";
                        $setting = new WebSetting();
                        // PENALTY: the configured cancellation charge is taken from the driver's wallet.
                        $taxiCancellationCharge = $setting->getSettingBykey('taxi_cancellation_charge');
                        if(!empty($taxiCancellationCharge) && $taxiCancellationCharge!= '0')
                        {
                            $driverWallet = new DriverWallet();
                            $driverWallet->driver_id = $user_id;
                            $driverWallet->amount = $taxiCancellationCharge;
                            $driverWallet->payment_type = DriverWallet::payment_type_debit;
                            $driverWallet->method_reason = "Amount Rs ".$taxiCancellationCharge." due to booking cancellation booking id ".$rideId;
                            $driverWallet->status = DriverWallet::STATUS_SUCCESS;
                            $driverWallet->created_on = Date('Y-m-d H:i:s');
                            $driverWallet->create_user_id  = $user_id;
                            $driverWallet->save(false);
                        }
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = "Please try again";
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = "Invalid Ride Id";
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
    // [doc:end] actionCancelRide

    // Withdraw request

    // [doc:start] actionWithdrawRequest - Withdraw Request
    public function actionWithdrawRequest()
    {
        $data = [];
        $user_id = $this->authUserId();
        $post = Yii::$app->request->post();

        $amount =  $post['amount'];
        $settings = new WebSetting();
        $minWithdrawAmount = $settings->getSettingBykey('min_withdraw');
        if (!empty($user_id)) {
            $todayDate = date('Y-m-d 00:00:00');
            $preDate =  date("Y-m-d 00:00:00", strtotime("- 1 day"));
            $tommorow =  date("Y-m-d 00:00:00", strtotime("+ 1 day"));

            try {
                $payout = TaxiPayout::find()->where(['driver_id' => $user_id])->andWhere(['is_withdraw_request' => TaxiPayout::WITHDRAW_REQUEST])->andWhere(['between', 'created_on', $preDate, $tommorow])->one();

                if (empty($payout)) {
                    if ((float)$amount >= (float)$minWithdrawAmount) {
                        $driverDetails = DriverDetails::find()->where(['user_id' => $user_id])->one();

                        $withDraw = new TaxiPayout();
                        $withDraw->driver_id = $user_id;
                        $withDraw->amount = $amount;
                        $withDraw->payment_type = TaxiPayout::PAYMENT_TYPE_DEBIT;
                        $withDraw->method_reason = "Withdraw Request of amount" . $amount;
                        $withDraw->is_withdraw_request = TaxiPayout::WITHDRAW_REQUEST;
                        $withDraw->status = TaxiPayout::STATUS_PROCESSING;
                        if(!empty($driverDetails))
                        {
                            $withDraw->vehicle_type = $driverDetails->vehical_id;
                            $withDraw->city_id = $driverDetails->city_id;
                        }
                        if ($withDraw->save(false)) {
                            $data['status'] = self::API_OK;
                            $data['details'] = "Withdraw Request Send Succesfully";
                        }
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = "You can'nt withdraw amount minimum with amount is: " . $minWithdrawAmount;
                    }

                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = "You alredy have a withdraw request today. Please try again tommorow";
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
    // [doc:end] actionWithdrawRequest

    // [doc:start] actionWithdrawRequestList - Withdraw Request List
    public function actionWithdrawRequestList()
    {
        $data = [];
        $user_id = $this->authUserId();
        $post = Yii::$app->request->post();

        $today = date('Y-m-d 00:00:00');
        $end_date = date('Y-m-d 00:00:00', strtotime($today . ' +1 day'));

        if (!empty($user_id)) {

            try {
                $status = $post['status'];

                $page = isset($post['page']) ? $post['page'] : 0;

                $query = TaxiPayout::find()
                    ->Where(['driver_id' => $user_id])->andWhere(['is_withdraw_request' => TaxiPayout::WITHDRAW_REQUEST])->andWhere(['status' => $status]);

                $driver_orders = new ActiveDataProvider([
                    'query' => $query,
                    'sort' => [
                        'defaultOrder' => [
                            'updated_on' => SORT_ASC,
                        ],
                    ],
                    'pagination' => array(
                        'page' => $page,
                        'pageSize' => 20,
                    ),
                ]);
                if (!empty($driver_orders->getModels())) {
                    foreach ($driver_orders->getModels() as $driver_order) {

                        $list[] = $driver_order->asJson();
                    }
                    $data['status'] = self::API_OK;

                    $data['details'] = $list;
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
    // [doc:end] actionWithdrawRequestList

    // [doc:start] actionReferUser - Refer User
    public function actionReferUser($code)
    {

        $post = Yii::$app->request->post();
        $user_id = $this->authUserId();

        $data = [];
        // Check mac id exist or not

        if ((!User::isAdmin()) && !empty($user_id)) {

            $profile = User::find()->where(['id' => $user_id,'referal_id' => 0])->one();

            if ($profile) {

                $referUser = User::find()->Where(['referal_code' => $code])->one();

                if (!empty($referUser && $referUser->id != $user_id)) {

                    $profile->referal_id = $referUser->id;

                    if ($profile->save(false)) {
                        if (!empty($profile->referal_id)) {

                            $setting = new WebSetting();

                            $refer_bonus = \app\modules\geo\services\ServiceModules::setting('user_referral_earning', \app\modules\geo\services\ServiceModules::ofVehicle((new \yii\db\Query())->select('vehical_id')->from('driver_details')->where(['user_id' => $profile->id])->scalar()));
                            $referral_bonus_user = $setting->getSettingBykey('referral_bonus_user');

                            $transaction = new DriverWallet();

                            $transaction->driver_id  = $profile->referal_id;

                            $transaction->payment_type = DriverWallet::payment_type_credit;

                            $transaction->amount = isset($refer_bonus) ? $refer_bonus : 0; //rand(1,3);//$refer_bonus->value;

                            $transaction->status = DriverWallet::STATUS_SUCCESS;
                            $transaction->type_id = DriverWallet::type_referral_earning;

                            $transaction->method_reason = 'Congratulations..!You earned cashback on your referral user' . $profile->referal_id;

                            if (!$transaction->save(false)) {

                                Yii::error('wallet entry not saved: ' . json_encode($transaction->getErrors()), 'api');
                                throw new \RuntimeException('Could not save the entry');
                            } else {

                                $title = 'Congratulations...!!!You earned Cashback';

                                $body = 'Congratulations..!You earned Cashback on your referral.';

                                //Send Notifciation

                            }
                            // Add cashback to user who using referal code
                            $transaction = new DriverWallet();

                            $transaction->user_id = $user_id;

                            $transaction->payment_type = DriverWallet::payment_type_credit;

                            $transaction->amount = isset($referral_bonus_user) ? $referral_bonus_user : 0; //rand(1,3);//$refer_bonus->value;

                            $transaction->status = DriverWallet::STATUS_SUCCESS;

                            $transaction->type_id = DriverWallet::type_referral_earning;

                            $transaction->method_reason = 'Congratulations..!You earned cashback on for using referal code of user #' . $profile->referal_id;

                            if (!$transaction->save(false)) {

                                Yii::error('wallet entry not saved: ' . json_encode($transaction->getErrors()), 'api');
                                throw new \RuntimeException('Could not save the entry');
                            } else {

                                $title = 'Congratulations...!!!You earned Rederal Bonus';

                                $body = 'Congratulations..!You earned Referal Bonus for applying referal code';

                                //Send Notifciation

                            }
                        }
                    }

                    $data['status'] = SELF::API_OK;

                    $data['details'] = $referUser->asJson();
                } else {

                    $data['status'] = SELF::API_NOK;

                    $data['error'] = 'Invalid Referal Code';
                }
            } else {
                $data['status'] = self::API_NOK;

                $data['error'] = 'Referal code already added';
            }
        } else {
            $data['status'] = self::API_NOK;

            $data['error'] = 'User id not found';
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionReferUser
    // [doc:start] actionMyReferal - My Referal
    public function actionMyReferal()
    {
        $data = [];
        $post = Yii::$app->request->post();
        $user_id = $this->authUserId();

        if (!empty($user_id)) {
            $referals = User::find()->where(['referal_id' => $user_id])->all();

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

    // [doc:start] actionCreateOrder - Create Order
    public function actionCreateOrder(){

        $data = [];
        try {
            $user_id = $this->authUserId();
            if (!empty($user_id)) {
                $post = Yii::$app->request->post();
                $order_amount = !empty($post['order_amount'])?$post['order_amount']:'';

                $user = User::find()->where(['id'=>$user_id])->one();
                $CashFree = new CashFree();
                $userData['name'] = rand(1111,9999);
                $userData['email'] = $user->email;
                $userData['customer_phone'] = $user->contact_no;
                $userData['order_id'] = rand(111111,999999);
                $userData['order_amount'] = $order_amount;
               $CreateOrders =   $CashFree->CreateOrders($userData);
              $CreateOrders_decoded =  json_decode($CreateOrders);
              if(!empty($CreateOrders_decoded->cf_order_id)){
                $data['status'] = self::API_OK;
                $data['details'] = $CreateOrders_decoded;
              }else{
                $data['status'] = self::API_NOK;
                $data['error'] = $CreateOrders_decoded;
              }

            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t("app", "User not found");
            }
        } catch(Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }

        return $this->sendJsonResponse($data);

    }
    // [doc:end] actionCreateOrder

    // [doc:start] actionAddBalance - Add Balance
    public function actionAddBalance()
    {
        $data = [];
        try {
            $post = \Yii::$app->request->post();
            if ($post) {
                $orderId = !empty($post['orderId'])?$post['orderId']:'';
                $user_id = $this->authUserId();
                if (!empty($user_id)) {
                    $user = User::find()->where(['id' => $user_id])->one();
                    if (!empty($user)) {

                $CashFree = new CashFree();

                $CreateOrders =   $CashFree->verifyPayment($orderId);
                $verifyPayment =  json_decode($CreateOrders);

                if(!empty($verifyPayment->order_status) && $verifyPayment->order_status=='PAID') {
                    $amount = $verifyPayment->order_amount;

                    $driver_wallet = new DriverWallet();
                    $driver_wallet->driver_id = $user_id;
                    $driver_wallet->amount = $amount;
                    $driver_wallet->payment_type =DriverWallet::payment_type_credit;
                    $driver_wallet->method_reason ='Amount credit ' . $amount;
                    $driver_wallet->status =DriverWallet::STATUS_SUCCESS;
                    if ($driver_wallet->save(false)) {
                        $data['status'] = self::API_OK;
                        $data['details'] = $driver_wallet->asJson();
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'Wallet added failed';
                    }

                }else{
                  $data['status'] = self::API_NOK;
                  $data['error'] = "payment failed";
                }

                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'Wrong User';
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = 'User Not Found';
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = 'No data posted.';
            }
        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionAddBalance

    // [doc:start] actionAvailableBalance - Available Balance
    public function actionAvailableBalance()
    {
        $data = [];
        try {
            $post = \Yii::$app->request->post();

            $user_id = $this->authUserId();
            if (!empty($user_id)) {
                $user = User::find()
                    ->where(['id' => $user_id])
                    ->one();
                if (!empty($user)) {
                    $availableBalance = DriverWallet::availableBalance($user->id);
                    $data['status'] = self::API_OK;
                    $data['details'] = $availableBalance;
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = 'Wrong User';
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = 'User Not Found';
            }
        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionAvailableBalance

    // [doc:start] actionTransactionList - Transaction List
    public function actionTransactionList()
    {
        $data = [];
        try {
            $post = \Yii::$app->request->post();
            $page = !empty($post['page']) ? $post['page'] : 0;
            $start_date = !empty($post['start_date'])? $post['start_date']: '';
            $end_date = !empty($post['end_date']) ? $post['end_date'] : '';
            $user_id = $this->authUserId();
            if (!empty($user_id)) {
                $user = User::find()->where(['id' => $user_id])->one();
                if (!empty($user)) {
                    $driver_wallet_query = DriverWallet::find()->where(['driver_id'=>$user_id]);

                    if (!empty($start_date) && !empty($end_date)) {
                        $driver_wallet_query->andWhere(['between','created_on',$start_date,$end_date]);
                    }

                    $DriverWallet = new ActiveDataProvider([
                        'query' => $driver_wallet_query,
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

                    if (!empty($DriverWallet)) {
                        foreach ($DriverWallet->models as $DriverWalletData) {
                            $list[] = $DriverWalletData->asJson();
                        }
                        if (!empty($list)) {
                            $data['status'] = self::API_OK;
                            $data['details'] = $list;
                        } else {
                            $data['status'] = self::API_NOK;
                            $data['error'] = 'Transaction data not found';
                        }
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'Transaction data not found';
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = 'Wrong User';
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = 'User Not Found';
            }
        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionTransactionList

    // [doc:start] actionWithdraw - Withdraw
    public function actionWithdraw()
    {
        $data = [];
        try {
            $post = \Yii::$app->request->post();
            $amount = !empty($post['amount']) ? $post['amount'] : 0;
            $user_id = $this->authUserId();
            if (!empty($user_id)) {
                $user = User::find()->where(['id' => $user_id])->one();
                if (!empty($user)) {
                    $availableBalance = DriverWallet::availableBalance($user->id);
                    if ($availableBalance > $amount && !empty($amount)) {
                        $settings = new WebSetting();
                        $minWithdrawAmount = $settings->getSettingBykey('min_withdraw');
                        if ((float)$amount >= (float)$minWithdrawAmount) {
                                $driver_wallet = new DriverWallet();
                                $driver_wallet->driver_id = $user_id;
                                $driver_wallet->amount = $amount;
                                $driver_wallet->payment_type =DriverWallet::payment_type_debit;
                                $driver_wallet->method_reason ='Amount withdraw ' . $amount;
                                $driver_wallet->status = DriverWallet::STATUS_PENDING;
                                if ($driver_wallet->save(false)) {
                                $withDraw = new TaxiPayout();
                                $withDraw->driver_id = $user_id;
                                $withDraw->amount = $amount;
                                $withDraw->payment_type = TaxiPayout::PAYMENT_TYPE_DEBIT;
                                $withDraw->method_reason = "Withdraw Request of amount" . $amount;
                                $withDraw->is_withdraw_request = TaxiPayout::WITHDRAW_REQUEST;
                                $withDraw->status = TaxiPayout::STATUS_PROCESSING;
                                $driverDetails = DriverDetails::find()->where(['user_id' => $user_id])->one();
                                if(!empty($driverDetails))
                                {
                                    $withDraw->city_id = $driverDetails->city_id;
                                    $withDraw->vehical_id = $driverDetails->vehical_id;
                                }
                                if ($withDraw->save(false)) {
                                    $data['status'] = self::API_OK;
                                    $data['details'] = "Withdraw Request Send Succesfully";
                                }

                            $data['status'] = self::API_OK;
                            $data['details'] = $driver_wallet->asJson();
                        } else {
                            $data['status'] = self::API_NOK;
                            $data['error'] = 'Wallet withdraw failed';
                        }

                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = "You can'nt withdraw amount minimum with amount is: " . $minWithdrawAmount;
                    }

                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'Available amount is ' . $availableBalance;
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = 'Wrong User';
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = 'User Not Found';
            }
        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionWithdraw

    // [doc:start] actionAddDriverEmergencyContacts - Add Driver Emergency Contacts
    public function actionAddDriverEmergencyContacts()
    {
        $data = [];
        $post = \Yii::$app->request->post();

        if ($post) {
            $user_id = $this->authUserId();
            if (!empty($user_id)) {
                $user = User::find()
                    ->where(['id' => $user_id])
                    ->one();
                if (!empty($user)) {
                    $person_name_one = !empty($post['person_name_one'])? $post['person_name_one']: '';
                    $person_phone_number_one = !empty(
                        $post['person_phone_number_one']
                    )
                        ? $post['person_phone_number_one']
                        : '';
                    $person_relationship_one = !empty(
                        $post['person_relationship_one']
                    )
                        ? $post['person_relationship_one']
                        : '';
                    $aadhar_number_one = !empty($post['aadhar_number_one'])
                        ? $post['aadhar_number_one']
                        : '';

                    $person_name_two = !empty($post['person_name_two'])
                        ? $post['person_name_two']
                        : '';
                    $person_phone_number_two = !empty(
                        $post['person_phone_number_two']
                    )
                        ? $post['person_phone_number_two']
                        : '';
                    $person_relationship_two = !empty(
                        $post['person_relationship_two']
                    )
                        ? $post['person_relationship_two']
                        : '';
                    $aadhar_number_two = !empty($post['aadhar_number_two'])
                        ? $post['aadhar_number_two']
                        : '';

                    $user_emergency_contacts = DriverEmergencyContacts::find()
                        ->where(['driver_id' => $user])->one();

                    if (!empty($user_emergency_contacts)) {
                        $user_emergency_contacts->name = $person_name_one;
                        $user_emergency_contacts->driver_id = $user_id;

                        $user_emergency_contacts->phone_number = $person_phone_number_one;
                        $user_emergency_contacts->relationship = $person_relationship_one;
                        $user_emergency_contacts->aadhar_number = $aadhar_number_one;

                        $user_emergency_contacts->status =
                            DriverEmergencyContacts::STATUS_ACTIVE;
                        $user_emergency_contacts->save(false);
                        $data['status'] = self::API_OK;
                        $data['details'] = 'Emergency contact updated';
                        $user->form_steps = User::EMERGENCY_CONTACT;
                        $user->save(false);
                    } else {
                        $user_emergency_contacts_one = new DriverEmergencyContacts();
                        $user_emergency_contacts_one->name = $person_name_one;
                        $user_emergency_contacts_one->driver_id = $user_id;

                        $user_emergency_contacts_one->phone_number = $person_phone_number_one;
                        $user_emergency_contacts_one->relationship = $person_relationship_one;
                        $user_emergency_contacts_one->aadhar_number = $aadhar_number_one;

                        $user_emergency_contacts_one->status =
                            DriverEmergencyContacts::STATUS_ACTIVE;
                        $user_emergency_contacts_one->save(false);

                        $user->form_steps = User::EMERGENCY_CONTACT;
                        $user->save(false);
                        $data['status'] = self::API_OK;
                        $data['details'] =
                            'Emergency contact updated successfully';
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = 'Wrong User';
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = 'User Not Found';
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = 'No data posted.';
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionAddDriverEmergencyContacts

    // [doc:start] actionSkipperStatus - Skipper Status
    /** @deprecated old route name (skipper-status); use taxi-status. */
    public function actionSkipperStatus()
    {
        return $this->actionTaxiStatus();
    }
    // [doc:end] actionSkipperStatus

    // [doc:start] actionUpdateSkipperCity - Update Skipper City
    /** @deprecated old route name (update-skipper-city); use update-taxi-city. */
    public function actionUpdateSkipperCity()
    {
        return $this->actionUpdateTaxiCity();
    }}
    // [doc:end] actionUpdateSkipperCity
