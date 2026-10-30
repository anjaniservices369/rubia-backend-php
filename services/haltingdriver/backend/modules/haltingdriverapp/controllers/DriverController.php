<?php

namespace app\modules\haltingdriverapp\controllers;

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
use app\modules\admin\models\Booking;
use app\modules\admin\models\BookingLogs;
use app\modules\admin\models\DriverAadharCard;
use app\modules\admin\models\DriverBankDetails;
use app\modules\admin\models\DriverBookingRatings;
use app\modules\admin\models\DriverEarnings;
use app\modules\admin\models\DriverEmergencyContacts;
use app\modules\admin\models\DriverPanCards;
use app\modules\admin\models\DriverRequests;
use app\modules\admin\models\DriverVehicleTypes;
use app\modules\admin\models\DriverWallet;
use app\modules\admin\models\DrivingLicense;
use app\modules\admin\models\FcmNotification;
use app\modules\admin\models\Notification;
use app\modules\admin\models\Services;
use app\modules\admin\models\UserBookingRatings;
use app\modules\admin\models\UserEmergencyContacts;
use app\modules\admin\models\VehicleTypes;
use app\modules\admin\models\VehicleVerification;
use app\modules\admin\models\DriverReferralEarnings;
use app\modules\admin\models\Plans;
use app\modules\admin\models\DriverSubscription;
use app\modules\admin\models\base\UserWallet;
 use app\modules\admin\models\base\City;
use app\modules\payments\services\CashFree;
use app\modules\admin\models\base\WebSetting as BaseWebSetting;
use DateTime;
use Exception;
use kartik\mpdf\Pdf;
use app\modules\geo\services\DrivingDistance;
use app\modules\admin\models\PaymentLog;
 use app\modules\admin\models\base\DriverCancelDuplicate;
use app\modules\admin\models\base\CancelRideCharges;
use app\modules\admin\models\CityRateCard;
use yii\helpers\Json;

/**
 * Partner (driver) app API: /api/driver/*  - the DRIVER SERVICE (hire a driver for N hours).
 *
 * Who calls it: the Partner app (com.rubia.driver). Every action authenticates with the `auth_code` header and
 * answers {status: OK|NOK, details|error}.
 *
 * Actions, in the order a driver meets them:
 *   login ......... send-otp, resend-otp, verify-otp, logout, check (is my session still valid + my profile)
 *   onboarding .... update-profile, upload-profile-picture, driving-license, add-aadhar, add-pan-card,
 *                   police-verification, driver-bank-details-add, driver-vehicle-types-add, pay-joining-fee
 *   presence ...... driver-status (go online/offline), update-lat-lng (live position)
 *   rides ......... bookings (offers + accepted), accept-ride, reject-ride, go-for-pickup, notify-arrived,
 *                   arrived-location (customer OTP), vehicle-verification (photos), start-ride, complete-ride,
 *                   collect-cash, cancel-ride, add-review, bookings-history, view-booking-details
 *   money ......... available-balance, transaction-list, add-balance, withdraw, earnings-dash-board
 *
 * Rules enforced here (see DEVELOPER_GUIDE.md section 3): ride steps work only for the driver the booking is
 * assigned to and only from the right previous state (ownedBooking()); one ride at a time; minimum wallet balance.
 */
class DriverController extends BKController
{
    // [doc:start] behaviors - Behaviors
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'corsFilter' => [
                'class' => \yii\filters\Cors::className(),
                'cors' => [
                    // restrict access to
                    'Origin' => [
                        'http://localhost:*',
                        'http://localhost:58600',
                    ],
                    // Allow only POST and PUT methods
                    'Access-Control-Request-Method' => ['POST', 'PUT'],
                    // Allow only headers 'X-Wsse'
                    'Access-Control-Request-Headers' => ['X-Wsse'],
                    // Allow credentials (cookies, authorization headers, etc.) to be exposed to the browser
                    'Access-Control-Allow-Credentials' => true,
                    // Allow OPTIONS caching
                    'Access-Control-Max-Age' => 3600,
                    // Allow the X-Pagination-Current-Page header to be exposed to the browser.
                    'Access-Control-Expose-Headers' => [
                        'X-Pagination-Current-Page',
                    ],
                ],
            ],

            'access' => [
                'class' => AccessControl::className(),
                'ruleConfig' => [
                    'class' => AccessRule::className(),
                ],

                'rules' => [
                    [
                        'actions' => [
                            'check',
                            'verify-otp',
                            'send-otp',
                            'legal-page',
                            'resend-otp',
                            'index',
                            'update-profile',
                            'delete-account',
                            'add-driver-emergency-contacts',
                            'update-lat-lng',
                            'apply-referral',
                            'vehicle-types',
                            'driving-license',
                            'add-aadhar',
                            'add-pan-card',
                            'police-verification',
                            'pay-joining-fee',
                            'driver-bank-details-add',
                            'driver-vehicle-types-add',
                            'add-balance',
                            'transaction-list',
                            'withdraw',
                            'withdraw-info',
                            'available-balance',
                            'driver-status',
                            'upload-profile-picture',
                            'bookings',
                            'accept-ride',
                            'reject-ride',
                            'vehicle-verification',
                            'go-for-pickup',
                            'arrived-location',
                            'complete-ride',
                            'cancel-ride',
                            'add-review',
                            'rating-and-reviews',
                            'view-booking-details',
                            'start-ride',
                            'credit-ride-earnings',
                            'bookings-history',
                            'list-fcm-notifications',
                            'clear-notifications',
                            'earnings-dash-board',
                            'earnings-by-order',
                            'create-phonepe-order',
                          	'create-order',
                            'verify-payment',
                            'my-referral-earings',
                            'plan',
                            'subscribe-now',
                            'my-earnings',
                            'collect-cash',
                            'my-earnings-dash-board',
                            'logout',
                            'automatic-ride-cancel',
                            'update-odometer-start',
                            'update-odometer-end',
                            'handle-response',
                            'notify-arrived',
                          'auto-cancel-subscription',
                           'current-city',
                            'razorpay-create-order',
                            'cashfree-create-order',
                            'razorpay-verify',
                            'razorpay-webhook',
                            'onboarding-status',
                            'payment-options',
                            'razorpay-create-qr',
                            'razorpay-qr-status',


                        ],

                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => [
                            'check',
                            'verify-otp',
                            'send-otp',
                            'legal-page',
                            'resend-otp',
                            'index',
                            'update-profile',
                            'delete-account',
                            'add-driver-emergency-contacts',
                            'update-lat-lng',
                            'apply-referral',
                            'vehicle-types',
                            'driving-license',
                            'add-aadhar',
                            'add-pan-card',
                            'police-verification',
                            'pay-joining-fee',
                            'driver-bank-details-add',
                            'driver-vehicle-types-add',
                            'add-balance',
                            'transaction-list',
                            'withdraw',
                            'withdraw-info',
                            'available-balance',
                            'driver-status',
                            'upload-profile-picture',
                            'bookings',
                            'accept-ride',
                            'reject-ride',
                            'vehicle-verification',
                            'go-for-pickup',
                            'arrived-location',
                            'complete-ride',
                            'cancel-ride',
                            'add-review',
                            'rating-and-reviews',
                            'view-booking-details',
                            'start-ride',
                            'credit-ride-earnings',
                            'bookings-history',
                            'list-fcm-notifications',
                            'clear-notifications',
                            'earnings-dash-board',
                            'earnings-by-order',
                            'create-phonepe-order',
                          	'create-order',
                            'verify-payment',
                            'my-referral-earings',
                            'plan',
                            'subscribe-now',
                            'my-earnings',
                            'collect-cash',
                            'my-earnings-dash-board',
                            'logout',
                            'automatic-ride-cancel',
                            'update-odometer-start',
                            'update-odometer-end',
                            'handle-response',
                            'notify-arrived',
                          'auto-cancel-subscription',
                          'current-city',
                            'razorpay-create-order',
                            'cashfree-create-order',
                            'razorpay-verify',
                            'razorpay-webhook',
                            'onboarding-status',
                            'payment-options',
                            'razorpay-create-qr',
                            'razorpay-qr-status',

                        ],

                        'allow' => true,
                        'roles' => ['?', '*'],
                    ],
                ],
            ],
        ]);
    }
    // [doc:end] behaviors

    // [doc:start] actionIndex - /api/driver/index - Index
    public function actionIndex()
    {
        $data['details'] = ['hello'];
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionIndex

    // [doc:start] actionLogout - /api/driver/logout - Logout
    public function actionLogout()
    {
        $data = [];
        $headers = isset(\Yii::$app->request->headers['auth_code'])
            ? \Yii::$app->request->headers['auth_code']
            : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            $model = AuthSession::find()
                ->where(['create_user_id' => $user_id])
                ->one();
            if (!empty($model)) {
                $model->delete();
                if (Yii::$app->user->logout(false)) {
                    $data['status'] = self::API_OK;
                }
                $data['details'] = ['Successfully Logged Out'];
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = [];
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = ['User Not Found'];
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionLogout

        // [doc:start] actionCheck - /api/driver/check - Is my login still valid? Called at app start-up. Also refreshes the push id and returns the driver's profile.
        /**
         * Is my login still valid? Called at app start-up. Also refreshes the push id and returns the driver's profile
         * and subscription status.
         *
         * POST /api/driver/check   (header auth_code)   optionally AuthSession[device_token]
         * NOK "session not found" = logged in elsewhere or the account was blocked: the app logs the driver out.
         */
        public function actionCheck()
    {
        $data = [];

        $headers = getallheaders();
        $auth_code = isset($headers['auth_code'])
            ? $headers['auth_code']
            : null;
        if ($auth_code == null) {
            $auth_code = \Yii::$app->request->get('auth_code');
        }
        if ($auth_code) {
            // Sessions of blocked / inactive accounts are invisible to this query, so they end up as "session not found".
            $auth_session = AuthSession::find()->where(['auth_code' => $auth_code])->one();
            if ($auth_session) {
                $user_id = $auth_session->create_user_id;
                $user = User::find()->where(['id'=>$user_id])->one();
                // the status flag of ended subscriptions is tidied once a day, by the first call after midnight
                DriverSubscription::expireEndedDaily();
                // Subscription state shown in the app (active / expired, start and end date).
                $driver_subscription = DriverSubscription::find()->where(['driver_id'=>$user_id])->one();
                $user_data = $user->asJson();
                if($driver_subscription){
                    // 1 only while the plan is really running (marked active and not past its end date)
                    $user_data['driver_subscription_status'] = $driver_subscription->isCurrent() ? 1 : 0;
                    $user_data['subscription_start'] = $driver_subscription->start_date;
                    $user_data['subscription_end'] = $driver_subscription->end_date;
                }
              	else{
                	$user_data['driver_subscription_status'] = 0;
                  	$user_data['subscription_start'] = '';
                    $user_data['subscription_end'] = '';
              	}

                $data['status'] = self::API_OK;
                $data['detail'] = $user_data;
                // The app sends its OneSignal subscription id here; push notifications are delivered to it.
                if (isset($_POST['AuthSession'])) {
                    $auth_session->device_token = $_POST['AuthSession']['device_token'];
                    if ($auth_session->save()) {
                        $data['auth_session'] = Yii::t('app','Auth Session updated');
                    } else {
                        $data['error'] = $auth_session->flattenErrors;
                    }
                }
            } else {
                $data['error'] = Yii::t('app', 'session not found');
            }
        } else {
            $data['error'] = Yii::t('app', 'Auth code not found');
            $data['auth'] = isset($auth_code) ? $auth_code : '';
        }

        // REPLY: every path above ends here with {status: OK|NOK, details|error}.
        return $this->sendJsonResponse($data);
    }
        // [doc:end] actionCheck

    // [doc:start] actionLegalPage - /api/driver/legal-page - GET /api/driver/legal-page?type=terms|privacy|refund (no login: shown before login).
    /**
     * GET /api/driver/legal-page?type=terms|privacy|refund   (no login: shown before login)
     * Written in the admin dashboard (Terms & Policies, app = Driver partner app).
     */
    public function actionLegalPage()
    {
        return $this->sendJsonResponse(
            \app\modules\admin\models\LegalPageApi::response(
                \app\modules\admin\models\Page::APP_DRIVER,
                Yii::$app->request->get('type')
            )
        );
    }
    // [doc:end] actionLegalPage

    // [doc:start] actionSendOtp - /api/driver/send-otp - Send Otp
    public function actionSendOtp()
    {
        return $this->sendJsonResponse(Yii::$app->otp->send(Yii::$app->request->post()));
    }
    // [doc:end] actionSendOtp

    // [doc:start] actionResendOtp - /api/driver/resend-otp - Resend Otp
    public function actionResendOtp()
    {
        return $this->sendJsonResponse(Yii::$app->otp->resend(Yii::$app->request->post()));
    }
    // [doc:end] actionResendOtp

    // [doc:start] actionVerifyOtp - /api/driver/verify-otp - Login / sign-up of a partner (driver): the OTP typed by the driver is checked and, if right, the app gets its.
    /**
     * Login / sign-up of a partner (driver): the OTP typed by the driver is checked and, if right, the app gets its
     * auth_code.
     *
     * POST /api/driver/verify-otp   contact_no, session_code (from send-otp), otp_code, device_token, device_type
     * OK: details = the driver's profile (secrets are never included), auth_code = the login code to keep.
     * A phone number logging in for the first time creates the partner account (role Driver, default commission).
     */
    public function actionVerifyOtp()
    {
        $data = [];
        $post = Yii::$app->request->post();

        if (!empty($post)) {
            try {
                // 1. BLOCKED ACCOUNTS: refused before the OTP is even checked.
                $contact_no = $post['contact_no'];

                if (User::find()->where(['contact_no' => $contact_no, 'user_role' => User::ROLE_DRIVER, 'status' => User::STATUS_BLOCKED])->exists()) {
                    $data['status'] = self::API_NOK;
                    $data['error'] = 'Your account has been blocked. Please contact support.';
                    return $this->sendJsonResponse($data);
                }
                $session_code = $post['session_code'];
                $otp_code = $post['otp_code'];

                // 2. CHECK THE OTP with the SMS provider (2factor.in) using the session code from send-otp.
                // OTP check (provider, or the dev-only test code): see OtpService::verify().
                $send_otp = Yii::$app->otp->verify($contact_no, $session_code, $otp_code);
// 3. OTP WAS RIGHT: find the account for this phone number, or create it.
if (($send_otp['Status'] ?? '') == 'Success') {
                    $providerId = User::ROLE_DRIVER;

                    $number = $post['contact_no'];
                    $auth_id = $post['contact_no'];

                    $auth = Auth::find()
                        ->where([
                            'source' => $providerId,
                            'source_id' => $auth_id,
                        ])
                        ->one();

                    // EXISTING ACCOUNT: log in and issue a new auth_code (any older login of this person is replaced).
                    if (!empty($auth)) {
                        $user = $auth->user;
                        $user->device_token = ($post['device_token'] ?? '');
                        $user->device_type = ($post['device_type'] ?? 0);
                        Yii::$app->user->login($user);

                        $data['status'] = self::API_OK;
                        $data['details'] = $user;
                        $data['auth_code'] = AuthSession::newSession(
                            $user
                        )->auth_code;
                    } else {
                        $settings = new WebSetting();
                        $default_admin_commission_raider = $settings->getSettingBykey('default_admin_commission_raider');

                        $check = User::find()->where(['contact_no' => $number])->andWhere(['user_role'=>User::ROLE_DRIVER])->one();
                        if (empty($check)) {
                            // NEW ACCOUNT: first login of this phone number creates the driver account.
                            $model = new User();
                            $model->username = $number. '@driver';
                            $model->contact_no = $number;
                            $model->device_token = ($post['device_token'] ?? '');
                            $model->device_type = ($post['device_type'] ?? 0);
                            $model->admin_per = $default_admin_commission_raider;

                            $model->referal_code = rand(11111111, 99999999);
                            $model->user_role = User::ROLE_DRIVER;

                            if ($model->validate()) {
                                if ($model->save()) {
                                    $auth = new Auth();
                                    $auth->user_id = $model->id;
                                    $auth->source = $providerId;
                                    $auth->source_id = $auth_id;
                                    if ($auth->save(false)) {
                                        $user = $auth->user;
                                        $user->device_token =
                                            ($post['device_token'] ?? '');
                                        $user->device_type =
                                            ($post['device_type'] ?? 0);
                                        Yii::$app->user->login($user);

                                        $data['status'] = self::API_OK;
                                        $data['details'] = $user;
                                        $data[
                                            'auth_code'
                                        ] = AuthSession::newSession(
                                            $user
                                        )->auth_code;

                                        // Tell the dashboard that a new person registered.
                                        $notification = new Notification();
                                        $notification->title =
                                            'New User Registered';
                                        $notification->icon = 'fas fa-user';
                                        $notification->user_id = $model->id;
                                        $notification->check_on_ajax = 0;
                                        $notification->module = '';
                                        $notification->model_type = get_class(
                                            new User()
                                        );
                                        $notification->save(false);
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
                            $data['error'] = Yii::t(
                                'app',
                                'This number is already registered with us.Please Contact Support'
                            );
                        }
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = Yii::t('app', 'OTP failed');
                }
            // Anything unexpected is answered as NOK (the text is cleaned of SQL/stack details by sendJsonResponse).
            } catch (Exception $e) {
                $data['status'] = self::API_NOK;
                $data['error'] = $e->getMessage();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = Yii::t('app', 'No  Data Posted');
        }
        // REPLY: every path above ends here with {status: OK|NOK, details|error}.
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionVerifyOtp

    // [doc:start] actionDeleteAccount - /api/driver/delete-account - Deletes the signed-in account (Google Play account deletion): signs out everywhere and erases the personal details; ride and.
    /**
     * Deletes the signed-in account (Google Play account deletion): signs out everywhere and erases the personal details; ride and
     * payment records stay without the personal link. Refused while a ride is in progress.
     */
    public function actionDeleteAccount()
    {
        $data = [];
        $headers = isset(\Yii::$app->request->headers['auth_code'])
            ? \Yii::$app->request->headers['auth_code']
            : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (empty($user_id)) {
            $data['status'] = self::API_NOK;
            $data['error'] = Yii::t('app', 'You are not logged in');

            return $this->sendJsonResponse($data);
        }
        [$ok, $message] = \app\components\AccountDeletion::deleteUser($user_id, User::ROLE_DRIVER, 'app', (string) Yii::$app->request->post('reason', ''));
        $data['status'] = $ok ? self::API_OK : self::API_NOK;
        $data[$ok ? 'message' : 'error'] = Yii::t('app', $message);

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionDeleteAccount


       // [doc:start] actionUpdateProfile - /api/driver/update-profile - Update Profile
       public function actionUpdateProfile()
    {
        $data = [];
        $post = Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code'])
            ? \Yii::$app->request->headers['auth_code']
            : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            if (!empty($post)) {
                try {
                    $model = User::find()
                        ->where(['id' => $user_id])
                        ->andWhere(['user_role' => User::ROLE_DRIVER])
                        ->one();
                    $first_name = !empty($post['first_name'])
                        ? $post['first_name']
                        : '';
                    $last_name = !empty($post['last_name'])
                        ? $post['last_name']
                        : '';
                    $email = !empty($post['email']) ? $post['email'] : '';
                    $date_of_birth = !empty($post['date_of_birth'])
                        ? $post['date_of_birth']
                        : '';
                    $gender = !empty($post['gender']) ? $post['gender'] : '';
                    // The Partner app sends the home address with the profile; it used to be ignored.
                    $address = isset($post['address']) ? trim((string) $post['address']) : null;

                    if (trim($first_name) === '' || trim($last_name) === '') {
                        // both names are mandatory: no blanks
                        $data['status'] = self::API_NOK;
                        $data['error'] = Yii::t('app', trim($first_name) === '' ? 'First name is required' : 'Last name is required');

                        return $this->sendJsonResponse($data);
                    }
                    if (!empty($model)) {
                        $model->first_name = trim($first_name);
                        $model->last_name = trim($last_name);
                        $model->email = $email;
                        $model->date_of_birth = $date_of_birth;
                        $model->gender = $gender;
                        if ($address !== null && $address !== '') {
                            $model->address = $address;
                        }

                        if ($model->save(false)) {
                            $data['status'] = self::API_OK;
                            $data['details'] = $model->asJson();
                        } else {
                            $data['status'] = self::API_NOK;
                            $data['error'] = Yii::t(
                                'app',
                                'User Data Update Failed'
                            );
                        }
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = Yii::t('app', 'User Data Not found');
                    }
                } catch (Exception $e) {
                    $data['status'] = self::API_NOK;
                    $data['error'] = $e->getMessage();
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t('app', 'Data Not Posted');
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = Yii::t('app', 'User Not Found');
        }

        return $this->sendJsonResponse($data);
    }
       // [doc:end] actionUpdateProfile

    // [doc:start] actionUpdateLatLng - /api/driver/update-lat-lng - The app reports the driver's live position (called every few seconds while online).
    /**
     * The app reports the driver's live position (called every few seconds while online).
     *
     * POST /api/driver/update-lat-lng   latitude, longitude
     * Used by the dispatcher to find the nearest drivers and by the dashboard live map.
     */
    public function actionUpdateLatLng()
    {
        $data = [];

        $post = \Yii::$app->request->post();
        if ($post) {
            $headers = isset(\Yii::$app->request->headers['auth_code'])
                ? \Yii::$app->request->headers['auth_code']
                : Yii::$app->request->getQueryParam('auth_code');
            // WHO IS CALLING: the app sends its login code (auth_code). It is turned into a user id here; a blocked,
            // inactive or logged-out account gets no id and is answered "User Not Found".
            $auth = new AuthSettings();
            $user_id = $auth->getAuthSession($headers);

            if (!empty($user_id)) {
                $user = User::find()
                    ->where(['id' => $user_id])
                    ->one();
                if (!empty($user)) {
                    // A missing or impossible position is refused instead of being stored.
                    $lat = $post['latitude'] ?? null;
                    $lng = $post['longitude'] ?? null;
                    if (!is_numeric($lat) || !is_numeric($lng) || abs($lat) > 90 || abs($lng) > 180) {
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'Valid latitude and longitude are required';
                        return $this->sendJsonResponse($data);
                    }
                    $previousLat = $user->lat;
                    $previousLng = $user->lng;
                    $user->lat = $lat;
                    $user->lng = $lng;

                    // Lookup city ID based on the provided city name

                    if ($user->save(false)) {
                        // A ride in progress: add the position to the ride's real-time distance (checked, deduplicated, drift removed).
                        $trackResult = Booking::recordTrackForDriver(
                            $user->id, $lat, $lng,
                            $post['accuracy'] ?? null, $post['speed_kmh'] ?? null, $post['recorded_at'] ?? null
                        );
                        // A ride in progress that has left the in-city radius: tell the customer and the driver.
                        $this->alertIfRadiusCrossed($user, $previousLat, $previousLng, $lat, $lng);
                        $data['status'] = self::API_OK;
                        $data['detail'] = $user;
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'Lat & long not updated';
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
        // REPLY: every path above ends here with {status: OK|NOK, details|error}.
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionUpdateLatLng

    // [doc:start] actionAddDriverEmergencyContacts - /api/driver/add-driver-emergency-contacts - Add Driver Emergency Contacts
    public function actionAddDriverEmergencyContacts()
    {
        $data = [];
        $post = \Yii::$app->request->post();

        if ($post) {
            $headers = isset(\Yii::$app->request->headers['auth_code'])
                ? \Yii::$app->request->headers['auth_code']
                : Yii::$app->request->getQueryParam('auth_code');
            $auth = new AuthSettings();
            $user_id = $auth->getAuthSession($headers);
            if (!empty($user_id)) {
                $user = User::find()
                    ->where(['id' => $user_id])
                    ->one();
              $data['user'] = $user;
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

                    $user_emergency_contacts_count = DriverEmergencyContacts::find()
                        ->where(['driver_id' => $user['id']])
                        ->count();

                    if ($user_emergency_contacts_count >= 2) {
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'Emergency contact Exist';
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

                        $user_emergency_contacts_two = new DriverEmergencyContacts();
                        $user_emergency_contacts_two->name = $person_name_two;
                        $user_emergency_contacts_two->driver_id = $user_id;
                        $user_emergency_contacts_one->aadhar_number = $aadhar_number_two;
                        $user_emergency_contacts_two->phone_number = $person_phone_number_two;
                        $user_emergency_contacts_two->relationship = $person_relationship_two;

                        $user_emergency_contacts_two->status =
                            DriverEmergencyContacts::STATUS_ACTIVE;
                        $user_emergency_contacts_two->save(false);

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

//                 ? \Yii::$app->request->headers['auth_code']
//                 : Yii::$app->request->getQueryParam('auth_code');

//                         // Retrieve driver referral earnings setting

//                             // Create a new instance of DriverReferralEarnings

// [doc:start] actionApplyReferral - /api/driver/apply-referral - Apply Referral
public function actionApplyReferral()
    {
        $data = [];
        $post = \Yii::$app->request->post();

        if ($post) {
            $headers = isset(\Yii::$app->request->headers['auth_code'])
                ? \Yii::$app->request->headers['auth_code']
                : Yii::$app->request->getQueryParam('auth_code');

            $auth = new AuthSettings();
            $user_id = $auth->getAuthSession($headers);

            if (!empty($user_id)) {
                $model = new User();
                $user = User::find()
                    ->where(['id' => $user_id])
                    ->one();

                    $contact = $user->contact_no;
                    $user->contact_no= (string)$contact;

                if (!empty($user)) {
                    $referal_code = !empty($post['referal_code']) ? $post['referal_code'] : '';

                    $referral_user_data = User::find()
                        ->where(['referal_code' => $referal_code])
                        ->one();

                    if (!empty($referral_user_data)) {
                        $referal_id = $referral_user_data->id;
                        $user->referal_id = $referal_id;
                        $user->save();

                        $settings = new WebSetting();
                        // Retrieve driver referral earnings setting
                        $driverReferralEarningSetting = \app\modules\geo\services\ServiceModules::setting('driver_referral_earning', \app\modules\geo\services\ServiceModules::DRIVER);

                        if (!empty($driverReferralEarningSetting)) {
                            // Create a new instance of DriverReferralEarnings
                            $driverReferralEarning = new DriverReferralEarnings();
                            $driverReferralEarning->driver_id = $referal_id;
                            $driverReferralEarning->earnings = $driverReferralEarningSetting;
                            $driverReferralEarning->status = DriverReferralEarnings::STATUS_INACTIVE;
                            $driverReferralEarning->create_user_id = $user_id;
                            $driverReferralEarning->update_user_id = $user_id;
                            $driverReferralEarning->save(false);

                                if ($driverReferralEarning->save(false)) {
                                    $data['status'] = self::API_OK;
                                    $data['details'] = 'Referral code apply success';
                                } else {
                                    $data['status'] = self::API_NOK;
                                    $data['error'] = 'Failed to store referral earnings';
                                }

                        } else {
                            $data['status'] = self::API_NOK;
                            $data['error'] = 'Driver referral earnings setting not found or invalid';
                        }
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'Referral code does not exist';
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
// [doc:end] actionApplyReferral

    // [doc:start] actionVehicleTypes - /api/driver/vehicle-types - Vehicle Types
    public function actionVehicleTypes()
    {
        $data = [];
        $post = \Yii::$app->request->post();
        $type = !empty($post['type']) ? $post['type'] : '';

        $headers = isset(\Yii::$app->request->headers['auth_code'])
            ? \Yii::$app->request->headers['auth_code']
            : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            $user = User::find()
                ->where(['id' => $user_id])
                ->one();
            if (!empty($user)) {
                if (!empty($type)) {
                    $vehicle_types = VehicleTypes::find()
                        ->where(['type' => $type])
                        ->andWhere(['status' => VehicleTypes::STATUS_ACTIVE])
                        ->all();
                } else {
                    $vehicle_types = VehicleTypes::find()
                        ->where(['status' => VehicleTypes::STATUS_ACTIVE])
                        ->all();
                }
                if (!empty($vehicle_types)) {
                    foreach ($vehicle_types as $vehicle_types_data) {
                        $list[] = $vehicle_types_data->asJson();
                    }
                    if (!empty($list)) {
                        $data['status'] = self::API_OK;
                        $data['details'] = $list;
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'Vehicle type data not found';
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = 'Vehicle type data not found';
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = 'Wrong User';
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = 'User Not Found';
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionVehicleTypes

    // [doc:start] actionDrivingLicense - /api/driver/driving-license - Driving License
    public function actionDrivingLicense()
    {
        $data = [];
        $post = \Yii::$app->request->post();

        if ($post) {
            $driving_license_front = !empty($post['driving_license_front'])
                ? $post['driving_license_front']
                : '';
            $driving_license_back = !empty($post['driving_license_back'])
                ? $post['driving_license_back']
                : '';
            $driving_license_number = !empty($post['driving_license_number'])
                ? $post['driving_license_number']
                : '';
            $issued_date = !empty($post['issued_date'])
                ? $post['issued_date']
                : '';
            $expire_date = !empty($post['expire_date'])
                ? $post['expire_date']
                : '';

            // Check if driving license number already exists for any user
            $existingDrivingLicense = DrivingLicense::find()
                ->where(['driving_license_number' => $driving_license_number])
                ->one();

            if (!empty($existingDrivingLicense)) {
                $data['status'] = self::API_NOK;
                $data['error'] = 'Driving license number already exists.';
            } else {
                // Proceed with user registration
                $headers = isset(\Yii::$app->request->headers['auth_code'])
                    ? \Yii::$app->request->headers['auth_code']
                    : Yii::$app->request->getQueryParam('auth_code');
                $auth = new AuthSettings();
                $user_id = $auth->getAuthSession($headers);

                if (!empty($user_id)) {
                    $user = User::find()->where(['id' => $user_id])->one();

                    if (!empty($user)) {
                        $driving_license_exist = DrivingLicense::find()
                            ->where(['driver_id' => $user_id])
                            ->one();

                        if (!empty($driving_license_exist)) {
                            $driving_license = DrivingLicense::find()
                                ->where(['id' => $driving_license_exist->id])
                                ->one();
                        } else {
                            $driving_license = new DrivingLicense();
                        }

                        $driving_license->driver_id = $user_id;
                        $driving_license->driving_license_front = $driving_license_front;
                        $driving_license->driving_license_back = $driving_license_back;
                        $driving_license->driving_license_number = $driving_license_number;
                        $driving_license->issued_date = $issued_date;
                        $driving_license->expire_date = $expire_date;
                        $driving_license->status = DrivingLicense::STATUS_ACTIVE;

                        if ($driving_license->save(false)) {
                            $data['status'] = self::API_OK;
                            $data['details'] = $driving_license->asJson();
                        } else {
                            $data['status'] = self::API_NOK;
                            $data['error'] = 'Driving license added failed.';
                        }
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'Wrong User.';
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = 'User Not Found.';
                }
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = 'No data posted.';
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionDrivingLicense

    // [doc:start] actionAddAadhar - /api/driver/add-aadhar - Add Aadhar
    public function actionAddAadhar()
    {
        $data = [];
        $post = \Yii::$app->request->post();

        if ($post) {
            $aadhar_card_front = !empty($post['aadhar_card_front']) ? $post['aadhar_card_front'] : '';
            $aadhar_card_back = !empty($post['aadhar_card_back']) ? $post['aadhar_card_back'] : '';
            $aadhar_number = !empty($post['aadhar_number']) ? $post['aadhar_number'] : '';

            // Check if Aadhar card number already exists for any user
            $existingAadharCard = DriverAadharCard::find()
                ->where(['aadhar_number' => $aadhar_number])
                ->one();

            if (!empty($existingAadharCard)) {
                $data['status'] = self::API_NOK;
                $data['error'] = 'Aadhar card number already exists.';
            } else {
                // Proceed with user registration
                $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
                $auth = new AuthSettings();
                $user_id = $auth->getAuthSession($headers);

                if (!empty($user_id)) {
                    $user = User::find()->where(['id' => $user_id])->one();

                    if (!empty($user)) {
                        $driver_aadhar_card_exist = DriverAadharCard::find()
                            ->where(['driver_id' => $user_id])
                            ->one();

                        if (!empty($driver_aadhar_card_exist)) {
                            $driver_aadhar_card = DriverAadharCard::find()
                                ->where(['id' => $driver_aadhar_card_exist->id])
                                ->one();
                        } else {
                            $driver_aadhar_card = new DriverAadharCard();
                        }

                        $driver_aadhar_card->driver_id = $user_id;
                        $driver_aadhar_card->aadhar_card_front = $aadhar_card_front;
                        $driver_aadhar_card->aadhar_card_back = $aadhar_card_back;
                        $driver_aadhar_card->aadhar_number = $aadhar_number;
                        $driver_aadhar_card->status = DriverAadharCard::STATUS_ACTIVE;

                        if ($driver_aadhar_card->save(false)) {
                            $data['status'] = self::API_OK;
                            $data['details'] = $driver_aadhar_card->asJson();
                        } else {
                            $data['status'] = self::API_NOK;
                            $data['error'] = 'Aadhar card added failed.';
                        }
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'Wrong User.';
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = 'User Not Found.';
                }
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = 'No data posted.';
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionAddAadhar

    // [doc:start] actionAddPanCard - /api/driver/add-pan-card - Add Pan Card
    public function actionAddPanCard()
    {
        $data = [];
        $post = \Yii::$app->request->post();

        if ($post) {
            $pan_number = !empty($post['pan_number']) ? $post['pan_number'] : '';
            $pan_card = !empty($post['pan_front']) ? $post['pan_front'] : '';

            // Check if pan card already exists for any user
            $existingPanCard = DriverPanCards::find()
                ->where(['pan_number' => $pan_number])
                ->one();

            if (!empty($existingPanCard)) {
                $data['status'] = self::API_NOK;
                $data['error'] = 'Pan number already exist.';
            } else {
                // Proceed with user registration
                $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
                $auth = new AuthSettings();
                $user_id = $auth->getAuthSession($headers);

                if (!empty($user_id)) {
                    $user = User::find()->where(['id' => $user_id])->one();

                    if (!empty($user)) {
                        $driver_pan_cards_exist = DriverPanCards::find()
                            ->where(['driver_id' => $user_id])
                            ->one();

                        if (!empty($driver_pan_cards_exist)) {
                            $driver_pan_cards = DriverPanCards::find()
                                ->where(['id' => $driver_pan_cards_exist->id])
                                ->one();
                        } else {
                            $driver_pan_cards = new DriverPanCards();
                        }

                        $driver_pan_cards->driver_id = $user_id;
                        $driver_pan_cards->pan_number = $pan_number;
                        $driver_pan_cards->pan_card = $pan_card;
                        $driver_pan_cards->status = DriverPanCards::STATUS_ACTIVE;

                        if ($driver_pan_cards->save(false)) {
                            $data['status'] = self::API_OK;
                            $data['details'] = $driver_pan_cards->asJson();
                        } else {
                            $data['status'] = self::API_NOK;
                            $data['error'] = 'Pan card update failed.';
                        }
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'Wrong User.';
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = 'User Not Found.';
                }
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = 'No data posted.';
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionAddPanCard

    // [doc:start] actionPoliceVerification - /api/driver/police-verification - Police Verification
    public function actionPoliceVerification()
    {
        $data = [];
        $post = \Yii::$app->request->post();

        $headers = isset(\Yii::$app->request->headers['auth_code'])
            ? \Yii::$app->request->headers['auth_code']
            : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            $user = User::find()
                ->where(['id' => $user_id])
                ->one();
            if (!empty($user)) {
                $user->is_done_police_verification =
                    User::police_verification_yes;
                if ($user->save(false)) {
                    $data['status'] = self::API_OK;
                    $data['details'] = $user->asJson();
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = 'police verification failed';
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = 'Wrong User';
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = 'User Not Found';
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionPoliceVerification

    // [doc:start] actionRazorpayCreateOrder - /api/driver/razorpay-create-order - Starts a Razorpay payment.
    /**
     * Starts a Razorpay payment.
     *
     * POST /api/driver/razorpay-create-order   purpose (joining_fee | wallet_topup | subscription), amount (wallet_topup only)
     * OK: details = key_id, order_id, amount (in paise), currency, name, description, prefill - pass them to Razorpay checkout.
     * The price of the joining fee and of a plan is fixed on the server; only a wallet top-up amount comes from the app.
     */
    public function actionRazorpayCreateOrder()
    {
        $data = [];
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $user_id = (new AuthSettings())->getAuthSession($headers);
        if (empty($user_id)) {
            $data['status'] = self::API_NOK;
            $data['error'] = 'User Not Found';

            return $this->sendJsonResponse($data);
        }
        $post = Yii::$app->request->post();
        $r = \app\modules\payments\services\PaymentOrders::create($user_id, $post['purpose'] ?? '', $post['amount'] ?? 0);
        if ($r['ok']) {
            $data['status'] = self::API_OK;
            $data['details'] = $r['checkout'];
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = $r['error'];
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionRazorpayCreateOrder

    // [doc:start] actionCashfreeCreateOrder - /api/driver/cashfree-create-order - Starts a Cashfree payment.
    /**
     * Starts a Cashfree payment.
     *
     * POST /api/driver/cashfree-create-order   purpose (joining_fee | wallet_topup | subscription), amount (wallet_topup only)
     * OK: details = order_id, payment_session_id, mode (sandbox | production), amount, currency - pass them to the Cashfree checkout.
     * Afterwards send the same order_id to /api/driver/pay-joining-fee with gateway=cashfree.
     */
    public function actionCashfreeCreateOrder()
    {
        $data = [];
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $user_id = (new AuthSettings())->getAuthSession($headers);
        if (empty($user_id)) {
            $data['status'] = self::API_NOK;
            $data['error'] = 'User Not Found';

            return $this->sendJsonResponse($data);
        }
        $post = Yii::$app->request->post();
        $r = \app\modules\payments\services\PaymentOrders::createCashfree($user_id, $post['purpose'] ?? '', $post['amount'] ?? 0);
        if ($r['ok']) {
            $data['status'] = self::API_OK;
            $data['details'] = $r['checkout'];
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = $r['error'];
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionCashfreeCreateOrder

    // [doc:start] actionRazorpayVerify - /api/driver/razorpay-verify - POST /api/driver/razorpay-verify razorpay_order_id, razorpay_payment_id, razorpay_signature.
    /**
     * POST /api/driver/razorpay-verify   razorpay_order_id, razorpay_payment_id, razorpay_signature
     * OK: details = {purpose, already_applied}. The same check as pay-joining-fee, add-balance and subscribe-now, for any purpose.
     */
    public function actionRazorpayVerify()
    {
        $data = [];
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $user_id = (new AuthSettings())->getAuthSession($headers);
        if (empty($user_id)) {
            $data['status'] = self::API_NOK;
            $data['error'] = 'User Not Found';

            return $this->sendJsonResponse($data);
        }
        $r = $this->confirmRazorpay($user_id, null);
        if ($r['ok']) {
            $data['status'] = self::API_OK;
            $data['details'] = ['purpose' => $r['purpose'], 'already_applied' => !empty($r['already'])];
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = $r['error'];
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionRazorpayVerify

    // [doc:start] actionRazorpayWebhook - /api/driver/razorpay-webhook - Called by Razorpay, not by the app. Set this address as the webhook in the Razorpay dashboard with the events payment.captured,.
    /**
     * Called by Razorpay, not by the app. Set this address as the webhook in the Razorpay dashboard with the events payment.captured,
     * order.paid and payment.failed, and paste the webhook secret in the dashboard Payments screen. A call whose X-Razorpay-Signature
     * does not match the raw body is refused.
     */
    public function actionRazorpayWebhook()
    {
        $raw = Yii::$app->request->getRawBody();
        $sig = Yii::$app->request->headers->get('X-Razorpay-Signature', '');
        if (!\app\modules\payments\services\Razorpay::verifyWebhookSignature($raw, $sig)) {
            Yii::$app->response->statusCode = 400;

            return $this->sendJsonResponse(['status' => self::API_NOK, 'error' => 'Invalid signature']);
        }
        $event = json_decode($raw, true);
        $done = is_array($event) ? \app\modules\payments\services\PaymentOrders::fromWebhook($event) : 'ignored';

        return $this->sendJsonResponse(['status' => self::API_OK, 'details' => $done]);
    }
    // [doc:end] actionRazorpayWebhook

    // [doc:start] actionOnboardingStatus - /api/driver/onboarding-status - POST|GET /api/driver/onboarding-status.
    /**
     * POST|GET /api/driver/onboarding-status
     * OK: details = {steps: [{key, label, done}], joining_fee: {amount, required, paid}, complete, enforced, can_go_online}.
     * The app shows the checklist and opens the next undone step; the joining fee step opens Razorpay checkout.
     */
    public function actionOnboardingStatus()
    {
        $data = [];
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $user_id = (new AuthSettings())->getAuthSession($headers);
        $user = $user_id ? User::find()->where(['id' => $user_id, 'user_role' => User::ROLE_DRIVER])->one() : null;
        if (!$user) {
            $data['status'] = self::API_NOK;
            $data['error'] = 'User Not Found';

            return $this->sendJsonResponse($data);
        }
        $fee = \app\modules\payments\services\PaymentOrders::joiningFee();
        $data['status'] = self::API_OK;
        $data['details'] = [
            'steps' => \app\modules\payments\services\DriverOnboarding::steps($user),
            'joining_fee' => ['amount' => $fee, 'required' => $fee > 0, 'paid' => (int) $user->is_paid_joining_fee === 1],
            'complete' => \app\modules\payments\services\DriverOnboarding::complete($user),
            'enforced' => \app\modules\payments\services\DriverOnboarding::enforced(),
            'can_go_online' => \app\modules\payments\services\DriverOnboarding::mayWork($user),
        ];

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionOnboardingStatus

    // [doc:start] actionPaymentOptions - /api/driver/payment-options - POST|GET /api/driver/payment-options (optional: purpose, amount - then the reply carries the amount the server will charge).
    /**
     * POST|GET /api/driver/payment-options   (optional: purpose, amount - then the reply carries the amount the server will charge)
     * OK: details = {amount, checkout, qr}. The app uses the Razorpay or Cashfree checkout.
     */
    public function actionPaymentOptions()
    {
        $data = [];
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $user_id = (new AuthSettings())->getAuthSession($headers);
        if (empty($user_id)) {
            $data['status'] = self::API_NOK;
            $data['error'] = 'User Not Found';

            return $this->sendJsonResponse($data);
        }
        $data['status'] = self::API_OK;
        $post = Yii::$app->request->post();
        $data['details'] = \app\modules\payments\services\PaymentOrders::options($post['purpose'] ?? null, $post['amount'] ?? 0);

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionPaymentOptions

    // [doc:start] actionRazorpayCreateQr - /api/driver/razorpay-create-qr - POST /api/driver/razorpay-create-qr purpose (joining_fee | wallet_topup | subscription), amount (wallet_topup only).
    /**
     * POST /api/driver/razorpay-create-qr   purpose (joining_fee | wallet_topup | subscription), amount (wallet_topup only)
     * OK: details = {id, image_url, amount, expires_at, purpose}. Show image_url, then call razorpay-qr-status every 4 seconds: when the driver pays
     * with any UPI app the payment is confirmed and applied (fee marked paid, wallet credited, plan started) with nothing else to do.
     * NOK when Razorpay refuses (for example the UPI QR feature is not switched on for the account).
     */
    public function actionRazorpayCreateQr()
    {
        $data = [];
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $user_id = (new AuthSettings())->getAuthSession($headers);
        if (empty($user_id)) {
            $data['status'] = self::API_NOK;
            $data['error'] = 'User Not Found';

            return $this->sendJsonResponse($data);
        }
        $post = Yii::$app->request->post();
        $r = \app\modules\payments\services\PaymentOrders::createQr($user_id, $post['purpose'] ?? '', $post['amount'] ?? 0);
        if ($r['ok']) {
            $data['status'] = self::API_OK;
            $data['details'] = $r['qr'];
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = $r['error'];
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionRazorpayCreateQr

    // [doc:start] actionRazorpayQrStatus - /api/driver/razorpay-qr-status - POST /api/driver/razorpay-qr-status qr_id.
    /**
     * POST /api/driver/razorpay-qr-status   qr_id
     * OK: details = {status: waiting | paid | expired, purpose}. Poll every few seconds while the QR is on screen.
     */
    public function actionRazorpayQrStatus()
    {
        $data = [];
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $user_id = (new AuthSettings())->getAuthSession($headers);
        if (empty($user_id)) {
            $data['status'] = self::API_NOK;
            $data['error'] = 'User Not Found';

            return $this->sendJsonResponse($data);
        }
        $r = \app\modules\payments\services\PaymentOrders::qrStatus($user_id, Yii::$app->request->post('qr_id', ''));
        if ($r['ok']) {
            $data['status'] = self::API_OK;
            $data['details'] = ['status' => $r['status'], 'purpose' => $r['purpose']];
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = $r['error'];
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionRazorpayQrStatus

    // [doc:start] actionPayJoiningFee - /api/driver/pay-joining-fee - The driver reports the joining fee paid through Razorpay.
    /**
     * The driver reports the joining fee paid through Razorpay.
     *
     * POST /api/driver/pay-joining-fee   razorpay_order_id, razorpay_payment_id, razorpay_signature
     * (the order comes from /api/driver/razorpay-create-order with purpose=joining_fee). The signature is verified and the payment
     * fetched from Razorpay; the joining fee is marked paid once, only for a captured payment of the exact fee.
     */
    public function actionPayJoiningFee()
    {
        $data = [];
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $user_id = (new AuthSettings())->getAuthSession($headers);
        if (empty($user_id)) {
            $data['status'] = self::API_NOK;
            $data['error'] = 'User Not Found';

            return $this->sendJsonResponse($data);
        }
        $r = $this->confirmRazorpay($user_id, \app\modules\payments\services\PaymentOrders::JOINING_FEE);
        if ($r['ok']) {
            $data['status'] = self::API_OK;
            $data['details'] = User::findOne($user_id)->asJson();
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = $r['error'];
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionPayJoiningFee

    /**
     * Shared by the payment endpoints: checks the Razorpay result the app sends (razorpay_order_id, razorpay_payment_id,
     * razorpay_signature) for a payment of the given purpose (null = any purpose).
     *
     * @return array ['ok' => bool, 'error' => string]
     */
    private function confirmRazorpay($userId, $purpose)
    {
        $post = Yii::$app->request->post();
        if (($post['gateway'] ?? '') === 'cashfree') {
            // Cashfree: the order is read back from Cashfree (no signature from the app is involved).
            $cfOrder = (string) ($post['orderId'] ?? ($post['order_id'] ?? ''));
            if ($cfOrder === '') {
                return ['ok' => false, 'error' => 'order_id is required.'];
            }

            return \app\modules\payments\services\PaymentOrders::confirmCashfree($userId, $cfOrder, $purpose);
        }
        $orderId = $post['razorpay_order_id'] ?? ($post['orderId'] ?? '');
        $paymentId = $post['razorpay_payment_id'] ?? '';
        $signature = $post['razorpay_signature'] ?? '';
        if (($post['applied'] ?? '') === '1' && $purpose !== null && \app\modules\payments\services\PaymentOrders::recentlyPaid($userId, $purpose)) {
            // paid by QR a moment ago: the server has already applied it
            return ['ok' => true, 'purpose' => $purpose, 'already' => true];
        }
        if ($orderId === '' || $paymentId === '' || $signature === '') {
            return ['ok' => false, 'error' => 'razorpay_order_id, razorpay_payment_id and razorpay_signature are required.'];
        }

        return \app\modules\payments\services\PaymentOrders::confirm($userId, $orderId, $paymentId, $signature, $purpose);
    }

    // [doc:start] actionDriverBankDetailsAdd - /api/driver/driver-bank-details-add - Driver Bank Details Add
    public function actionDriverBankDetailsAdd()
    {
        $data = [];
        try {
            $post = \Yii::$app->request->post();
            if ($post) {
                $headers = isset(\Yii::$app->request->headers['auth_code'])
                    ? \Yii::$app->request->headers['auth_code']
                    : Yii::$app->request->getQueryParam('auth_code');
                $auth = new AuthSettings();
                $user_id = $auth->getAuthSession($headers);
                if (!empty($user_id)) {
                    $user = User::find()
                        ->where(['id' => $user_id])
                        ->one();

                        $model = User::find()
                        ->where(['id' => $user_id])
                        ->andWhere(['user_role' => User::ROLE_DRIVER])
                        ->one();

                    if (!empty($user)) {
                        $account_holder_name = !empty(
                            $post['account_holder_name']
                        )
                            ? $post['account_holder_name']
                            : '';
                        $account_number = !empty($post['account_number'])
                            ? $post['account_number']
                            : '';
                        $ifsc = !empty($post['ifsc_code']) ? $post['ifsc_code'] : '';
                        $bank_name = !empty($post['bank_name'])
                            ? $post['bank_name']
                            : '';
                        $upi = !empty($post['upi_id']) ? $post['upi_id'] : '';

                    $joining_date = !empty($post['date_of_joining']) ? $post['date_of_joining'] : '';
                   $originalDateTime = new DateTime($joining_date);
                    $formattedDate = $originalDateTime->format('d-m-Y H:i:s');

                        $driver_bank_details_exist = DriverBankDetails::find()
                            ->where(['driver_id' => $user_id])
                            ->one();
                        if (!empty($driver_bank_details_exist)) {
                            $driver_bank_details = DriverBankDetails::find()
                                ->where([
                                    'id' => $driver_bank_details_exist->id,
                                ])
                                ->one();
                        } else {
                            $driver_bank_details = new DriverBankDetails();
                        }
                        $driver_bank_details->driver_id = $user_id;
                        $driver_bank_details->account_holder_name = $account_holder_name;
                        $driver_bank_details->account_number = $account_number;
                        $driver_bank_details->ifsc = $ifsc;
                        $driver_bank_details->bank_name = $bank_name;
                        $driver_bank_details->upi = $upi;
                        $model->joining_date=$formattedDate;
                        $driver_bank_details->status =
                            DriverBankDetails::STATUS_ACTIVE;
                        if ($driver_bank_details->save(false) && $model->save(false)) {
                            $data['status'] = self::API_OK;
                            $data['details'] = $driver_bank_details->asJson();
                        } else {
                            $data['status'] = self::API_NOK;
                            $data['error'] = 'Bank details added failed';
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
    // [doc:end] actionDriverBankDetailsAdd

    // [doc:start] actionDriverVehicleTypesAdd - /api/driver/driver-vehicle-types-add - Driver Vehicle Types Add
    public function actionDriverVehicleTypesAdd()
    {
        $data = [];
        try {
            $post = \Yii::$app->request->post();
            if ($post) {
                $vehicle_type_id = !empty($post['vehicle_type_id'])
                    ? $post['vehicle_type_id']
                    : '';
                $headers = isset(\Yii::$app->request->headers['auth_code'])
                    ? \Yii::$app->request->headers['auth_code']
                    : Yii::$app->request->getQueryParam('auth_code');
                $auth = new AuthSettings();
                $user_id = $auth->getAuthSession($headers);
                if (!empty($user_id)) {
                    $user = User::find()
                        ->where(['id' => $user_id])
                        ->one();
                    if (!empty($user)) {
                        if (!empty($vehicle_type_id)) {
                            $vehicle_type_id_arr = explode(
                                ',',
                                $vehicle_type_id
                            );

                            if (!empty($vehicle_type_id_arr[0])) {
                                foreach (
                                    $vehicle_type_id_arr
                                    as $vehicle_type_id_arr_data
                                ) {
                                    $driver_vehicle_types_exist = DriverVehicleTypes::find()
                                        ->where(['driver_id' => $user_id])
                                        ->andWhere([
                                            'vehicle_type_id' => $vehicle_type_id_arr_data,
                                        ])
                                        ->one();

                                    if (!empty($driver_vehicle_types_exist)) {
                                        $driver_vehicle_types = DriverVehicleTypes::find()
                                            ->where([
                                                'id' =>
                                                    $driver_vehicle_types_exist->id,
                                            ])
                                            ->one();
                                    } else {
                                        $driver_vehicle_types = new DriverVehicleTypes();
                                    }

                                    $driver_vehicle_types->driver_id = $user_id;
                                    $driver_vehicle_types->vehicle_type_id = $vehicle_type_id_arr_data;
                                    $driver_vehicle_types->status =
                                        DriverVehicleTypes::STATUS_ACTIVE;
                                    $driver_vehicle_types->save(false);
                                }

                                $data['status'] = self::API_OK;
                                $data['details'] =
                                    'vehicle types added successfully';
                            } else {
                                $data['status'] = self::API_NOK;
                                $data['error'] = 'vehicle_type_id required';
                            }
                        } else {
                            $data['status'] = self::API_NOK;
                            $data['error'] = 'vehicle_type_id required';
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
    // [doc:end] actionDriverVehicleTypesAdd

    // [doc:start] actionAddBalance - /api/driver/add-balance - The driver reports a wallet top-up paid through Razorpay.
    /**
     * The driver reports a wallet top-up paid through Razorpay.
     *
     * POST /api/driver/add-balance   razorpay_order_id, razorpay_payment_id, razorpay_signature
     * (the order comes from /api/driver/razorpay-create-order with purpose=wallet_topup and amount). The amount credited is the
     * amount Razorpay captured for that order, never a number sent by the app.
     */
    public function actionAddBalance()
    {
        $data = [];
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $user_id = (new AuthSettings())->getAuthSession($headers);
        if (empty($user_id)) {
            $data['status'] = self::API_NOK;
            $data['error'] = 'User Not Found';

            return $this->sendJsonResponse($data);
        }
        $r = $this->confirmRazorpay($user_id, \app\modules\payments\services\PaymentOrders::WALLET_TOPUP);
        if ($r['ok']) {
            $w = DriverWallet::find()->where(['driver_id' => $user_id, 'payment_type' => DriverWallet::payment_type_credit])->orderBy(['id' => SORT_DESC])->one();
            $data['status'] = self::API_OK;
            $data['details'] = $w ? $w->asJson() : [];
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = $r['error'];
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionAddBalance

    // [doc:start] actionAvailableBalance - /api/driver/available-balance - Available Balance
    public function actionAvailableBalance()
    {
        $data = [];
        try {
            $post = \Yii::$app->request->post();

            $headers = isset(\Yii::$app->request->headers['auth_code'])
                ? \Yii::$app->request->headers['auth_code']
                : Yii::$app->request->getQueryParam('auth_code');
            $auth = new AuthSettings();
            $user_id = $auth->getAuthSession($headers);
            if (!empty($user_id)) {
                $user = User::find()
                    ->where(['id' => $user_id])
                    ->one();
                if (!empty($user)) {
                    $availableBalance = DriverWallet::availableBalance($user->id, \app\modules\geo\services\ServiceModules::DRIVER);
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

    // [doc:start] actionTransactionList - /api/driver/transaction-list - Transaction List
    public function actionTransactionList()
    {
        $data = [];
        try {
            $post = \Yii::$app->request->post();
            $page = !empty($post['page']) ? $post['page'] : 0;
            $start_date = !empty($post['start_date'])? $post['start_date']: '';
            $end_date = !empty($post['end_date']) ? $post['end_date'] : '';
            $headers = isset(\Yii::$app->request->headers['auth_code'])? \Yii::$app->request->headers['auth_code']: Yii::$app->request->getQueryParam('auth_code');
            $auth = new AuthSettings();
            $user_id = $auth->getAuthSession($headers);
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

    // [doc:start] actionWithdrawInfo - /api/driver/withdraw-info - POST|GET /api/driver/withdraw-info.
    /**
     * POST|GET /api/driver/withdraw-info
     * OK: details = {balance, min_withdraw, bank: {account_holder_name, bank_name, account_number_masked, ifsc, upi} | null,
     *               pending: [{id, amount, created_on}]}. The money is paid to this bank; null means the driver must add bank details first.
     */
    public function actionWithdrawInfo()
    {
        $data = [];
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $user_id = (new AuthSettings())->getAuthSession($headers);
        if (empty($user_id)) {
            $data['status'] = self::API_NOK;
            $data['error'] = 'User Not Found';

            return $this->sendJsonResponse($data);
        }
        $bank = DriverBankDetails::find()->where(['driver_id' => $user_id])->one();
        $pending = DriverWallet::find()->where(['driver_id' => $user_id, 'payment_type' => DriverWallet::payment_type_debit, 'status' => DriverWallet::STATUS_PENDING])
            ->orderBy(['id' => SORT_DESC])->limit(5)->all();
        $list = [];
        foreach ($pending as $w) {
            $list[] = ['id' => (int) $w->id, 'amount' => (int) $w->amount, 'created_on' => (string) $w->created_on];
        }
        $acct = $bank ? (string) $bank->account_number : '';
        $data['status'] = self::API_OK;
        $data['details'] = [
            'balance' => DriverWallet::availableBalance($user_id, \app\modules\geo\services\ServiceModules::DRIVER),
            'min_withdraw' => (int) (new \app\modules\admin\models\WebSetting())->getSettingBykey('min_withdraw'),
            'bank' => ($bank && $acct !== '') ? [
                'account_holder_name' => (string) $bank->account_holder_name,
                'bank_name' => (string) $bank->bank_name,
                'account_number_masked' => str_repeat('*', max(0, strlen($acct) - 4)) . substr($acct, -4),
                'ifsc' => (string) $bank->ifsc,
                'upi' => (string) $bank->upi,
            ] : null,
            'pending' => $list,
        ];

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionWithdrawInfo

    // [doc:start] actionWithdraw - /api/driver/withdraw - Withdraw
    public function actionWithdraw()
    {
        $data = [];
        try {
            $post = \Yii::$app->request->post();
            // The amount must be a plain positive number: a negative "withdrawal" used to be accepted and raised the balance.
            $rawAmount = isset($post['amount']) ? trim((string) $post['amount']) : '';
            // Whole rupees only: the wallet stores whole rupees, so 20.5 would have been recorded as 20.
            $amount = preg_match('/^\d{1,7}$/', $rawAmount) ? (int) $rawAmount : 0;
            $headers = isset(\Yii::$app->request->headers['auth_code'])
                ? \Yii::$app->request->headers['auth_code']
                : Yii::$app->request->getQueryParam('auth_code');
            $auth = new AuthSettings();
            $user_id = $auth->getAuthSession($headers);
            if (!empty($user_id)) {
                $user = User::find()
                    ->where(['id' => $user_id])
                    ->one();
                if (!empty($user)) {
                    $availableBalance = DriverWallet::availableBalance(
                        $user->id,
                        \app\modules\geo\services\ServiceModules::DRIVER
                    );
                    // The money is always paid to the bank account the driver registered; without one there is nowhere to send it.
                    $bank = DriverBankDetails::find()->where(['driver_id' => $user_id])->one();
                    $minWithdraw = (int) (new \app\modules\admin\models\WebSetting())->getSettingBykey('min_withdraw');
                    if (empty($bank) || trim((string) $bank->account_number) === '' || trim((string) $bank->ifsc) === '') {
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'Add your bank details first (Add Details > Bank Details); withdrawals are paid to that account.';

                        return $this->sendJsonResponse($data);
                    }
                    if ($amount > 0 && $minWithdraw > 0 && $amount < $minWithdraw) {
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'The minimum withdrawal is ' . $minWithdraw . ' rupees.';

                        return $this->sendJsonResponse($data);
                    }
                    // The whole balance can be withdrawn (it used to have to be strictly more than the amount).
                    if ($amount > 0 && $availableBalance >= $amount) {
                        $driver_wallet = new DriverWallet();
                        $driver_wallet->driver_id = $user_id;
                        $driver_wallet->amount = $amount;
                        $driver_wallet->payment_type =
                            DriverWallet::payment_type_debit;
                        // The bank is written into the entry (account masked) so the request stays readable even if the driver edits the details later.
                        $acct = (string) $bank->account_number;
                        $driver_wallet->method_reason = 'Withdrawal ' . $amount . ' to ' . $bank->bank_name . ' A/C ****' . substr($acct, -4)
                            . ' IFSC ' . $bank->ifsc . (trim((string) $bank->upi) !== '' ? ' UPI ' . $bank->upi : '') . ' (waiting for the office to pay)';
                        $driver_wallet->status = DriverWallet::STATUS_PENDING;
                        if ($driver_wallet->save(false)) {
                            $data['status'] = self::API_OK;
                            $data['details'] = $driver_wallet->asJson();
                        } else {
                            $data['status'] = self::API_NOK;
                            $data['error'] = 'Wallet withdraw failed';
                        }
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = $amount > 0
                            ? 'Available amount is ' . $availableBalance
                            : 'Enter a whole number of rupees greater than zero.';
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

    // [doc:start] actionListFcmNotifications - /api/driver/list-fcm-notifications - List Fcm Notifications
    public function actionListFcmNotifications()
    {
        $data = [];

        // Fetch the auth code from the request headers or query parameters
        $authCode = Yii::$app->request->headers->get('auth_code',Yii::$app->request->getQueryParam('auth_code'));

        // Fetch the user ID based on the auth code
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($authCode);

        if (!empty($user_id)) {
            $query = FcmNotification::find()
                ->where([
                    'user_id' => $user_id,
                    'status' => FcmNotification::STATUS_ACTIVE,
                ])
                ->orderBy(['created_on' => SORT_DESC]);

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => 10, // Set the number of items per page
                    'page' => Yii::$app->request->get('page', 0),                ],

            ]);

            $list = [];
            foreach ($dataProvider->getModels() as $notification) {
                $list[] = $notification->asJson();
            }

            if (!empty($list)) {
                $data['status'] = self::API_OK;
                $data['details'] = $list;
                $data['pagination'] = [
                    'total_pages' => $dataProvider
                        ->getPagination()
                        ->getPageCount(),
                    'current_page' =>
                        $dataProvider->getPagination()->getPage() + 1,
                    'per_page' => $dataProvider->getPagination()->getPageSize(),
                    'total_count' => $dataProvider->getTotalCount(),
                ];
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t(
                    'app',
                    'No Fcm Notifications found for the specified user'
                );
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = Yii::t('app', 'No User found');
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionListFcmNotifications

    // [doc:start] actionDriverStatus - /api/driver/driver-status - Go online / offline. Offline drivers receive no ride offers.
    /**
     * Go online / offline. Offline drivers receive no ride offers.
     *
     * POST /api/driver/driver-status   status = 1 (online) | 2 (offline)
     */
    public function actionDriverStatus()
    {
        $data = [];
        try {
            $post = \Yii::$app->request->post();
            $status = !empty($post['status']) ? $post['status'] : 0;
            $headers = isset(\Yii::$app->request->headers['auth_code'])
                ? \Yii::$app->request->headers['auth_code']
                : Yii::$app->request->getQueryParam('auth_code');
            // WHO IS CALLING: the app sends its login code (auth_code). It is turned into a user id here; a blocked,
            // inactive or logged-out account gets no id and is answered "User Not Found".
            $auth = new AuthSettings();
            $user_id = $auth->getAuthSession($headers);
            if (!empty($user_id)) {
                $user = User::find()
                    ->where(['id' => $user_id])
                    ->one();
                if (!empty($user)) {
                    if ((int) $status === User::online_status_yes && !\app\modules\payments\services\DriverOnboarding::mayWork($user)) {
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'Please finish your onboarding (documents, joining fee and approval) before going online.';
                    } elseif (in_array((int) $status, [User::online_status_yes, User::online_status_no], true)) {
                        // This single value decides whether the dispatch job offers rides to this driver.
                        $user->online_status = $status;
                        if ($user->save(false)) {
                            $data['status'] = self::API_OK;
                            $data['details'] = $user->asJson();
                        } else {
                            $data['status'] = self::API_NOK;
                            $data['error'] = 'status update failed';
                        }
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'status required';
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
        // REPLY: every path above ends here with {status: OK|NOK, details|error}.
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionDriverStatus

    // [doc:start] actionUploadProfilePicture - /api/driver/upload-profile-picture - Upload Profile Picture
    public function actionUploadProfilePicture()
    {
        $data = [];
        $post = Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code'])
            ? \Yii::$app->request->headers['auth_code']
            : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            if (!empty($post)) {
                try {
                    $model = User::find()
                        ->where(['id' => $user_id])
                        ->andWhere(['user_role' => User::ROLE_DRIVER])
                        ->one();
                    $profile_image = !empty($post['profile_image'])
                        ? $post['profile_image']
                        : '';

                    if (!empty($model)) {
                        $model->profile_image = $profile_image;

                        if ($model->save(false)) {
                            $data['status'] = self::API_OK;
                            $data['details'] = $model->asJson();
                        } else {
                            $data['status'] = self::API_NOK;
                            $data['error'] = Yii::t(
                                'app',
                                'User Data Update Failed'
                            );
                        }
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = Yii::t('app', 'User Data Not found');
                    }
                } catch (Exception $e) {
                    $data['status'] = self::API_NOK;
                    $data['error'] = $e->getMessage();
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t('app', 'Data Not Posted');
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = Yii::t('app', 'User Not Found');
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionUploadProfilePicture

    //         ? \Yii::$app->request->headers['auth_code']
    //         : Yii::$app->request->getQueryParam('auth_code');

    //                     // ->andWhere(['booking.status' =>$status ])
    //                     ;

    //                             [

    //                     // elseif($status == Booking::STATUS_WAITING_FOR_DRIVER){
    //                     //         $checkBooking = Booking::find()
    //                     //         ->innerJoinWith('driverRequests as dr')
    //                     //         ->where(['dr.driver_id' => $user_id])
    //                     //         ->andWhere(['booking.payment_status' =>Booking::payment_status_pending ])->andWhere([
    //                     //             'IN',
    //                     //             'dr.status',
    //                     //             [
    //                     //                 Booking::STATUS_DRIVER_ACCEPTED,
    //                     //                 Booking::STATUS_GO_FOR_PICKUP,
    //                     //                 Booking::STATUS_START_RIDE,
    //                     //                 Booking::STATUS_ARRIVED_LOCATION,
    //                     //             ],
    //                     //         ])->one();
    //                     //         if(!empty($checkBooking))
    //                     //         {
    //                     //             $query->andWhere(['dr.status' => 100]);
    //                     //         }else
    //                     //         {
    //                     //             $query->andWhere(['dr.status' => $status]);
    //                     //         }
    //                     //     }

    //                 // echo $query->createCommand()->getRawSql();exit;

    /**
     * Distance and travel time from the driver's last known position (sent by the Partner app every 20 s while online)
     * to the customer's pickup point, for the booking card. The distance is the straight line times 1.3 (roads are
     * longer), the time assumes a city speed of 25 km/h. Both are estimates; empty when either position is unknown.
     *
     * @return array distance_to_pickup_km (1 decimal) and eta_to_pickup_min (whole minutes), or nulls
     */
    private function distanceToPickup($driver, $booking)
    {
        $none = ['distance_to_pickup_km' => null, 'eta_to_pickup_min' => null];
        if (empty($driver) || !is_numeric($driver->lat) || !is_numeric($driver->lng) || (float) $driver->lat == 0.0
            || !is_numeric($booking->pickup_latitude) || !is_numeric($booking->pickup_longitude)) {
            return $none;
        }
        $dLat = deg2rad((float) $booking->pickup_latitude - (float) $driver->lat);
        $dLng = deg2rad((float) $booking->pickup_longitude - (float) $driver->lng);
        $h = sin($dLat / 2) ** 2 + cos(deg2rad((float) $driver->lat)) * cos(deg2rad((float) $booking->pickup_latitude)) * sin($dLng / 2) ** 2;
        $roadKm = 12742 * asin(min(1, sqrt($h))) * 1.3;

        return [
            'distance_to_pickup_km' => round($roadKm, 1),
            'eta_to_pickup_min' => max(1, (int) round($roadKm / 25 * 60)),
        ];
    }

    /**
     * The driver's ride list: offers waiting for an answer (status 1) or the rides already accepted (status 12).
     *
     * POST /api/driver/bookings   status=1 (offers) | status=12 (accepted rides)   page=0..
     * Only bookings that have a driver_requests row for this driver are shown, newest pickup time first, 10 per page.
     * Each row also carries what the driver would earn (estimate minus GST/fees minus commission).
     */
    /**
     * SQL: the ride is closed for the driver - paid (collect-cash) and rated by this driver (add-review). Until then a completed ride
     * stays in the driver's Accepted rides.
     */
    private static function closedForDriverSql()
    {
        return 'booking.payment_status = ' . (int) Booking::payment_status_success
            . ' AND EXISTS (SELECT 1 FROM driver_booking_ratings dbr WHERE dbr.booking_id = booking.id)';
    }

    // [doc:start] actionBookings - /api/driver/bookings - Bookings
    public function actionBookings()
{
    $data = [];
    $post = Yii::$app->request->post();
    $headers = isset(\Yii::$app->request->headers['auth_code'])
        ? \Yii::$app->request->headers['auth_code']
        : Yii::$app->request->getQueryParam('auth_code');
    // WHO IS CALLING: the app sends its login code (auth_code). It is turned into a user id here; a blocked,
    // inactive or logged-out account gets no id and is answered "User Not Found".
    $auth = new AuthSettings();
    $user_id = $auth->getAuthSession($headers);

    if (!empty($user_id)) {
        // WHICH LIST: no status asked for = the offers still waiting for an answer.
        $status = !empty($post['status']) ? $post['status'] : Booking::STATUS_WAITING_FOR_DRIVER;

        try {
            $model = User::find()
                ->where(['id' => $user_id])
                ->andWhere(['user_role' => User::ROLE_DRIVER])
                ->one();
             $currentDateTime = date('Y-m-d H:i:s'); // Include time for more accurate comparison

            if (!empty($model)) {
                \app\modules\dispatch\services\BookingExpiry::run(200, [
                    'id' => (new \yii\db\Query())->select('booking_id')->from('driver_requests')->where(['driver_id' => $user_id]),
                ]);
                // BUILD THE LIST: bookings joined to this driver's offers (driver_requests), not yet paid, not cancelled by the customer.
                $query = Booking::find()
                    ->innerJoinWith('driverRequests as dr')
                    ->where(['dr.driver_id' => $user_id]);
                // Offers and rides in progress are unpaid; the Accepted list also keeps a completed ride until it is paid and this
                // driver has rated the customer, so it is not filtered by payment here.
                if ($status != Booking::STATUS_ACCEPTED_RIDES) {
                    $query->andWhere(['booking.payment_status' => Booking::payment_status_pending]);
                }

                // Add a condition to filter out canceled bookings
                $query->andWhere(['!=', 'booking.status', Booking::STATUS_USER_CANCELLED]);

                       ;
// You may also want to add a time comparison for pickup_time
   // ['>', 'pickup_date', $currentDateTime], // Pickup date is in the future
  // ['and',
      // ['=', 'pickup_date', $currentDateTime], // Pickup date is today
   // ['>=', 'pickup_time', date('H:i:s')],   // Pickup time is in the future
                if (!empty($status)) {
                    // "Accepted rides" = every ride the driver holds that is not finished: accepted, on the way, arrived, started.
                    // Anything else = bookings in that single status, excluding the ones this driver already rejected.
                    if ($status == Booking::STATUS_ACCEPTED_RIDES) {
                        // A completed ride stays here (bill, collect payment, rate the customer) until the payment is collected AND this
                        // driver has rated the customer; then it moves to Completed.
                        $query->andWhere(['OR',
                            ['booking.status' => [
                                Booking::STATUS_DRIVER_ACCEPTED,
                                Booking::STATUS_GO_FOR_PICKUP,
                                Booking::STATUS_START_RIDE,
                                Booking::STATUS_ARRIVED_LOCATION,
                            ]],
                            ['AND', ['booking.status' => Booking::STATUS_RIDE_COMPLETED],
                                new \yii\db\Expression('NOT (' . self::closedForDriverSql() . ')')],
                        ])
                        ->andWhere(['booking.driver_id' => $user_id]);
                    } else {
                        // Offers this driver turned down, or accepted and then cancelled, are not offered to them again.
                        $query->andWhere(['booking.status' => $status])
                        ->andWhere(['NOT IN','dr.status',[Booking::STATUS_DRIVER_REJECTED, Booking::STATUS_DRIVER_CANCELLED]]);
                        if ((int) $status === Booking::STATUS_WAITING_FOR_DRIVER) {
                            $query->andWhere(\app\modules\dispatch\services\BookingExpiry::availablePickupCondition());
                        }
                    }
                }

                // Filter out records where pickup_date and pickup_time are less than current time minus 15 minutes
              //

                // Soonest pickup first.
                $query->orderBy([
                    'pickup_date' => SORT_ASC,
                    'pickup_time' => SORT_ASC,
                ]);

                // Paging: 10 per page, page number from ?page=.
                $dataProvider = new ActiveDataProvider([
                    'query' => $query,
                    'pagination' => [
                        'pageSize' => 10,
                        'page' => Yii::$app->request->get('page', 0),
                    ],
                ]);

                $list = [];
                $settings = new WebSetting();
                $driver = User::find()->where(['id'=>$user_id])->one();
                $default_admin_commission_raider = $settings->getSettingBykey('default_admin_commission_raider');
                 // COMMISSION: the driver's own percentage if set, otherwise the default from Web Settings.
                 $admin_per = !empty($driver->admin_per) ? $driver->admin_per : $default_admin_commission_raider;

                // Add the driver's expected earning to each booking: estimate - GST - fees = base; commission = base x %; driver gets the rest.
                foreach ($dataProvider->getModels() as $booking) {
                    $bookingData = $booking->asJson();

                    // HOW FAR AND HOW LONG: from this driver's last known position to the customer's pickup point.
                    $bookingData += $this->distanceToPickup($driver, $booking);

                    $estimatePrice = $bookingData['estimate_price'] ?? 0;
                    $gst = $bookingData['gst'] ?? 0;
                    $securedFee = $bookingData['secured_fee'] ?? 0;
                    $platformCharges = $bookingData['platform_charges'] ?? 0;

                    $adminEarnings = ($admin_per/100) * ($estimatePrice - $gst - $securedFee - $platformCharges);

                    $driverEarnings = $estimatePrice - $adminEarnings - $gst - $securedFee - $platformCharges;

                    $bookingData['admin_earnings'] = number_format($adminEarnings, 2, '.', '');
                    $bookingData['driver_earnings'] = number_format($driverEarnings, 2, '.', '');

                    $list[] = $bookingData;
                }

                // Nothing found is a NOK ("No Bookings found") - the app treats that as an empty list, not an error.
                if (!empty($list)) {

                    $data['status'] = self::API_OK;
                    $data['details'] = $list;
                    $data['pagination'] = [
                        'total_pages' => $dataProvider
                            ->getPagination()
                            ->getPageCount(),
                        'current_page' =>
                            $dataProvider->getPagination()->getPage() + 1,
                        'per_page' => $dataProvider
                            ->getPagination()
                            ->getPageSize(),
                        'total_count' => $dataProvider->getTotalCount(),
                    ];
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = Yii::t(
                        'app',
                        'No Bookings found for the specified user driver'
                    );
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t('app', 'Driver data not found');
            }
        // Anything unexpected is answered as NOK (the text is cleaned of SQL/stack details by sendJsonResponse).
        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }
    } else {
        $data['status'] = self::API_NOK;
        $data['error'] = Yii::t('app', 'User Not Found');
    }

    // REPLY: every path above ends here with {status: OK|NOK, details|error}.
    return $this->sendJsonResponse($data);
}
    // [doc:end] actionBookings

    // [doc:start] actionBookingsHistory - /api/driver/bookings-history - Bookings History
    public function actionBookingsHistory()
    {
        $data = [];
        $post = Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code'])
            ? \Yii::$app->request->headers['auth_code']
            : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            $status = !empty($post['status']) ? $post['status'] : '';
            $start_date = !empty($post['start_date']) ? $post['start_date'] : '';
            $end_date = !empty($post['end_date']) ? $post['end_date'] : '';

            try {
                $model = User::find()->where(['id' => $user_id])->andWhere(['user_role' => User::ROLE_DRIVER])->one();
                if (!empty($model)) {
                    $query = Booking::find()
                        ->innerJoinWith('driverRequests as dr')
                        ->where(['dr.driver_id' => $user_id]);

                    if (!empty($status)) {
                        if ($status == Booking::STATUS_ACCEPTED_RIDES) {
                            $query->andWhere([
                                'IN',
                                'dr.status',
                                [
                                    Booking::STATUS_DRIVER_ACCEPTED,
                                    Booking::STATUS_GO_FOR_PICKUP,
                                    Booking::STATUS_START_RIDE,
                                    Booking::STATUS_ARRIVED_LOCATION,
                                ],
                            ])->andWhere(['booking.driver_id' => $user_id]);
                        } else if($status==Booking::STATUS_CANCELLED) {
                            // CANCELLED RIDES: rides this driver actually held that did not happen.
                            //  - the customer cancelled: the booking keeps its driver and shows as cancelled by the customer;
                            //  - this driver cancelled: the booking goes back to the pool (and loses its driver), so the driver's own
                            //    offer row, marked "driver cancelled", is what puts it in this list.
                            // Offers the driver only turned down were never theirs and are not listed.
                            $query->andWhere(['OR',
                                ['AND', ['booking.driver_id' => $user_id],
                                    ['booking.status' => [Booking::STATUS_USER_CANCELLED, Booking::STATUS_DRIVER_CANCELLED]]],
                                ['dr.status' => Booking::STATUS_DRIVER_CANCELLED],
                            ]);
                        } else if($status==Booking::STATUS_RIDE_COMPLETED) {
                            // Completed = finished, payment collected and the customer rated by this driver (until then it is in Accepted rides).
                            $query->andWhere(['in','booking.status',[Booking::STATUS_RIDE_COMPLETED]])
                            ->andWhere(new \yii\db\Expression(self::closedForDriverSql()))
                            ->andWhere(['booking.driver_id' => $user_id]);
                        }
                        else {
                            $query->andWhere(['dr.status' => $status])
                            ->andWhere(['booking.driver_id' => $user_id]);
                        }

                    }else{
                         $query->andWhere(['booking.driver_id' => $user_id]);
                    }
                    if(!empty($start_date ) && !empty($end_date) ){
                        $query->andWhere(['between', 'booking.created_on', $start_date, $end_date]);
                    }

                    $dataProvider = new ActiveDataProvider([
                        'query' => $query,

                        'sort' => [
                            'defaultOrder' => [
                                'created_on' => SORT_DESC,
                            ],
                        ],

                        'pagination' => [
                            'pageSize' => 10, // Set the number of items per page
                            'page' => Yii::$app->request->get('page', 0),
                        ],
                    ]);

                    $list = [];

                    foreach ($dataProvider->getModels() as $booking) {
                        $row = $booking->asJson($user_id);
                        // Say who cancelled. A ride this driver cancelled is back in the pool with another status, so it is shown to
                        // this driver as cancelled (the card and the details follow the status).
                        $ownOffer = (int) (new \yii\db\Query())->select('status')->from('driver_requests')
                            ->where(['booking_id' => $booking->id, 'driver_id' => $user_id])->scalar();
                        if ($ownOffer === Booking::STATUS_DRIVER_CANCELLED) {
                            $row['status'] = Booking::STATUS_DRIVER_CANCELLED;
                            $row['cancelled_by'] = 'driver';
                        } elseif ((int) $booking->status === Booking::STATUS_USER_CANCELLED) {
                            $row['cancelled_by'] = 'customer';
                        }
                        $list[] = $row;
                    }
                    if (!empty($list)) {
                        $data['status'] = self::API_OK;
                        $data['details'] = $list;
                        $data['pagination'] = ['total_pages' => $dataProvider->getPagination()->getPageCount(),
                            'current_page' =>
                                $dataProvider->getPagination()->getPage() + 1,
                            'per_page' => $dataProvider
                                ->getPagination()
                                ->getPageSize(),
                            'total_count' => $dataProvider->getTotalCount(),
                        ];
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = Yii::t(
                            'app',
                            'No Bookings found for the specified user'
                        );
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = Yii::t('app', 'Driver data not found');
                }
            } catch (Exception $e) {
                $data['status'] = self::API_NOK;
                $data['error'] = $e->getMessage();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = Yii::t('app', 'User Not Found');
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionBookingsHistory

    // [doc:start] actionAcceptRide - /api/driver/accept-ride - The driver accepts a ride offer. First driver wins.
    /**
     * The driver accepts a ride offer. First driver wins.
     *
     * POST /api/driver/accept-ride   booking_id
     * Checks: booking still waiting; driver wallet >= minimum; driver has no ride in progress.
     * Effect: booking -> Driver Accepted with this driver; the driver's offer -> accepted, every other offer ->
     * closed; both sides are notified; a booking_logs line is written.
     */
    public function actionAcceptRide()
    {
        $data = [];
        $post = Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code'])
            ? \Yii::$app->request->headers['auth_code']
            : Yii::$app->request->getQueryParam('auth_code');
        // WHO IS CALLING: the app sends its login code (auth_code). It is turned into a user id here; a blocked,
        // inactive or logged-out account gets no id and is answered "User Not Found".
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            $booking_id = !empty($post['booking_id'])? $post['booking_id']: '';

            try {
                // The caller must be a driver account.
                $model = User::find()->where(['id' => $user_id])->andWhere(['user_role' => User::ROLE_DRIVER])->one();
                if (!empty($model)) {
                    \app\modules\dispatch\services\BookingExpiry::run(1, ['id' => $booking_id]);
                    // The booking must still be waiting. If another driver already took it, nothing is found here.
                    $booking = Booking::find()->where(['id' => $booking_id])->andWhere(['status' => Booking::STATUS_WAITING_FOR_DRIVER])
                        ->one();
                    if (!empty($booking)) {
                        // PHOTO RULE: a driver must have a profile photo before accepting a ride (the customer sees it on the driver card).
                        if (trim((string) $model->profile_image) === '') {
                            $data['status'] = self::API_NOK;
                            $data['error'] = 'Please add your profile photo before accepting rides. Open Profile and add a clear photo of yourself.';
                            $data['details'] = $data['error'];
                            return $this->sendJsonResponse($data);
                        }
                        // WALLET RULE: the commission of cash rides is taken from the driver's wallet, so the wallet must hold at least the
                        // minimum set in Web Settings (driver_min_wallet_bal).
                        $availableBalance = DriverWallet::availableBalance($user_id, \app\modules\geo\services\ServiceModules::DRIVER);
                        $settings = new WebSetting();
                        $driver_min_wallet_bal = $settings->getSettingBykey('driver_min_wallet_bal');
if($availableBalance >= $driver_min_wallet_bal) {

    //check for already having ride or not
    // ONE RIDE AT A TIME: a driver with a ride accepted / on the way / arrived / started cannot take another.
    $hasOngoingRides = Booking::find()
                    ->where(['driver_id' => $user_id])
                    ->andWhere(['status' => [
                        Booking::STATUS_DRIVER_ACCEPTED,
                        Booking::STATUS_GO_FOR_PICKUP,
                        Booking::STATUS_START_RIDE,
                        Booking::STATUS_ARRIVED_LOCATION,

                    ]])
                    ->exists();
    if ($hasOngoingRides) {
         $data['status'] = self::API_NOK;
        $data['details'] = "You already having ongoing ride. You are not allowed to accept now";
         return $this->sendJsonResponse($data);

    }
    // RATING IS COMPULSORY: a completed ride must be paid (collect-cash) and the customer rated (stars and a review) before the next ride.
    $openCompleted = Booking::find()->select('id')
        ->where(['driver_id' => $user_id, 'status' => Booking::STATUS_RIDE_COMPLETED])
        ->andWhere('NOT (' . self::closedForDriverSql() . ')')
        ->orderBy(['id' => SORT_DESC])
        ->scalar();
    if ($openCompleted) {
        $data['status'] = self::API_NOK;
        $data['details'] = 'Please collect the payment and rate the customer for booking #' . $openCompleted . ' before accepting a new ride.';
        $data['error'] = $data['details'];
        return $this->sendJsonResponse($data);
    }
    // Claim the booking with ONE conditional update. Two drivers tapping Accept at the same moment both pass the
    // "still waiting" check above, but only one of them changes a row here; the other gets 0 and is refused below.
    $claimed = Booking::updateAll(
        ['status' => Booking::STATUS_DRIVER_ACCEPTED, 'driver_id' => $model->id, 'notif_status' => 0, 'updated_on' => date('Y-m-d H:i:s')],
        ['and',
            ['id' => $booking->id, 'status' => Booking::STATUS_WAITING_FOR_DRIVER],
            \app\modules\dispatch\services\BookingExpiry::availablePickupCondition(),
        ]
    );
    if (!$claimed) {
        $data['status'] = self::API_NOK;
        \app\modules\dispatch\services\BookingExpiry::run(1, ['id' => $booking->id]);
        $data['error'] = Yii::t('app', 'This booking is no longer available. Its pickup time has passed or another driver has accepted it.');
        return $this->sendJsonResponse($data);
    }
    $booking->refresh();
    // Tell the customer and the driver (after the booking really is theirs).
    Yii::$app->push->notifyUser($booking->id, $booking->user_id, 'Accepted Booking', 'Ride successfully accepted. booking id #' . $booking->id);
    Yii::$app->push->notifyUser($booking->id, $booking->driver_id, 'Accepted Booking', 'you accepted ride. booking id #' . $booking->id);
    if ($booking->save(false)) {
        // CLOSE THE OTHER OFFERS: every other driver's offer for this booking is cancelled; this driver's becomes "accepted".
        $driver_requests = DriverRequests::find()->where(['booking_id' => $booking->id])->andWhere(['status' =>Booking::STATUS_WAITING_FOR_DRIVER])->all();
        if (!empty($driver_requests)) {
            foreach ($driver_requests as $driver_requests_data) {
                if (
                    $driver_requests_data->driver_id !=$user_id
                ) {
                    $driver_requests_data->status =DriverRequests::STATUS_BOOKING_AUTO_CANCELLED;
                    $driver_requests_data->save(false);
                } else {
                    $driver_requests_data->status = DriverRequests::STATUS_DRIVER_ACCEPTED;
                    $driver_requests_data->save(false);
                }
            }
        }

        $title = 'Booking  Accepted';
        $body = 'Your booking was accepted. Booking ID #' .$booking->id;
        $booking->id;
        Yii::$app->push->notifyUser($booking->id, $booking->user_id, $title, $body);
        // History line shown on the booking page in the dashboard.
        $booking_logs = new  BookingLogs();
        $booking_logs->booking_id = $booking->id;
        $booking_logs->description = $booking->getStateOptionsBadges();
        $booking_logs->status = BookingLogs::STATUS_ACTIVE;
        $booking_logs->save(false);

        $data['status'] = self::API_OK;
        $data['details'] = $booking->asJson();
    } else {
        $data['status'] = self::API_NOK;
        $data['error'] = Yii::t('app','Booking accepted failed');
    }

}else{

    $data['status'] = self::API_NOK;
    $data['error'] = Yii::t('app','You can not accept rides available wallet balance is '.$availableBalance);

}

                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = Yii::t('app', 'This booking is no longer available. Its pickup time has passed or another driver has accepted it.');
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = Yii::t('app', 'Driver data not found');
                }
            // Anything unexpected is answered as NOK (the text is cleaned of SQL/stack details by sendJsonResponse).
            } catch (Exception $e) {
                $data['status'] = self::API_NOK;
                $data['error'] = $e->getMessage();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = Yii::t('app', 'User Not Found');
        }

        // REPLY: every path above ends here with {status: OK|NOK, details|error}.
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionAcceptRide

    // [doc:start] actionRejectRide - /api/driver/reject-ride - The driver turns an offer down. Only this driver's offer is marked rejected; the booking stays open for others.
    /**
     * The driver turns an offer down. Only this driver's offer is marked rejected; the booking stays open for others.
     *
     * POST /api/driver/reject-ride   booking_id
     */
    public function actionRejectRide()
    {
        $data = [];
        $post = Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code'])
            ? \Yii::$app->request->headers['auth_code']
            : Yii::$app->request->getQueryParam('auth_code');
        // WHO IS CALLING: the app sends its login code (auth_code). It is turned into a user id here; a blocked,
        // inactive or logged-out account gets no id and is answered "User Not Found".
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            $booking_id = !empty($post['booking_id'])
                ? $post['booking_id']
                : '';

            try {
                $model = User::find()
                    ->where(['id' => $user_id])
                    ->andWhere(['user_role' => User::ROLE_DRIVER])
                    ->one();
                if (!empty($model)) {
                    $booking = Booking::find()
                        ->where(['id' => $booking_id])
                        ->one();
                    if (!empty($booking)) {
                        // Find THIS driver's offer for the booking (a driver can only reject what was offered to them).
                        $driver_requests = DriverRequests::find()
                            ->where(['booking_id' => $booking->id])
                            ->andWhere(['driver_id' => $user_id])
                            ->one();
                        if (!empty($driver_requests)) {
                            $driver_requests->status =
                                DriverRequests::STATUS_DRIVER_REJECTED;
                            // Mark the offer rejected. The booking itself is untouched.
                            $driver_requests->save(false);
                            $data['status'] = self::API_OK;
                            $data['details'] = 'Booking rejected successfully';
                        } else {
                            $data['status'] = self::API_NOK;
                            $data['error'] = Yii::t(
                                'app',
                                'Booking request not found'
                            );
                        }
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = Yii::t(
                            'app',
                            'Booking data not found'
                        );
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = Yii::t('app', 'Driver data not found');
                }
            // Anything unexpected is answered as NOK (the text is cleaned of SQL/stack details by sendJsonResponse).
            } catch (Exception $e) {
                $data['status'] = self::API_NOK;
                $data['error'] = $e->getMessage();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = Yii::t('app', 'User Not Found');
        }

        // REPLY: every path above ends here with {status: OK|NOK, details|error}.
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionRejectRide

    // [doc:start] actionVehicleVerification - /api/driver/vehicle-verification - Saves the photos the driver takes of the car before/after the ride (selfie, 4 car sides).
    /**
     * Saves the photos the driver takes of the car before/after the ride (selfie, 4 car sides).
     *
     * POST /api/driver/vehicle-verification   json_data=[{booking_id, image, image_type, type}, ...]
     * image is the URL returned by /api/media/upload. Photos for a booking that is not this driver's are ignored.
     */
    public function actionVehicleVerification()
    {
        $data = [];
        try {
            $post = Yii::$app->request->post();
            $headers = isset(\Yii::$app->request->headers['auth_code'])? \Yii::$app->request->headers['auth_code']: Yii::$app->request->getQueryParam('auth_code');
            // WHO IS CALLING: the app sends its login code (auth_code). It is turned into a user id here; a blocked,
            // inactive or logged-out account gets no id and is answered "User Not Found".
            $auth = new AuthSettings();
            $user_id = $auth->getAuthSession($headers);
            if (!empty($user_id)) {
                $json_data = !empty($post['json_data'])? $post['json_data']: '';
                // One entry per photo.
                $booking_data = json_decode($json_data);
                if (!empty($booking_data[0])) {
                    foreach ($booking_data as $booking_data_list) {
                        // Only the driver who holds the booking may attach photos to it.
                        $ownsBooking = Booking::find()
                            ->where(['id' => $booking_data_list->booking_id, 'driver_id' => $user_id])
                            ->exists();
                        if (!$ownsBooking) {
                            continue;
                        }
                        // Store the photo against its booking.
                        $vehicle_verification = new VehicleVerification();
                        $vehicle_verification->booking_id =
                            $booking_data_list->booking_id;
                        $vehicle_verification->image =
                            $booking_data_list->image;
                        $vehicle_verification->image_type =
                            $booking_data_list->image_type;
                        $vehicle_verification->type = $booking_data_list->type;
                        $vehicle_verification->status =
                            VehicleVerification::STATUS_ACTIVE;
                        $vehicle_verification->save(false);
                        // the photo belongs to this ride: in the bucket it lies in the folder ride/<booking id>/
                        try {
                            $sides = [1 => 'front', 2 => 'back', 3 => 'side-one', 4 => 'side-two', 5 => 'damages', 6 => 'selfie'];
                            \app\modules\filestorage\services\MediaStore::attachToBooking(
                                $booking_data_list->image,
                                $booking_data_list->booking_id,
                                ((int) $booking_data_list->type === 2 ? 'after' : 'before') . '-' . ($sides[(int) $booking_data_list->image_type] ?? 'photo')
                            );
                        } catch (\Throwable $e) {
                            Yii::warning('ride photo not filed under its ride: ' . $e->getMessage(), 'upload');
                        }
                    }
                    $data['status'] = self::API_OK;
                    $data['details'] = Yii::t('app', 'data added successfully');
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = Yii::t('app', 'Json Not Found');
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t('app', 'User Not Found');
            }
        // Anything unexpected is answered as NOK (the text is cleaned of SQL/stack details by sendJsonResponse).
        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }

        // REPLY: every path above ends here with {status: OK|NOK, details|error}.
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionVehicleVerification


    /**
     * The booking, only if it is assigned to this driver and in one of the given states.
     * Ride steps (pickup, arrival, start, complete) must not work on another driver's ride or out of order.
     */
    private function ownedBooking($bookingId, $driverId, array $statuses)
    {
        return Booking::find()
            ->where(['id' => $bookingId, 'driver_id' => $driverId, 'status' => $statuses])
            ->one();
    }
    // [doc:start] actionGoForPickup - /api/driver/go-for-pickup - The driver starts driving to the customer. Booking: Driver Accepted -> Go for Pickup.
    /**
     * The driver starts driving to the customer. Booking: Driver Accepted -> Go for Pickup.
     *
     * POST /api/driver/go-for-pickup   booking_id
     * Only the driver the booking is assigned to, and only from "Driver Accepted".
     */
    public function actionGoForPickup()
    {
        $data = [];
        try {
            $post = Yii::$app->request->post();
            $headers = isset(\Yii::$app->request->headers['auth_code'])
                ? \Yii::$app->request->headers['auth_code']
                : Yii::$app->request->getQueryParam('auth_code');
            // WHO IS CALLING: the app sends its login code (auth_code). It is turned into a user id here; a blocked,
            // inactive or logged-out account gets no id and is answered "User Not Found".
            $auth = new AuthSettings();
            $user_id = $auth->getAuthSession($headers);
            if (!empty($user_id)) {
                $booking_id = !empty($post['booking_id'])
                    ? $post['booking_id']
                    : '';

                if (!empty($booking_id)) {
                    // OWNERSHIP AND ORDER: must be this driver's booking and in the right previous state, otherwise "booking data not found".
                    $booking = $this->ownedBooking($booking_id, $user_id, [Booking::STATUS_DRIVER_ACCEPTED]);
                    if (!empty($booking)) {
                        // Move the ride one step forward.
                        $booking->status = Booking::STATUS_GO_FOR_PICKUP;
                        if ($booking->save(false)) {
                            $driver_requests = DriverRequests::find()
                                ->where(['driver_id' => $user_id])
                                ->andWHere(['booking_id' => $booking_id])
                                ->one();
                            if (!empty($driver_requests)) {
                                $driver_requests->status = $booking->status;
                                $driver_requests->save(false);
                            }

                            $title = 'Driver on the way';
                            $body = 'Your driver is on the way to the pickup point. Booking ID #' .$booking->id;
                            $booking->id;
                            Yii::$app->push->notifyUser($booking->id,$booking->user_id,$title,$body);

                            // History line for the dashboard.
                            $booking_logs = new  BookingLogs();
                            $booking_logs->booking_id = $booking->id;
                            $booking_logs->description = $booking->getStateOptionsBadges();
                            $booking_logs->status = BookingLogs::STATUS_ACTIVE;
                            $booking_logs->save(false);

                            $data['status'] = self::API_OK;
                            $data['details'] = Yii::t('app','ride go for pickup');
                        } else {
                            $data['status'] = self::API_NOK;
                            $data['error'] = Yii::t(
                                'app',
                                'ride go for pickup failed'
                            );
                        }
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = Yii::t(
                            'app',
                            'booking data not found'
                        );
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = Yii::t('app', 'booking_id required');
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t('app', 'User Not Found');
            }
        // Anything unexpected is answered as NOK (the text is cleaned of SQL/stack details by sendJsonResponse).
        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }

        // REPLY: every path above ends here with {status: OK|NOK, details|error}.
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionGoForPickup

    // [doc:start] actionNotifyArrived - /api/driver/notify-arrived - The driver tells the customer "I have arrived, please share your OTP". Sends two notifications; the booking.
    /**
     * The driver tells the customer "I have arrived, please share your OTP". Sends two notifications; the booking
     * status does not change here (the OTP check in arrived-location does that).
     *
     * POST /api/driver/notify-arrived   booking_id
     */
    public function actionNotifyArrived()
    {
        $data = [];

        try {
            $post = Yii::$app->request->post();
            $headers = isset(\Yii::$app->request->headers['auth_code'])
                ? \Yii::$app->request->headers['auth_code']
                : Yii::$app->request->getQueryParam('auth_code');
            // WHO IS CALLING: the app sends its login code (auth_code). It is turned into a user id here; a blocked,
            // inactive or logged-out account gets no id and is answered "User Not Found".
            $auth = new AuthSettings();
            $user_id = $auth->getAuthSession($headers);

            if(!empty($user_id)){
                $booking_id = !empty($post['booking_id'])
                ? $post['booking_id']
                : '';
                if (!empty($booking_id)) {
                    $booking = $this->ownedBooking($booking_id, $user_id, [Booking::STATUS_DRIVER_ACCEPTED, Booking::STATUS_GO_FOR_PICKUP]);
                    if (empty($booking)) {
                        $data['status'] = self::API_NOK;
                        $data['error'] = Yii::t('app', 'Booking data not found');
                        return $this->sendJsonResponse($data);
                    }

                  		 $booking->notif_status = $booking::STATUS_WAITING_FOR_DRIVER;

                        // Notify User
                        // Message to the customer: the driver is here, share your OTP (ride is cancelled if not within 15 minutes).
                        $user_title = 'Arrived Location';
                        $user_body = 'Driver arrived.Please Share your OTP,otherwise your ride will be cancelled in 15 minutes. Booking ID #' . $booking->id;
                        Yii::$app->push->notifyUser($booking->id, $booking->user_id, $user_title, $user_body);

                        // Notify Driver
                        // Confirmation to the driver.
                        $driver_title = 'Arrived Location';
                        $driver_body = 'You have arrived at the location. Booking ID #' . $booking->id;
                        Yii::$app->push->notifyUser($booking->id, $booking->driver_id, $driver_title, $driver_body);

                  		$booking->save();

                        $data['status'] = self::API_OK;
                        $data['details'] = Yii::t('app', 'Notifications sent successfully');

                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = Yii::t('app', 'Booking data not found');
                }
            }else{
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t('app', 'User Not Found');
            }

        // Anything unexpected is answered as NOK (the text is cleaned of SQL/stack details by sendJsonResponse).
        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }

        // REPLY: every path above ends here with {status: OK|NOK, details|error}.
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionNotifyArrived

    // [doc:start] actionArrivedLocation - /api/driver/arrived-location - The driver is at the pickup point and enters the 4-digit OTP the customer shows. This is the security gate:.
    /**
     * The driver is at the pickup point and enters the 4-digit OTP the customer shows. This is the security gate:
     * without the right OTP the ride cannot start.
     *
     * POST /api/driver/arrived-location   booking_id, otp, final_pickup_latitude, final_pickup_longitude
     * Booking: Accepted / Go for Pickup -> Arrived at Location (only when the OTP matches).
     */
    public function actionArrivedLocation()
    {
        $data = [];
        try {
            $post = Yii::$app->request->post();
            $headers = isset(\Yii::$app->request->headers['auth_code'])
                ? \Yii::$app->request->headers['auth_code']
                : Yii::$app->request->getQueryParam('auth_code');
            // WHO IS CALLING: the app sends its login code (auth_code). It is turned into a user id here; a blocked,
            // inactive or logged-out account gets no id and is answered "User Not Found".
            $auth = new AuthSettings();
            $user_id = $auth->getAuthSession($headers);
            if (!empty($user_id)) {
                $booking_id = !empty($post['booking_id'])
                    ? $post['booking_id']
                    : '';
                $final_pickup_latitude = !empty($post['final_pickup_latitude'])
                    ? $post['final_pickup_latitude']
                    : '';
                $final_pickup_longitude = !empty(
                    $post['final_pickup_longitude']
                )
                    ? $post['final_pickup_longitude']
                    : '';
                $otp = !empty($post['otp']) ? $post['otp'] : '';

                if (!empty($booking_id)) {
                    $booking = $this->ownedBooking($booking_id, $user_id, [Booking::STATUS_DRIVER_ACCEPTED, Booking::STATUS_GO_FOR_PICKUP]);
                    if (!empty($booking)) {
                        // THE OTP CHECK: compared with the code created when the customer booked.
                        if ($otp == $booking->otp) {
                            // Remember where the driver really picked the customer up (used to measure the ride).
                            $booking->final_pickup_latitude = $final_pickup_latitude;
                            $booking->final_pickup_longitude = $final_pickup_longitude;
                            $booking->status = Booking::STATUS_ARRIVED_LOCATION;
                            if ($booking->save(false)) {
                                $driver_requests = DriverRequests::find()
                                    ->where(['driver_id' => $user_id])
                                    ->andWHere(['booking_id' => $booking_id])
                                    ->one();
                                if (!empty($driver_requests)) {
                                    $driver_requests->status = $booking->status;
                                    $driver_requests->save(false);
                                }

                            $title = 'OTP verified';
                            $body = 'OTP verified successfully. Booking ID #' .$booking->id;
                            $booking->id;
                            Yii::$app->push->notifyUser($booking->id,$booking->user_id,$title,$body);

                             $title = 'OTP verified';
                            $body = 'OTP verified successfully. Booking ID #' .$booking->id;
                            $notification  =   Yii::$app->push->notifyUser($booking->id,$booking->driver_id,$title,$body);

                            // History line for the dashboard.
                            $booking_logs = new  BookingLogs();
                            $booking_logs->booking_id = $booking->id;
                            $booking_logs->description = $booking->getStateOptionsBadges();
                            $booking_logs->status = BookingLogs::STATUS_ACTIVE;
                            $booking_logs->save(false);

                                $data['status'] = self::API_OK;
                                $data['details'] = Yii::t('app','ride started ');
                            } else {
                                $data['status'] = self::API_NOK;
                                $data['error'] = Yii::t(
                                    'app',
                                    'ride started failed'
                                );
                            }
                        } else {
                            $data['status'] = self::API_NOK;
                            $data['error'] = Yii::t(
                                'app',
                                'Otp verification failed'
                            );
                        }
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = Yii::t(
                            'app',
                            'booking data not found'
                        );
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = Yii::t('app', 'booking_id required');
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t('app', 'User Not Found');
            }
        // Anything unexpected is answered as NOK (the text is cleaned of SQL/stack details by sendJsonResponse).
        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }

        // REPLY: every path above ends here with {status: OK|NOK, details|error}.
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionArrivedLocation

    /**
     * Called with every position the Partner app reports (about every 20 s while the driver is online). When this driver has an
     * in-city ride in progress and has been beyond the admin's in-city radius around the pickup point on two positions in a row
     * (so one GPS blip does nothing), the customer gets a push notification and an entry in their in-app notification list,
     * the driver gets a push, and the booking log gets a note. It happens once per ride (`radius_alert_at`). The fare is
     * changed to outstation when the ride is completed (see settleRadiusCrossing), not here.
     */
    private function alertIfRadiusCrossed($driver, $previousLat, $previousLng, $lat, $lng)
    {
        try {
            $radiusKm = Booking::incityRadiusKm();
            $booking = Booking::find()
                ->where(['driver_id' => $driver->id, 'status' => Booking::STATUS_START_RIDE, 'trip_type' => Booking::TRIP_TYPE_INTERCITY])
                ->andWhere(['radius_alert_at' => null])
                ->one();
            if (empty($booking) || !is_numeric($booking->pickup_latitude) || !is_numeric($booking->pickup_longitude)) {
                return;
            }
            $polygon = Booking::serviceAreaPolygon($booking->city_id);
            // beyond the in-city area: outside the city's map, or (when the admin set a radius) farther than the radius from the pickup
            $beyond = function ($pointLat, $pointLng) use ($booking, $radiusKm, $polygon) {
                if (!is_numeric($pointLat) || !is_numeric($pointLng)) {
                    return false;
                }

                return Booking::outsideServiceArea($pointLat, $pointLng, $polygon)
                    || ($radiusKm > 0 && Booking::kmBetween($booking->pickup_latitude, $booking->pickup_longitude, $pointLat, $pointLng) > $radiusKm);
            };
            if (!$beyond($lat, $lng) || !$beyond($previousLat, $previousLng)) {
                return;
            }
            // Only the first position pair that qualifies sends the alert, even when two reports arrive together.
            $claimed = Booking::updateAll(['radius_alert_at' => date('Y-m-d H:i:s')], ['id' => $booking->id, 'radius_alert_at' => null]);
            if (!$claimed) {
                return;
            }
            Yii::$app->push->notifyUser($booking->id, $booking->user_id, 'Outstation limit crossed',
                'Your trip has left the in-city area. The fare will be charged as an outstation trip. Booking ID #' . $booking->id);
            Yii::$app->push->notifyDriver($booking->id, $driver->id, 'Outstation limit crossed',
                'You have left the in-city area. This ride will be charged as an outstation trip. Booking ID #' . $booking->id);
            $log = new BookingLogs();
            $log->booking_id = $booking->id;
            $log->description = 'Ride left the in-city area (outside the city map or beyond the in-city radius): customer and driver were alerted';
            $log->status = BookingLogs::STATUS_ACTIVE;
            $log->save(false);
        } catch (\Throwable $e) {
            Yii::error('radius alert failed for driver ' . $driver->id . ': ' . $e->getMessage(), 'booking');
        }
    }

    /**
     * Called when a ride is completed. Measures, from the driver's route log and the final drop, how far from the booked pickup point
     * the ride went (maxKm) and how far from it the ride ended (endKm), saves the route in booking_route_log, and settles two things:
     *  1. ONE WAY OR ROUND TRIP: a one-way booking that went out and ended back at the pickup is billed as a round trip; a round trip
     *     that ended far from the pickup is billed as one way (Booking::actualRideType). Only when the city has a fare for the other type.
     *  2. IN-CITY OR OUTSTATION: when the admin has set an in-city radius and an in-city ride went beyond it, it is billed as outstation.
     * The booking's rider_type / trip_type are changed before the final fare is worked out, so the bill follows what was driven.
     * Anything that cannot be priced (no fare card) leaves the booking as booked and says so in the booking log.
     *
     * @param array $points the driver's route: a list of points with lat and lng
     * @param float|null $trackMaxKm the farthest the server's tracker saw the ride from the pickup, when it tracked this ride
     * @param int|null $trackOutside how many tracked points were outside the city's map, when the server tracked this ride
     */
    private function settleRadiusCrossing($booking, array $points, $dropLat, $dropLng, $finalDistance, $trackMaxKm = null, $trackOutside = null)
    {
        if (!is_numeric($booking->pickup_latitude) || !is_numeric($booking->pickup_longitude)) {
            return;
        }
        try {
            $radiusKm = Booking::incityRadiusKm();
            $maxKm = 0.0;
            $endKm = null;
            $used = [];
            foreach ($points as $point) {
                if (!is_array($point) || !isset($point['lat'], $point['lng']) || !is_numeric($point['lat']) || !is_numeric($point['lng'])) {
                    continue;
                }
                $used[] = ['lat' => (float) $point['lat'], 'lng' => (float) $point['lng']];
                $km = Booking::kmBetween($booking->pickup_latitude, $booking->pickup_longitude, $point['lat'], $point['lng']);
                $maxKm = max($maxKm, $km);
                $endKm = $km; // the last point of the route is where the ride ended, unless the final drop says otherwise
            }
            if (is_numeric($dropLat) && is_numeric($dropLng)) {
                $used[] = ['lat' => (float) $dropLat, 'lng' => (float) $dropLng];
                $endKm = Booking::kmBetween($booking->pickup_latitude, $booking->pickup_longitude, $dropLat, $dropLng);
                $maxKm = max($maxKm, $endKm);
            }
            if ($endKm === null) {
                return; // no route and no final drop: nothing to judge
            }
            // The server's own running maximum (every tracked position, not a thinned list) is the exact farthest point.
            if ($trackMaxKm !== null) {
                $maxKm = max($maxKm, (float) $trackMaxKm);
            }
            $notes = [];

            // 1. one way or round trip
            $bookedRide = (int) $booking->rider_type;
            $billedRide = Booking::actualRideType($bookedRide, $maxKm, $endKm);
            if ($billedRide !== $bookedRide) {
                $hasCard = \app\modules\admin\models\CityRateCard::find()
                    ->where(['city_id' => $booking->city_id, 'ride_type' => $billedRide, 'trip_type' => $booking->trip_type])->exists();
                if ($hasCard) {
                    $booking->rider_type = $billedRide;
                    $notes[] = 'Ride ' . ($billedRide === Booking::RIDER_TYPE_ROUNDTRIP
                        ? 'went ' . round($maxKm, 1) . ' km out and came back to the pickup point'
                        : 'ended ' . round($endKm, 1) . ' km from the pickup point and did not come back')
                        . ': booked as ' . ($bookedRide === Booking::RIDER_TYPE_ONEWAY ? 'one way' : 'a round trip')
                        . ', billed as ' . ($billedRide === Booking::RIDER_TYPE_ONEWAY ? 'one way' : 'a round trip');
                } else {
                    $billedRide = $bookedRide;
                    $notes[] = 'Ride looks like the other ride type, but no fare is set up for it in this city, so the booked type was kept (needs review)';
                }
            }

            // 2. in-city or outstation: in-city is inside the city's map. The ride is outstation if any point of it was outside the map
            // (the server's own count when it tracked the ride, else the uploaded route and the final drop), or if the admin's optional
            // in-city radius was exceeded.
            $polygon = Booking::serviceAreaPolygon($booking->city_id);
            $outsideMap = false;
            if ($trackOutside !== null) {
                $outsideMap = (int) $trackOutside > 0;
            } else {
                foreach ($used as $u) {
                    if (Booking::outsideServiceArea($u['lat'], $u['lng'], $polygon)) {
                        $outsideMap = true;
                        break;
                    }
                }
            }
            $crossed = $outsideMap || ($radiusKm > 0 && $maxKm > $radiusKm);
            $radiusConverted = false;
            if ($crossed && $booking->trip_type == Booking::TRIP_TYPE_INTERCITY) {
                $booking->trip_type = Booking::TRIP_TYPE_OUTSTATION;
                // One way: the outstation fare uses the real distance. Round trip: it uses the real hours. A round trip that goes
                // out of town and comes back ends at the pickup, so the route log is the only proof it ever left the radius.
                $outstation = $booking->rider_type == Booking::RIDER_TYPE_ROUNDTRIP
                    ? $booking->calculateOutstationRoundTripEstimate()
                    : $booking->calculateOutstationOneWayEstimate('true', $finalDistance);
                if (!is_array($outstation)) {
                    // No outstation fare for this city: keep the in-city fare and leave a note for the admin.
                    $booking->trip_type = Booking::TRIP_TYPE_INTERCITY;
                    $notes[] = 'Ride left the in-city area (farthest point ' . round($maxKm, 1)
                        . ' km from the pickup); no outstation fare is set up for this city, so the in-city fare was kept (needs review)';
                } else {
                    $radiusConverted = true;
                    $notes[] = 'Ride left the in-city area (the city map' . ($radiusKm > 0 && $maxKm > $radiusKm ? ' or the ' . round($radiusKm, 1) . ' km radius' : '')
                        . '; farthest point ' . round($maxKm, 1) . ' km from the pickup): converted to an outstation ride';
                }
            }

            Yii::$app->db->createCommand()->insert('booking_route_log', [
                'booking_id' => $booking->id,
                'driver_id' => $booking->driver_id,
                'radius_km' => $radiusKm,
                'max_km_from_pickup' => round($maxKm, 2),
                'end_km_from_pickup' => round($endKm, 2),
                'crossed' => $crossed ? 1 : 0,
                'converted' => ($radiusConverted || $billedRide !== $bookedRide) ? 1 : 0,
                'radius_converted' => $radiusConverted ? 1 : 0,
                'ride_type_from' => $bookedRide,
                'ride_type_to' => $billedRide,
                'point_count' => count($used),
                'route' => json_encode(array_slice($used, 0, 5000)),
                'created_on' => date('Y-m-d H:i:s'),
            ])->execute();

            foreach ($notes as $note) {
                $log = new BookingLogs();
                $log->booking_id = $booking->id;
                $log->description = $note;
                $log->status = BookingLogs::STATUS_ACTIVE;
                $log->save(false);
            }
        } catch (\Throwable $e) {
            Yii::error('route check failed for booking ' . $booking->id . ': ' . $e->getMessage(), 'booking');
        }
    }

    // [doc:start] actionCompleteRide - /api/driver/complete-ride - The driver ends the trip: distance and time are measured, the final fare is calculated and the money is recorded.
    /**
     * The driver ends the trip: distance and time are measured, the final fare is calculated and the money is recorded.
     *
     * POST /api/driver/complete-ride   booking_id, final_drop_latitude, final_drop_longitude, final_drop_address,
     *                                 lat_long_route_map (JSON list of {lat,lng} the phone recorded, optional)
     * Booking: Start Ride -> Ride Completed. Writes driver_earnings (one row) and one driver_wallet row:
     *   cash ride   -> debit of (commission + GST) from the driver's wallet,
     *   wallet ride -> credit.
     * The final fare can differ from the estimate because it uses the real distance and time.
     */
    public function actionCompleteRide()
    {
        $data = [];
        try {
            $post = Yii::$app->request->post();
            Yii::info($post, 'postData');
            $headers = isset(\Yii::$app->request->headers['auth_code'])
                ? \Yii::$app->request->headers['auth_code']
                : Yii::$app->request->getQueryParam('auth_code');
            // WHO IS CALLING: the app sends its login code (auth_code). It is turned into a user id here; a blocked,
            // inactive or logged-out account gets no id and is answered "User Not Found".
            $auth = new AuthSettings();
            $user_id = $auth->getAuthSession($headers);
             $driving_distance = new DrivingDistance();
            if (!empty($user_id)) {
                $driver = User::find()->where(['id'=>$user_id])->one();
                $booking_id = !empty($post['booking_id'])? $post['booking_id']: '';
                $final_drop_latitude = !empty($post['final_drop_latitude'])? $post['final_drop_latitude']: '';
                $final_drop_longitude = !empty($post['final_drop_longitude'])? $post['final_drop_longitude']: '';
                $final_drop_address = !empty($post['final_drop_address'])? $post['final_drop_address']: '';
                $lat_long_route_map = !empty($post['lat_long_route_map'])? $post['lat_long_route_map']: '';

                // The drop position is required: without it the distance cannot be measured.
                if ($final_drop_latitude === null || $final_drop_longitude === null ||$final_drop_latitude === 0 || $final_drop_longitude === 0|| $final_drop_latitude === "null" || $final_drop_longitude === "null") {
            $data['status'] = self::API_NOK;
            $data['error'] = Yii::t('app', 'Final drop latitude and longitude missing. Please close the app and retry');
            return $this->sendJsonResponse($data);
               }

                if (!empty($booking_id)) {
                    // Only the assigned driver, and only a ride that has started, can be completed.
                    $booking = $this->ownedBooking($booking_id, $user_id, [Booking::STATUS_START_RIDE]);
                    if (!empty($booking)) {
                        // 1. DISTANCE, from what actually happened. In order of trust:
                        //   a) the distance the server added up position by position while the ride ran (Booking::trackedDistance),
                        //      closed at the final drop and with the long no-signal stretches re-measured on the road;
                        //   b) the route the phone uploaded, measured along its points (no road lookup per leg, so a long route is
                        //      fast and cannot time out);
                        //   c) only if neither exists, the road distance from the pickup to the final drop.
                        $coordinates = [];
                        $trackMaxKm = null;
                        $trackOutside = null;
                        $tracked = Booking::trackedDistance($booking->id, $final_drop_latitude, $final_drop_longitude);
                        $uploadedRoute = !empty($lat_long_route_map) && $lat_long_route_map != 'null' ? json_decode($lat_long_route_map, true) : null;
                        if (!is_array($uploadedRoute)) {
                            $uploadedRoute = [];
                        }
                        if ($tracked !== null) {
                            $finalDistance = $tracked['km'];
                            $coordinates = $tracked['points'];
                            $trackMaxKm = $tracked['max_km_from_pickup'];
                            $trackOutside = $tracked['outside_points'];
                        } elseif (count($uploadedRoute) > 1) {
                            $coordinates = $uploadedRoute;
                            $finalDistance = round(Booking::routeDistanceKm($coordinates), 2);
                        } else {
                            $coordinates = [];
                            $finalDistance = $driving_distance->getDrivingDistanceGoogleMap(
                                $booking->final_pickup_latitude,
                                $booking->final_pickup_longitude,
                                $final_drop_latitude,
                                $final_drop_longitude
                            );
                            $finalDistance = $finalDistance['meters'] / 1000;
                        }
                        $data['test'] = count($coordinates);

                         Yii::info($finalDistance, 'postData');

                        // 2. SAVE THE END OF THE TRIP: drop position, distance, status, end time.
                        $booking->final_drop_latitude = $final_drop_latitude;
                        $booking->final_drop_longitude = $final_drop_longitude;
                        $booking->final_drop_address = $final_drop_address;
                        $booking->total_distance = $finalDistance;
                        $booking->status = Booking::STATUS_RIDE_COMPLETED;
                        $booking->end_time = date('Y-m-d H:i:s');

                        // 3. TIME USED: end - start, as decimal hours (this is what the hourly fare uses).
                        if (!empty($booking->start_time) && !empty($booking->end_time)) {
    $to_time = strtotime($booking->start_time);
    $from_time = strtotime($booking->end_time);
    $minutes = round(abs($to_time - $from_time) / 60, 2);
    $hours = floor($minutes / 60);
    $min = $minutes - ($hours * 60);
    $usage_hours = $hours + ($min / 60); // Convert minutes to decimal hours

    if ($usage_hours >= 1) {
        $booking->usage_hours = number_format($usage_hours, 2); // Display as decimal hours
    } else {
        $booking->usage_hours = number_format($minutes / 60, 2); // Convert and display as decimal hours
    }

}

                        // Calculate estimate based on rider_type and trip_type
                        // Calculate estimate based on rider_type and trip_type
                        // 4. FINAL FARE: the same fare formula as the estimate, run again with the real distance/time, per ride type.
                        // IN-CITY RADIUS: a ride that travelled beyond the admin's radius around the pickup point is an outstation ride.
                        // The proof is the driver's own route log (the positions the Partner app recorded during the ride) and the
                        // final drop point; the log is kept with the booking.
                        $this->settleRadiusCrossing($booking, isset($coordinates) && is_array($coordinates) ? $coordinates : [],
                            $final_drop_latitude, $final_drop_longitude, $finalDistance, $trackMaxKm, $trackOutside);

                        if ($booking->rider_type == Booking::RIDER_TYPE_ONEWAY && $booking->trip_type == Booking::TRIP_TYPE_INTERCITY) {
                            $estimate = $booking->calculateIntercityOneWayEstimate('true',$finalDistance);
                        } elseif (
                            $booking->rider_type == Booking::RIDER_TYPE_ONEWAY && $booking->trip_type == Booking::TRIP_TYPE_OUTSTATION
                        ) {
                            $estimate = $booking->calculateOutstationOneWayEstimate('true',$finalDistance);
                        } elseif (
                            $booking->rider_type ==Booking::RIDER_TYPE_ROUNDTRIP &&$booking->trip_type == Booking::TRIP_TYPE_INTERCITY
                        ) {
                            $estimate = $booking->calculateIntercityRoundTripEstimate('true');

                        } elseif ($booking->rider_type == Booking::RIDER_TYPE_ROUNDTRIP &&$booking->trip_type == Booking::TRIP_TYPE_OUTSTATION) {
                            $estimate = $booking->calculateOutstationRoundTripEstimate('true');

                        }

                      $price_detail =   json_decode($booking->price_detail);

Yii::info($price_detail, 'postData');
Yii::info($estimate, 'postData');

                        // 5. SPLIT: commission = (fare - GST - fees) x driver's % ; driver share (final_price) = fare - commission - GST - fees.
                        if ($estimate != 'false') {

   							$settings = new WebSetting();
                            $default_admin_commission_raider = $settings->getSettingBykey('default_admin_commission_raider');
                             $admin_per = !empty($driver->admin_per) ? $driver->admin_per : $default_admin_commission_raider;
                             $amount_before_gst = $estimate['estimatePrice'] - $estimate['gst'] - $estimate['securedFee'] - $estimate['platformCharges'];

                            // Calculate admin earning based on admin percentage
                            $admin_earning = $amount_before_gst * ($admin_per / 100);

                            // Round admin earning to 2 decimal places
                            $rounded_admin_earning = round($admin_earning, 2);

                       		 $booking->final_price = $estimate['estimatePrice'] - $rounded_admin_earning -  $estimate['gst'] - $estimate['securedFee'] - $estimate['platformCharges'];

                            $booking->subtotal = $estimate['estimatePrice'];
                            $booking->gst = $estimate['gst'];
                             $estimate['estimatePrice'] = $estimate['estimatePrice'] - $booking->coupon_discount;
                             $estimate['coordinates'] = isset($coordinates)?$coordinates:0;

                            //Update $price_details
                            $booking->price_detail = json_encode($estimate,true);

                        } else {

                            // Handle the case when the estimate is not available
                            // You can assign a default value or show an error message
                             $booking->final_price = $estimate['estimatePrice'];
                            $booking->subtotal = $booking->estimate_price;

                        }

                        $booking->save(false);

                        $settings = new WebSetting();
                        $default_admin_commission_raider = $settings->getSettingBykey('default_admin_commission_raider');
                        //add driver earnings
                        // // driver_requests status

                        // 6. EARNINGS ROW: one row per ride - total, commission %, admin earning (= commission + GST + fees), driver earning.
                        $driver_earnings = new DriverEarnings();
$driver_earnings->booking_id = $booking->id;
$driver_earnings->driver_id = $booking->driver_id;
$driver_earnings->total_distance_km = !empty($booking->total_distance) ? $booking->total_distance : 0;
$driver_earnings->total_amount = $estimate['estimatePrice'];

// Calculate admin commission rate
$admin_per = !empty($driver->admin_per) ? $driver->admin_per : $default_admin_commission_raider;
$driver_earnings->admin_per = $admin_per;

// Calculate amount before GST
$amount_before_gst = $driver_earnings->total_amount - $booking->gst - $estimate['securedFee'] - $estimate['platformCharges'];

// Calculate admin earning based on admin percentage
$admin_earning = $amount_before_gst * ($admin_per / 100);

// Round admin earning to 2 decimal places
$rounded_admin_earning = round($admin_earning, 2);

$driver_earnings->admin_earning = $rounded_admin_earning + $booking->gst + $estimate['securedFee'] + $estimate['platformCharges'];

// Calculate driver earning (considering admin commission and GST)
$driver_earning = $driver_earnings->total_amount - $driver_earnings->admin_earning;
$driver_earnings->driver_earning = round($driver_earning, 2);

$driver_earnings->payment_method = $booking->payment_method;
$driver_earnings->status = DriverEarnings::STATUS_APPROVED;
$driver_earnings->payment_type = DriverEarnings::payment_type_credit;
$driver_earnings->is_settled_wallet = DriverEarnings::is_settled_wallet_no;
$driver_earnings->save(false);

// Update driver_requests status
$driver_requests = DriverRequests::find()->where(['driver_id' => $booking->driver_id])->andWhere(['booking_id' => $booking->id])->one();
if (!empty($driver_requests)) {
    $driver_requests->status = $booking->status;
    $driver_requests->save(false);
}

                    // 7. WALLET ENTRY: wallet ride -> credit; cash ride -> the driver already holds the cash, so the admin share
                    // (commission + GST) is DEBITED from the driver's wallet.
                    if($driver_earnings->payment_method==Booking::payment_method_wallet){
                        $driver_wallet = new DriverWallet();
                        $driver_wallet->driver_id = $user_id;
                        $driver_wallet->amount = $driver_earnings->admin_earning ;
                        $driver_wallet->payment_type =DriverWallet::payment_type_credit;
                        $driver_wallet->method_reason ='Amount credit ' . $driver_wallet->amount.' Booking id is '.$booking->id;
                        $driver_wallet->status =DriverWallet::STATUS_SUCCESS;
                        $driver_wallet->save(false);
                    }else{
                        $driver_wallet = new DriverWallet();
                        $driver_wallet->driver_id = $user_id;
                        $driver_wallet->amount = $admin_earning + $estimate['gst'];
                        $driver_wallet->payment_type =DriverWallet::payment_type_debit;
                        $driver_wallet->method_reason ='Admin earnings debit ' . $admin_earning.' and gst is '.$estimate['gst'];
                        $driver_wallet->status =DriverWallet::STATUS_SUCCESS;
                        $driver_wallet->save(false);

                    }

                        // 8. Tell the customer, and add the history line.
                        $title = 'Ride completed';
                        $body = 'Your ride is complete. Please pay the driver and rate the ride. Booking ID #' .$booking->id;
                        $booking->id;
                        Yii::$app->push->notifyUser($booking->id,$booking->user_id,$title,$body);

                        $booking_logs = new  BookingLogs();
                        $booking_logs->booking_id = $booking->id;
                        $booking_logs->description = $booking->getStateOptionsBadges();
                        $booking_logs->status = BookingLogs::STATUS_ACTIVE;
                        $booking_logs->save(false);

                        $data['status'] = self::API_OK;
                        $data['details'] = $booking->asJson();
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = Yii::t(
                            'app',
                            'booking data not found'
                        );
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = Yii::t('app', 'booking_id required');
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t('app', 'User Not Found');
            }
        // Anything unexpected is answered as NOK (the text is cleaned of SQL/stack details by sendJsonResponse).
        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }

Yii::info("DATA-------", 'postData');
Yii::info($data, 'postData');
Yii::info("-------------------Complete Ride FUnction COmpleted-------", 'postData');
        // REPLY: every path above ends here with {status: OK|NOK, details|error}.
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionCompleteRide

    // [doc:start] actionCancelRide - /api/driver/cancel-ride - The driver gives up a ride they had accepted. The booking goes back to Waiting for Driver for other drivers.
    /**
     * The driver gives up a ride they had accepted. The booking goes back to Waiting for Driver for other drivers.
     *
     * POST /api/driver/cancel-ride   booking_id, cancel_reason
     * Only the assigned driver, and only before the ride starts. The driver is charged the Driver Cancellation
     * Charge (Web Settings) as a pending wallet debit and the customer is told.
     */
    public function actionCancelRide()
    {
        $data = [];
        $post = \Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code'])
            ? \Yii::$app->request->headers['auth_code']
            : Yii::$app->request->getQueryParam('auth_code');
        // WHO IS CALLING: the app sends its login code (auth_code). It is turned into a user id here; a blocked,
        // inactive or logged-out account gets no id and is answered "User Not Found".
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            try {
                $booking_id = !empty($post['booking_id'])? $post['booking_id']: '';
                 $cancel_reason = !empty($post['cancel_reason'])? $post['cancel_reason']: '';

                if (!empty($booking_id) && !empty($cancel_reason)) {
                   $booking = Booking::findOne($booking_id);

                    // only the driver who holds the ride may cancel it, and only before it starts
                    if (!empty($booking) && (int) $booking->driver_id !== (int) $user_id) {
                        $booking = null;
                    }
                    if (!empty($booking) && !in_array((int) $booking->status, [Booking::STATUS_DRIVER_ACCEPTED, Booking::STATUS_GO_FOR_PICKUP, Booking::STATUS_ARRIVED_LOCATION], true)) {
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'This booking can no longer be cancelled';
                        return $this->sendJsonResponse($data);

                    }

                    if (!empty($booking)) {
                   // A copy of the booking is kept (driver_cancel_duplicate) so the dashboard can show the cancelled attempt.
                   $driverCancelDuplicate = new \app\modules\admin\models\base\DriverCancelDuplicate();
$driverCancelDuplicate->attributes = $booking->attributes;
$driverCancelDuplicate->user_id = $user_id;
$driverCancelDuplicate->driver_id = null;
$driverCancelDuplicate->cancel_reason = $cancel_reason;
$driverCancelDuplicate->status = \app\modules\admin\models\base\DriverCancelDuplicate::STATUS_DRIVER_CANCELLED;
$driverCancelDuplicate->booking_id = $booking->id; // Add this line

                    if ($driverCancelDuplicate->validate() && $driverCancelDuplicate->save()) {
                        // Your existing code...
                        // Back to the pool: no driver, waiting again. The dispatch job offers it to other drivers.
                        $booking->status = Booking::STATUS_WAITING_FOR_DRIVER;
                        $booking->driver_id = null;
                        $booking->save(false);

                        $driver_requests = DriverRequests::find()
                            ->where(['booking_id' => $booking_id])
                            ->andWhere(['driver_id' => $user_id])
                            ->one();

                        if (!empty($driver_requests)) {
                            if ($driver_requests->status == DriverRequests::STATUS_DRIVER_CANCELLED) {
                                $data['status'] = self::API_NOK;
                                $data['error'] = 'Booking already cancelled';
                                return $this->sendJsonResponse($data);
                            }

                            $driver_requests->status = DriverRequests::STATUS_DRIVER_CANCELLED;
                            $driver_requests->cancel_reason = $cancel_reason;
                            $driver_requests->save(false);

                            $title = 'Booking Cancelled by driver booking id #' . $booking_id;
                            $body = 'Sorry for inconvenience. This Booking is cancelled by the driver. The booking will be assigned to nearby drivers.';
                            Yii::$app->push->notifyUser($booking_id, $booking->user_id, $title, $body);

                            $booking_logs = new BookingLogs();
                            $booking_logs->booking_id = $booking->id;
                            $booking_logs->description = $booking->getStateOptionsBadges();
                            $booking_logs->status = BookingLogs::STATUS_ACTIVE;
                            $booking_logs->save(false);
                        }

                        // PENALTY: debit the driver's wallet by the configured Driver Cancellation Charge.
                        $driverCancellationCharge = WebSetting::find()->where(['setting_key' => 'driver_cancellation_charge'])->one();
                        if (!empty($driverCancellationCharge) && $driverCancellationCharge->value != '0') {
                            $driverWallet = new DriverWallet();
                            $driverWallet->driver_id = $user_id;
                            $driverWallet->amount = (float) $driverCancellationCharge->value;
                            $driverWallet->payment_type = DriverWallet::payment_type_debit;
                            $driverWallet->method_reason = $driverCancellationCharge->value . " For booking cancel";
                            $driverWallet->status = DriverWallet::STATUS_PENDING;
                            $driverWallet->created_on = date('Y-m-d H:i:s');
                            $driverWallet->create_user_id  = $user_id;
                            $driverWallet->save(false);
                        }

                        $data['status'] = self::API_OK;
                        $data['details'] = 'Booking cancelled';
                    } else {
                        $data['status'] = self::API_NOK;
                         $data['error'] = 'Failed to save DriverCancelDuplicate record: ' . print_r($driverCancelDuplicate->errors, true);
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = 'Booking data not found';
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = 'booking_id and cancel_reason required';
            }
        // Anything unexpected is answered as NOK (the text is cleaned of SQL/stack details by sendJsonResponse).
        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }
    } else {
        $data['status'] = self::API_NOK;
        $data['error'] = 'No session found';
    }

    // REPLY: every path above ends here with {status: OK|NOK, details|error}.
    return $this->sendJsonResponse($data);
}
    // [doc:end] actionCancelRide

    // [doc:start] actionAddReview - /api/driver/add-review - The driver rates the customer after a ride (1-5 and an optional comment).
    /**
     * The driver rates the customer after a ride (1-5 and an optional comment).
     *
     * POST /api/driver/add-review   booking_id, rating, review
     * Only the driver of that booking. One rating per booking (a second call updates it). The customer's average
     * rating is recalculated and stored.
     */
    public function actionAddReview()
    {
        $data = [];
        $post = \Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code'])
            ? \Yii::$app->request->headers['auth_code']
            : Yii::$app->request->getQueryParam('auth_code');
        // WHO IS CALLING: the app sends its login code (auth_code). It is turned into a user id here; a blocked,
        // inactive or logged-out account gets no id and is answered "User Not Found".
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            try {
                $booking_id = !empty($post['booking_id'])
                    ? $post['booking_id']
                    : '';
                $rating = !empty($post['rating']) ? $post['rating'] : '';
                $review = !empty($post['review']) ? trim($post['review']) : '';

                if (!empty($booking_id) && (!is_numeric($rating) || $rating < 1 || $rating > 5 || $review === '')) {
                    // RATING IS COMPULSORY: stars (1-5) and a written review.
                    $data['status'] = self::API_NOK;
                    $data['error'] = 'Please give a rating and write a review.';
                } elseif (!empty($booking_id)) {
                    // Only the booking's own driver may rate it.
                    $booking = Booking::find()->where(['id' => $booking_id, 'driver_id' => $user_id])->one();
                    if (!empty($booking)) {
                        $DriverBookingRatingsCheck = DriverBookingRatings::find()
                            ->where(['booking_id' => $booking_id])
                            ->one();
                        if (!empty($DriverBookingRatingsCheck)) {
                            $DriverBookingRatings = DriverBookingRatings::find()
                                ->where([
                                    'id' => $DriverBookingRatingsCheck->id,
                                ])
                                ->one();
                        } else {
                            $DriverBookingRatings = new DriverBookingRatings();
                        }
                        $DriverBookingRatings->driver_id = $user_id;
                        $DriverBookingRatings->user_id = $booking->user_id;
                        $DriverBookingRatings->booking_id = $booking_id;
                        $DriverBookingRatings->rating = $rating;
                        $DriverBookingRatings->review = $review;
                        $DriverBookingRatings->status =
                            DriverBookingRatings::STATUS_ACTIVE;
                        // Save (or update) the rating.
                        if ($DriverBookingRatings->save(false)) {
                            //update average rating
                            // New average of all ratings drivers gave this customer.
                            $user_average = DriverBookingRatings::find()
                                ->where(['user_id' => $booking->user_id])
                                ->average('rating');
                            // the customer's record belongs to the customer app: it is told, this service never writes it
                            \app\modules\customerapi\services\CustomerClient::reportRating(
                                $booking->user_id,
                                $user_average,
                                (int) DriverBookingRatings::find()->where(['user_id' => $booking->user_id])->count()
                            );

                            $data['status'] = self::API_OK;
                            $data['details'] = $DriverBookingRatings->asJson();
                        } else {
                            $data['status'] = self::API_NOK;
                            $data['error'] = 'Rating added failed';
                        }
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'booking data not found';
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = 'booking_id required';
                }
            } catch (Exception $e) {
                $data['status'] = self::API_NOK;
                $data['error'] = $e->getMessage();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = 'NO session found';
        }
        // REPLY: every path above ends here with {status: OK|NOK, details|error}.
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionAddReview

    // [doc:start] actionRatingAndReviews - /api/driver/rating-and-reviews - Rating And Reviews
    public function actionRatingAndReviews()
    {
        $data = [];
        try {
            // Fetch the auth code from the request headers or query parameters
            $authCode = Yii::$app->request->headers->get(
                'auth_code',
                Yii::$app->request->getQueryParam('auth_code')
            );

            // Fetch the user ID based on the auth code
            $auth = new AuthSettings();
            $user_id = $auth->getAuthSession($authCode);

            if (!empty($user_id)) {
                $rating_one = DriverBookingRatings::find()
                    ->innerJoinWith('booking')
                    ->where(['booking.driver_id' => $user_id])
                    ->andWhere(['driver_booking_ratings.rating' => 1])
                    ->count();
                $rating_two = DriverBookingRatings::find()
                    ->innerJoinWith('booking')
                    ->where(['booking.driver_id' => $user_id])
                    ->andWhere(['driver_booking_ratings.rating' => 2])
                    ->count();
                $rating_three = DriverBookingRatings::find()
                    ->innerJoinWith('booking')
                    ->where(['booking.driver_id' => $user_id])
                    ->andWhere(['driver_booking_ratings.rating' => 3])
                    ->count();
                $rating_four = DriverBookingRatings::find()
                    ->innerJoinWith('booking')
                    ->where(['booking.driver_id' => $user_id])
                    ->andWhere(['driver_booking_ratings.rating' => 4])
                    ->count();
                $rating_five = DriverBookingRatings::find()
                    ->innerJoinWith('booking')
                    ->where(['booking.driver_id' => $user_id])
                    ->andWhere(['driver_booking_ratings.rating' => 5])
                    ->count();
                $total_rating = DriverBookingRatings::find()
                    ->innerJoinWith('booking')
                    ->where(['booking.driver_id' => $user_id])
                    ->count();

                $query = DriverBookingRatings::find()
                    ->innerJoinWith('booking')
                    ->where(['booking.driver_id' => $user_id])
                    ->orderBy(['driver_booking_ratings.created_on' => SORT_DESC]);

                $dataProvider = new ActiveDataProvider([
                    'query' => $query,
                    'pagination' => [
                        'pageSize' => 10, // Set the number of items per page
                        'page' => Yii::$app->request->get('page', 0), // Get the page number from the request, default to 1 if not provided
                    ],
                ]);

                $list = [];
                foreach ($dataProvider->getModels() as $ratings) {
                    $list[] = $ratings->asJson();
                }

                if (!empty($list)) {
                    $rating_data = [
                        'rating_one' => $rating_one,
                        'rating_two' => $rating_two,
                        'rating_three' => $rating_three,
                        'rating_four' => $rating_four,
                        'rating_five' => $rating_five,
                        'total_rating' => $total_rating,
                    ];

                    $data['status'] = self::API_OK;
                    $data['details'] = $list;
                    $data['rating'] = $rating_data;

                    $data['pagination'] = [
                        'total_pages' => $dataProvider
                            ->getPagination()
                            ->getPageCount(),
                        'current_page' =>
                            $dataProvider->getPagination()->getPage() + 1,
                        'per_page' => $dataProvider
                            ->getPagination()
                            ->getPageSize(),
                        'total_count' => $dataProvider->getTotalCount(),
                    ];
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = Yii::t(
                        'app',
                        'No ratings found for the specified user'
                    );
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t('app', 'No User found');
            }
        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionRatingAndReviews

    // [doc:start] actionViewBookingDetails - /api/driver/view-booking-details - View Booking Details
    public function actionViewBookingDetails()
    {
        $data = [];
        $post = \Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code'])
            ? \Yii::$app->request->headers['auth_code']
            : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            try {
                $booking_id = !empty($post['booking_id'])? $post['booking_id']: '';

                if (!empty($booking_id)) {
                    \app\modules\dispatch\services\BookingExpiry::run(1, ['id' => $booking_id]);
                    $booking = Booking::find()
                        ->where(['id' => $booking_id])
                        ->one();
                    if (!empty($booking)) {
                    $settings = new WebSetting();
                    $driver = User::find()->where(['id'=>$user_id])->one();
                    $default_admin_commission_raider = $settings->getSettingBykey('default_admin_commission_raider');
                    $admin_per = !empty($driver->admin_per) ? $driver->admin_per : $default_admin_commission_raider;
                            // Calculate admin and driver earnings
                    $estimatePrice = $booking->estimate_price ?? 0;
                    $gst = $booking->gst ?? 0;
                    $securedFee = $booking->secured_fee ?? 0;
                    $platformCharges = $booking->platform_charges ?? 0;

                    $adminEarnings = ($admin_per/100) * ($estimatePrice - $gst - $securedFee - $platformCharges);
                    $driverEarnings = $estimatePrice - $adminEarnings - $gst - $securedFee - $platformCharges;

                    // Add earnings to the booking data
                    $bookingData = $booking->asJson();
                    $bookingData['admin_earnings'] = number_format($adminEarnings, 2, '.', '');
                    $bookingData['driver_earnings'] = number_format($driverEarnings, 2, '.', '');

                    $data['status'] = self::API_OK;
                    $data['details'] = $bookingData;

                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'booking data not found';
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = 'booking_id required';
                }
            } catch (Exception $e) {
                $data['status'] = self::API_NOK;
                $data['error'] = $e->getMessage();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = 'NO session found';
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionViewBookingDetails

  // [doc:start] actionStartRide - /api/driver/start-ride - The driver starts the trip after the OTP has been verified. Booking: Arrived at Location -> Start Ride.
  /**
   * The driver starts the trip after the OTP has been verified. Booking: Arrived at Location -> Start Ride.
   *
   * POST /api/driver/start-ride   booking_id, vehicle_number
   * Records the start time (the fare is later based on the real duration) and the car number.
   */
  public function actionStartRide()
    {
        $data = [];
        try {
            $post = Yii::$app->request->post();
            $headers = isset(\Yii::$app->request->headers['auth_code'])
                ? \Yii::$app->request->headers['auth_code']
                : Yii::$app->request->getQueryParam('auth_code');
            // WHO IS CALLING: the app sends its login code (auth_code). It is turned into a user id here; a blocked,
            // inactive or logged-out account gets no id and is answered "User Not Found".
            $auth = new AuthSettings();
            $user_id = $auth->getAuthSession($headers);
            if (!empty($user_id)) {
                $booking_id = !empty($post['booking_id'])? $post['booking_id']: '';
                $vehicle_number = !empty($post['vehicle_number'])? $post['vehicle_number']: '';

                if (!empty($booking_id)) {
                    // Cannot start before the OTP step: only an "Arrived at Location" booking of this driver is accepted.
                    $booking = $this->ownedBooking($booking_id, $user_id, [Booking::STATUS_ARRIVED_LOCATION]);
                    if (!empty($booking)) {
                        $booking->status = Booking::STATUS_START_RIDE;
                        $booking->vehicle_number = $vehicle_number;
                        // START CLOCK: duration and fare are computed from this moment.
                        $booking->start_time = date('Y-m-d H:i:s');

                        if ($booking->save(false)) {
                            $driver_requests = DriverRequests::find()
                                ->where(['driver_id' => $user_id])
                                ->andWHere(['booking_id' => $booking_id])
                                ->one();
                            if (!empty($driver_requests)) {

                            $title = 'Ride Started';
                            $body = 'Ride started successfully. Booking ID #'.$booking->id;
                            $notification  =   Yii::$app->push->notifyUser($booking->id,$booking->driver_id,$title,$body);

                                  $title = 'Ride Started';
                            $body = 'Ride started successfully. Booking ID #'.$booking->id;
                            $notification  =   Yii::$app->push->notifyUser($booking->id,$booking->user_id,$title,$body);

                                $driver_requests->status = $booking->status;
                                $driver_requests->save(false);
                            }
                            $data['status'] = self::API_OK;
                            $data['details'] = Yii::t(
                                'app',
                                'ride go for pickup'
                            );
                        } else {
                            $data['status'] = self::API_NOK;
                            $data['error'] = Yii::t('app','ride go for pickup failed');
                        }
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = Yii::t(
                            'app',
                            'booking data not found'
                        );
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = Yii::t('app', 'booking_id required');
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t('app', 'User Not Found');
            }
        // Anything unexpected is answered as NOK (the text is cleaned of SQL/stack details by sendJsonResponse).
        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }

        // REPLY: every path above ends here with {status: OK|NOK, details|error}.
        return $this->sendJsonResponse($data);
    }
  // [doc:end] actionStartRide

// [doc:start] actionCreditRideEarnings - /api/driver/credit-ride-earnings - Credit Ride Earnings
public function actionCreditRideEarnings(){

          //add or debit  balance to wallet
          $bookings = Booking::find()->where(['status'=>Booking::STATUS_RIDE_COMPLETED])->all();
            if(!empty($bookings)){
                foreach($bookings as $booking){
                $driver_earnings = DriverEarnings::find()->where(['booking_id'=>$booking->id])->andWhere(['is_settled_wallet'=>DriverEarnings::is_settled_wallet_no])->one();
                if(!empty($driver_earnings)) {

                if($driver_earnings->payment_method==Booking::payment_method_wallet) {
                    $driver_wallet = new DriverWallet();
                    $driver_wallet->driver_id = $booking->driver_id;
                    $driver_wallet->amount = $driver_earnings->driver_earning ;
                    $driver_wallet->payment_type =DriverWallet::payment_type_credit;
                    $driver_wallet->method_reason ='Amount credit ' . $driver_wallet->amount.' Booking id is '.$booking->id;
                    $driver_wallet->status =DriverWallet::STATUS_SUCCESS;
                    $driver_wallet->save(false);
                } else {
                    $checkDriverWallet = DriverWallet::find()->where(['method_reason' => 'Amount debit ' . $driver_earnings->admin_earning.' Booking id is '.$booking->id])->one();
                    if(empty($checkDriverWallet))
                    {
                        $driver_wallet = new DriverWallet();
                        $driver_wallet->driver_id = $booking->driver_id;
                        $driver_wallet->amount = $driver_earnings->admin_earning;
                        $driver_wallet->payment_type =DriverWallet::payment_type_debit;
                        $driver_wallet->method_reason ='Amount debit ' . $driver_earnings->admin_earning.' Booking id is '.$booking->id;
                        $driver_wallet->status =DriverWallet::STATUS_SUCCESS;
                        $driver_wallet->save(false);
                    }
                }
                $driver_earnings->is_settled_wallet = DriverEarnings::is_settled_wallet_yes;
                $driver_earnings->save(false);
            }

                }
            }

}
// [doc:end] actionCreditRideEarnings

///// Automatic ride cancel after 15 min
// [doc:start] actionAutomaticRideCancel - /api/driver/automatic-ride-cancel - Automatic Ride Cancel
public function actionAutomaticRideCancel()
{
    // Retrieve bookings that are waiting for a driver and have no assigned driver
    $bookings = Booking::find()
        ->where(['status' => Booking::STATUS_WAITING_FOR_DRIVER])
        ->andWhere(['driver_id' => NULL])
        ->all();

    if (!empty($bookings)) {
        foreach ($bookings as $booking) {
            $createdOn = new DateTime($booking->pickup_date . ' ' . $booking->pickup_time);
            $userWaitingTime = BaseWebSetting::find()->where(['setting_key' => 'user_waiting_time'])->one();

            // Calculate the expected cancellation time in seconds
            $expectedCancellationTime = strtotime($booking->pickup_date . ' ' . $booking->pickup_time) + ($userWaitingTime->value * 60);

            // Get the current time in seconds
            $currentDateTime = time();

            // Compare current time with expected cancellation time
            if ($currentDateTime >= $expectedCancellationTime) {
                $booking->status = Booking::STATUS_CANCELLED;
              	$title = 'Ride Cancelled';
                $body = 'Your Ride has been cancelled automatically. booking id #'.$booking->id;
                $notification  =   Yii::$app->push->notifyUser($booking->id,$booking->user_id,$title,$body);
                if ($booking->save(false)) {
                    echo "Booking ID " . $booking->id . " has been cancelled.\n";
                } else {
                    echo "Failed to cancel booking ID " . $booking->id . "\n";
                }
            }
        }
    }
}
// [doc:end] actionAutomaticRideCancel

// [doc:start] actionClearNotifications - /api/driver/clear-notifications - Clear Notifications
public function actionClearNotifications()
{
    $data = [];
    $post = \Yii::$app->request->post();
    $headers = isset(\Yii::$app->request->headers['auth_code'])
        ? \Yii::$app->request->headers['auth_code']
        : Yii::$app->request->getQueryParam('auth_code');
    $auth = new AuthSettings();
    $user_id = $auth->getAuthSession($headers);
    if (!empty($user_id)) {
        try {
            $fcm_notification = FcmNotification::find()
                ->where(['user_id' => $user_id])
                ->all();
            if (!empty($fcm_notification)) {
                foreach ($fcm_notification as $fcm_notification_data) {
                    $fcm_notification_data->delete();
                }

                $data['status'] = self::API_OK;
                $data['details'] = 'Notification data cleared successfully';
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = 'Notification data not  found';
            }
        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }
    } else {
        $data['status'] = self::API_NOK;
        $data['error'] = 'NO session found';
    }
    return $this->sendJsonResponse($data);
}
// [doc:end] actionClearNotifications

    // [doc:start] actionEarningsDashBoard - /api/driver/earnings-dash-board - Earnings Dash Board
    public function actionEarningsDashBoard()
    {
        $data = [];
        $post = \Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            try {
                $start_date = date('Y-m-d 00:00:00');
                $end_date = date('Y-m-d 23:59:59');

                $driver_earnings_today = DriverEarnings::find()->where(['driver_id' => $user_id])->andWhere(['between', 'created_on', $start_date, $end_date])->sum('driver_earning');
                $driver_earnings_today_cash = DriverEarnings::find()->where(['driver_id' => $user_id])
                    ->andWhere(['payment_method' => DriverEarnings::payment_method_cash])
                    ->andWhere(['between', 'created_on', $start_date, $end_date])->sum('total_amount');

                // Fetch bookings that meet the criteria
                $query = Booking::find()
                    ->where(['driver_id' => $user_id])
                    ->andWhere(['status' => Booking::STATUS_RIDE_COMPLETED])
                    ->andWhere(['between', 'updated_on', $start_date, $end_date]);

                    $dataProvider = new ActiveDataProvider([
                        'query' => $query,
                        'sort' => [
                            'defaultOrder' => [
                                'created_on' => SORT_DESC,
                            ],
                        ],

                    ]);

                    $list = [];
                    foreach ($dataProvider->getModels() as $booking) {
                        $list[] = $booking->asJson();
                    }

                // Variable to store the total cancellation charges
                    $totalCancellationCharges = 0;

                                    // Iterate through the bookings and accumulate cancellation charges
                foreach ($list as $booking) {

                    $cancellationCharges = $booking['cancellation_charges'];

                    // Add the cancellation charges to the total
                    $totalCancellationCharges += $cancellationCharges;
                }

                $earnings['driver_earnings_today'] = !empty($driver_earnings_today) ? round((double) $driver_earnings_today, 2) : (double) 0;
                $earnings['driver_earnings_today_cash'] = !empty($driver_earnings_today_cash) ? round((double) $driver_earnings_today_cash + $totalCancellationCharges, 2) : (double) 0;

                $earnings['totalCancellationCharges'] = $totalCancellationCharges;
                $data['status'] = self::API_OK;
                $data['details'] = $earnings;

            } catch (Exception $e) {
                $data['status'] = self::API_NOK;
                $data['error'] = $e->getMessage();
            }

        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = 'NO session found';
        }
        return $this->sendJsonResponse($data);

    }
    // [doc:end] actionEarningsDashBoard

// [doc:start] actionEarningsByOrder - /api/driver/earnings-by-order - Earnings By Order
public function actionEarningsByOrder(){
    $data = [];
    $post = \Yii::$app->request->post();
    $headers = isset(\Yii::$app->request->headers['auth_code'])? \Yii::$app->request->headers['auth_code']: Yii::$app->request->getQueryParam('auth_code');
    $auth = new AuthSettings();
    $user_id = $auth->getAuthSession($headers);
    if (!empty($user_id)) {
        try {
            $post = Yii::$app->request->post();
            $headers = isset(\Yii::$app->request->headers['auth_code'])
                ? \Yii::$app->request->headers['auth_code']
                : Yii::$app->request->getQueryParam('auth_code');
            $auth = new AuthSettings();
            $user_id = $auth->getAuthSession($headers);
            if (!empty($user_id)) {
                $start_date = !empty($post['start_date']) ? $post['start_date'] : '';
                $end_date = !empty($post['end_date']) ? $post['end_date'] : '';

                try {
                    $model = User::find()->where(['id' => $user_id])->andWhere(['user_role' => User::ROLE_DRIVER])->one();
                    if (!empty($model)) {
                        $query = Booking::find()
                            ->innerJoinWith('driverRequests as dr')
                            ->where(['dr.driver_id' => $user_id])
                            ->andWhere(['booking.status'=>Booking::STATUS_RIDE_COMPLETED]);

                        if(!empty($start_date ) && !empty($end_date) ){
                            $query->andWhere(['booking.between', 'booking.created_on', $start_date, $end_date]);
                        }

                        $dataProvider = new ActiveDataProvider([
                            'query' => $query,

                            'sort' => [
                                'defaultOrder' => [
                                    'created_on' => SORT_DESC,
                                ],
                            ],

                            'pagination' => [
                                'pageSize' => 10, // Set the number of items per page
                                'page' => Yii::$app->request->get('page', 0),
                            ],
                        ]);

                        $list = [];
                        foreach ($dataProvider->getModels() as $booking) {
                            $list[] = $booking->asJson();
                        }

                        if (!empty($list)) {
                            $data['status'] = self::API_OK;
                            $data['details'] = $list;
                            $data['pagination'] = ['total_pages' => $dataProvider->getPagination()->getPageCount(),
                                'current_page' =>
                                    $dataProvider->getPagination()->getPage() + 1,
                                'per_page' => $dataProvider
                                    ->getPagination()
                                    ->getPageSize(),
                                'total_count' => $dataProvider->getTotalCount(),
                            ];
                        } else {
                            $data['status'] = self::API_NOK;
                            $data['error'] = Yii::t(
                                'app',
                                'No Bookings found for the specified user'
                            );
                        }
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = Yii::t('app', 'Driver data not found');
                    }
                } catch (Exception $e) {
                    $data['status'] = self::API_NOK;
                    $data['error'] = $e->getMessage();
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t('app', 'User Not Found');
            }

        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }

    } else {
        $data['status'] = self::API_NOK;
        $data['error'] = 'NO session found';
    }
    return $this->sendJsonResponse($data);

}
// [doc:end] actionEarningsByOrder

// [doc:start] actionCreateOrder - /api/driver/create-order - Kept for the apps that still call it: the same as razorpay-create-order with purpose=wallet_topup and amount=order_amount.
/**
 * Kept for the apps that still call it: the same as razorpay-create-order with purpose=wallet_topup and amount=order_amount.
 */
public function actionCreateOrder()
{
    $post = Yii::$app->request->post();
    Yii::$app->request->setBodyParams(array_merge($post, ['purpose' => \app\modules\payments\services\PaymentOrders::WALLET_TOPUP, 'amount' => $post['order_amount'] ?? 0]));

    return $this->actionRazorpayCreateOrder();
}
// [doc:end] actionCreateOrder

// [doc:start] actionCreatePhonepeOrder - /api/driver/create-phonepe-order - Create Phonepe Order
public function actionCreatePhonepeOrder(){

    $data = [];
    try {
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            $post = Yii::$app->request->post();
            $order_amount = !empty($post['order_amount'])?$post['order_amount']:'';

            $user = User::find()->where(['id'=>$user_id])->one();

            $userData['email'] = $user->email;
            $userData['customer_phone'] = $user->contact_no;
            $userData['order_amount'] = $order_amount;
            $userData['user_id'] = $user->id;

          if(!empty($userData)){
            $data['status'] = self::API_OK;
            $data['details'] = $userData ;
          }else{
            $data['status'] = self::API_NOK;
            $data['error'] = $userData ;
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
// [doc:end] actionCreatePhonepeOrder

//             // Log or output the raw JSON data for debugging

//             // Decode the base64-encoded response

//             // If you want to return the same decoded response, use it directly

//             // Return the JSON response in pretty-printed format

// [doc:start] actionHandleResponse - /api/driver/handle-response - Handle Response
public function actionHandleResponse()
{
    $data = [];
    try {
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        $user = User::find()->where(['id' => $user_id])->one();

        if (!empty($user_id)) {
            $rawData = Yii::$app->request->post();

            // Log or output the raw JSON data for debugging
            Yii::info('Raw JSON Data: ' . json_encode($rawData), 'handle-response');

            // Decode the base64-encoded response
            $decodedResponse = base64_decode($rawData['response']);

            // Log the decoded response for debugging
            Yii::info('Decoded Response: ' . $decodedResponse, 'handle-response');

            // If you want to return the same decoded response, use it directly
            $data['status'] = self::API_OK;
            $data['response'] = json_decode($decodedResponse, true);

            // Create a new PaymentLog model instance
            $paymentLog = new PaymentLog();

            // Assuming $data['response']['data'] is an array containing the nested data
            $responseData = isset($data['response']['data']) ? $data['response']['data'] : [];

            // Populate the model attributes with the data you want to store
            $paymentLog->attributes = [
                'driver_id' => $user->id,
                'merchantId' => isset($responseData['merchantId']) ? $responseData['merchantId'] : null,
                'success' => isset($data['response']['success']) ? $data['response']['success'] : null,
                'code' => isset($data['response']['code']) ? $data['response']['code'] : null,
                'message' => isset($data['response']['message']) ? $data['response']['message'] : null,
                'responseCode' => isset($responseData['responseCode']) ? $responseData['responseCode'] : null,
                'merchantTransactionId' => isset($responseData['merchantTransactionId']) ? $responseData['merchantTransactionId'] : null,
                'transactionid' => isset($responseData['transactionId']) ? $responseData['transactionId'] : null,
                'amount' => isset($responseData['amount']) ? $responseData['amount'] : null,
                'status' => isset($responseData['state']) ? $responseData['state'] : null,
                'paymentInstrument_type' => isset($responseData['paymentInstrument']['type']) ? $responseData['paymentInstrument']['type'] : null,
                'paymentInstrument_pgTransactionId' => isset($responseData['paymentInstrument']['pgTransactionId']) ? $responseData['paymentInstrument']['pgTransactionId'] : null,
                'paymentInstrument_pgServiceTransactionId' => isset($responseData['paymentInstrument']['pgServiceTransactionId']) ? $responseData['paymentInstrument']['pgServiceTransactionId'] : null,
                'paymentInstrument_bankTransactionId' => isset($responseData['paymentInstrument']['bankTransactionId']) ? $responseData['paymentInstrument']['bankTransactionId'] : null,
                'paymentInstrument_bankId' => isset($responseData['paymentInstrument']['bankId']) ? $responseData['paymentInstrument']['bankId'] : null,
            ];

            // Save the model to the database
            if ($paymentLog->save()) {
                Yii::info('Payment log saved successfully.', 'handle-response');
            } else {
                Yii::error('Failed to save payment log: ' . json_encode($paymentLog->errors), 'handle-response');
            }

            return $this->sendJsonResponse($data);
        } else {
            Yii::warning('User not found for auth code: ' . $headers, 'handle-response');
            $data = [
                'status' => self::API_NOK,
                'error' => Yii::t("app", "User not found"),
            ];
        }
    } catch (Exception $e) {
        Yii::error('Exception: ' . $e->getMessage(), 'handle-response');
        $data = [
            'status' => self::API_NOK,
            'error' => [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ],
        ];
    }

    return $this->sendJsonResponse($data);
}
// [doc:end] actionHandleResponse

// [doc:start] actionVerifyPayment1 - /api/driver/verify-payment1 - Verify Payment1
public function actionVerifyPayment1(){

    $data = [];
    try {
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            $post = Yii::$app->request->post();
            $orderId = !empty($post['orderId'])?$post['orderId']:'';
            $service_request_id = !empty($post['service_request_id'])?$post['service_request_id']:'';

            $CashFree = new CashFree();

            $CreateOrders =   $CashFree->verifyPayment($orderId);
          $verifyPayment =  json_decode($CreateOrders);
          if(!empty($verifyPayment->order_status) && $verifyPayment->order_status=='PAID') {

          }else{
            $data['status'] = self::API_NOK;
            $data['error'] = "payment failed";
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
// [doc:end] actionVerifyPayment1

// [doc:start] actionMyReferralEarings - /api/driver/my-referral-earings - My Referral Earings
public function actionMyReferralEarings(){

    $data = [];
    $post = \Yii::$app->request->post();
    $headers = isset(\Yii::$app->request->headers['auth_code'])? \Yii::$app->request->headers['auth_code']: Yii::$app->request->getQueryParam('auth_code');
    $auth = new AuthSettings();
    $user_id = $auth->getAuthSession($headers);
    if (!empty($user_id)) {
        try {
            $driver  = User::find()->where(['id'=>$user_id])->one();

            $user_referral_earnings = DriverReferralEarnings::find()->where(['driver_id'=>$user_id])->andwhere(['status'=>1])->sum('earnings');
            $user_earnings['earnings'] = !empty($user_referral_earnings)?$user_referral_earnings:0;
            $user_earnings['referral_code'] = $driver->referal_code;
            $data['status'] = self::API_OK;
            $data['details'] = $user_earnings;

        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }
    } else {
        $data['status'] = self::API_NOK;
        $data['error'] = 'NO session found';
    }
    return $this->sendJsonResponse($data);

}
// [doc:end] actionMyReferralEarings

// [doc:start] actionPlan - /api/driver/plan - Plan
public function actionPlan(){

    $data = [];
    $post = \Yii::$app->request->post();
    $headers = isset(\Yii::$app->request->headers['auth_code'])? \Yii::$app->request->headers['auth_code']: Yii::$app->request->getQueryParam('auth_code');
    $auth = new AuthSettings();
    $user_id = $auth->getAuthSession($headers);
    if (!empty($user_id)) {
        try {
        $plans = Plans::find()->where(['status'=>Plans::STATUS_ACTIVE])->one();
        if(!empty($plans)){
            $data['status'] = self::API_OK;
            $data['details'] = $plans->asJson($user_id);
        }else{
            $data['status'] = self::API_NOK;
            $data['error'] = 'plan data not  found';
        }

        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }
    } else {
        $data['status'] = self::API_NOK;
        $data['error'] = 'NO session found';
    }
    return $this->sendJsonResponse($data);
}
// [doc:end] actionPlan

// [doc:start] actionSubscribeNow - /api/driver/subscribe-now - POST /api/driver/subscribe-now razorpay_order_id, razorpay_payment_id, razorpay_signature.
/**
 * POST /api/driver/subscribe-now   razorpay_order_id, razorpay_payment_id, razorpay_signature
 * (the order comes from /api/driver/razorpay-create-order with purpose=subscription; the price is the active plan's price).
 */
public function actionSubscribeNow()
{
    $data = [];
    $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
    $user_id = (new AuthSettings())->getAuthSession($headers);
    if (empty($user_id)) {
        $data['status'] = self::API_NOK;
        $data['error'] = 'User Not Found';

        return $this->sendJsonResponse($data);
    }
    $r = $this->confirmRazorpay($user_id, \app\modules\payments\services\PaymentOrders::SUBSCRIPTION);
    if ($r['ok']) {
        $sub = DriverSubscription::find()->where(['driver_id' => $user_id])->one();
        $data['status'] = self::API_OK;
        $data['details'] = $sub ? $sub->asJson() : [];
    } else {
        $data['status'] = self::API_NOK;
        $data['error'] = $r['error'];
    }

    return $this->sendJsonResponse($data);
}
// [doc:end] actionSubscribeNow

// [doc:start] actionMyEarnings - /api/driver/my-earnings - My Earnings
public function actionMyEarnings(){

    $data = [];
    $post = Yii::$app->request->post();
    $headers = isset(\Yii::$app->request->headers['auth_code'])? \Yii::$app->request->headers['auth_code']: Yii::$app->request->getQueryParam('auth_code');
    $auth = new AuthSettings();
    $user_id = $auth->getAuthSession($headers);
    if (!empty($user_id)) {

        try {
            $start_date = !empty($post['start_date'])?$post['start_date']:'';
            $end_date = !empty($post['end_date'])?$post['end_date']:'';

            $model = User::find()->where(['id' => $user_id])->andWhere(['user_role' => User::ROLE_DRIVER])->one();
            if (!empty($model)) {
                $query = Booking::find()->where(['driver_id' => $user_id])->andWhere(['status'=>Booking::STATUS_RIDE_COMPLETED]);

                if(!empty($start_date) && !empty($end_date)){
                    $query->andWhere(['between','updated_on',$start_date,$end_date]);
                }

                $dataProvider = new ActiveDataProvider([
                    'query' => $query,
                    'sort' => [
                        'defaultOrder' => [
                            'created_on' => SORT_DESC,
                        ],
                    ],

                    'pagination' => [
                        'pageSize' => 10, // Set the number of items per page
                        'page' => Yii::$app->request->get('page', 0),
                    ],

                ]);

                $list = [];
                foreach ($dataProvider->getModels() as $booking) {
                    $list[] = $booking->asJson();
                }

                if (!empty($list)) {
                    $data['status'] = self::API_OK;
                    $data['details'] = $list;

                    $data['pagination'] = [
                        'total_pages' => $dataProvider->getPagination()->getPageCount(),
                        'current_page' =>
                            $dataProvider->getPagination()->getPage() + 1,
                        'per_page' => $dataProvider
                            ->getPagination()
                            ->getPageSize(),
                        'total_count' => $dataProvider->getTotalCount(),
                    ];
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = Yii::t(
                        'app',
                        'No Bookings found for the specified user'
                    );
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t('app', 'Driver data not found');
            }
        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }
    } else {
        $data['status'] = self::API_NOK;
        $data['error'] = Yii::t('app', 'User Not Found');
    }

    return $this->sendJsonResponse($data);

}
// [doc:end] actionMyEarnings

// [doc:start] actionMyEarningsDashBoard - /api/driver/my-earnings-dash-board - My Earnings Dash Board
public function actionMyEarningsDashBoard(){

    $data = [];
    $post = Yii::$app->request->post();
    $headers = isset(\Yii::$app->request->headers['auth_code'])? \Yii::$app->request->headers['auth_code']: Yii::$app->request->getQueryParam('auth_code');
    $auth = new AuthSettings();
    $user_id = $auth->getAuthSession($headers);
    if (!empty($user_id)) {

        try {
            $start_date = !empty($post['start_date'])?$post['start_date']:'';
            $end_date = !empty($post['end_date'])?$post['end_date']:'';
            $model = User::find()->where(['id' => $user_id])->andWhere(['user_role' => User::ROLE_DRIVER])->one();
            if (!empty($model)) {
                $earnings = number_format(DriverEarnings::find()->where(['driver_id'=>$user_id])->andWhere(['between','created_on',$start_date,$end_date])->sum('driver_earning'), 0);
                $cash_collected = DriverEarnings::find()->where(['driver_id'=>$user_id])
                ->andWhere(['payment_method'=>DriverEarnings::payment_method_cash])
                ->andWhere(['between','created_on',$start_date,$end_date])->sum('total_amount');
                $avg_rating = 0;
                $rides = Booking::find()->where(['driver_id'=>$user_id])->andWhere(['status'=>Booking::STATUS_RIDE_COMPLETED])->andWhere(['between','updated_on',$start_date,$end_date])->count();
                $km = Booking::find()->where(['driver_id'=>$user_id])->andWhere(['status'=>Booking::STATUS_RIDE_COMPLETED])->andWhere(['between','updated_on',$start_date,$end_date])->sum('total_distance');
                $hours = Booking::find()->where(['driver_id'=>$user_id])->andWhere(['status'=>Booking::STATUS_RIDE_COMPLETED])->andWhere(['between','updated_on',$start_date,$end_date])->sum('usage_hours');
                $reject_rides =DriverRequests::find()->where(['driver_id'=>$user_id])->andWHere(['status'=>DriverRequests::STATUS_DRIVER_REJECTED])->andWhere(['between','updated_on',$start_date,$end_date])->count();

                $dashboard['earnings'] = !empty($earnings)?$earnings:0;
                $dashboard['cash_collected'] = !empty($cash_collected)?intVal($cash_collected):0;
                $dashboard['avg_rating'] = !empty($avg_rating)?$avg_rating:0;
                $dashboard['rides'] = !empty($rides)?$rides:0;
                $dashboard['km'] = !empty($km)?$km:0;
                $dashboard['hours'] = !empty($hours)?$hours:0;
                $dashboard['reject_rides'] = !empty($reject_rides)?$reject_rides:0;

                    $data['status'] = self::API_OK;
                    $data['details'] = $dashboard;

            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t('app', 'Driver data not found');
            }
        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }
    } else {
        $data['status'] = self::API_NOK;
        $data['error'] = Yii::t('app', 'User Not Found');
    }

    return $this->sendJsonResponse($data);

}
// [doc:end] actionMyEarningsDashBoard

    // [doc:start] actionCollectCash - /api/driver/collect-cash - The driver confirms the money for a completed ride was received. Settles the customer's earlier unpaid.
    /**
     * The driver confirms the money for a completed ride was received. Settles the customer's earlier unpaid
     * cancellation charges against this ride and marks the booking paid.
     *
     * POST /api/driver/collect-cash   booking_id (user_id is accepted but ignored: the customer is the booking's)
     * Only the driver of a COMPLETED booking can do this.
     */
    public function actionCollectCash()
{
    $data = [];
    $post = Yii::$app->request->post();
    $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
    // WHO IS CALLING: the app sends its login code (auth_code). It is turned into a user id here; a blocked,
    // inactive or logged-out account gets no id and is answered "User Not Found".
    $auth = new AuthSettings();
    $driver_id = $auth->getAuthSession($headers);

    if (!empty($driver_id)) {
        try {
            $booking_id = $post['booking_id'];
            $user_id = $post['user_id'];
            $model = User::find()->where(['id' => $driver_id])->andWhere(['user_role' => User::ROLE_DRIVER])->one();

            if (!empty($model)) {
                // Only this driver's own booking.
                $Booking = Booking::find()->where(['driver_id' => $driver_id])
                    ->andWhere(['id' => $booking_id])
                    ->one();

                if (!empty($Booking)) {
                    // The ride must be completed first (complete-ride).
                    if ($Booking->status == Booking::STATUS_RIDE_COMPLETED) {
                        // Process cancellation charges for the user
                        // UNPAID CANCELLATION CHARGES of this customer (from earlier cancelled rides) are settled now: marked completed and
                        // debited from this driver's wallet as a settlement entry.
                        $cancelChargeModel = new CancelRideCharges();
                        $pendingCancellationCharges = $cancelChargeModel->find()
                            ->where(['user_id' => $Booking->user_id, 'status' => CancelRideCharges::STATUS_PENDING])
                            ->all();

                            $totalCancellationCharge =0;

                        foreach ($pendingCancellationCharges as $charge) {

                            // Update cancel charge status to completed
                            $totalCancellationCharge += (float)$charge->cancel_charge;
                            $charge->status = CancelRideCharges::STATUS_COMPLETED;
                          $charge->settlement_booking_id = $booking_id;
                            $charge->save(false);
                        }

                            if( $totalCancellationCharge>0){
                                $driverWallet = new DriverWallet();
                            $driverWallet->driver_id = $driver_id;
                            $driverWallet->amount = (float)$totalCancellationCharge;
                            $driverWallet->payment_type = DriverWallet::payment_type_debit;
                            $driverWallet->method_reason = $totalCancellationCharge . " debit for user's cancellation charges";
                            $driverWallet->status = DriverWallet::STATUS_SUCCESS;
                            $driverWallet->created_on = date('Y-m-d H:i:s');
                            $driverWallet->create_user_id = $driver_id;
                            $driverWallet->save(false);
                            }

                        // Update booking payment status
                        // PAID: the booking is closed for payment.
                        $Booking->payment_status = Booking::payment_status_success;
                        $Booking->save(false);

                        $data['status'] = self::API_OK;
                        $data['details'] = $Booking->asJson();
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = Yii::t('app', 'Ride not completed, try again');
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = Yii::t('app', 'Booking data not found');
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t('app', 'Driver data not found');
            }
        // Anything unexpected is answered as NOK (the text is cleaned of SQL/stack details by sendJsonResponse).
        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }
    } else {
        $data['status'] = self::API_NOK;
        $data['error'] = Yii::t('app', 'User not found');
    }

    // REPLY: every path above ends here with {status: OK|NOK, details|error}.
    return $this->sendJsonResponse($data);
}
    // [doc:end] actionCollectCash

// [doc:start] actionUpdateOdometerStart - /api/driver/update-odometer-start - Update Odometer Start
public function actionUpdateOdometerStart(){
    $data = [];
    $post = Yii::$app->request->post();
    $headers = isset(\Yii::$app->request->headers['auth_code'])? \Yii::$app->request->headers['auth_code']: Yii::$app->request->getQueryParam('auth_code');
    $auth = new AuthSettings();
    $user_id = $auth->getAuthSession($headers);
    if (!empty($user_id)) {

        try {
            $booking_id = $post['booking_id'];
            $odometer_start = $post['odometer_start'];
            $odometer_start_image = $post['odometer_start_image'];
            $booking_id = $post['booking_id'];
            $model = User::find()->where(['id' => $user_id])->andWhere(['user_role' => User::ROLE_DRIVER])->one();
            if (!empty($model)) {
                $Booking = Booking::find()->where(['driver_id' => $user_id])
                ->andWhere(['id'=>$booking_id])
                ->one();
                if(!empty($Booking)){
                        $Booking->odometer_start = $odometer_start;
                        $Booking->odometer_start_image = $odometer_start_image;
                        $Booking->save(false);
                        $data['status'] = self::API_OK;
                        $data['details'] = $Booking->asJson();

                }else{
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t('app', 'booking data not found');
                }

            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t('app', 'Driver data not found');
            }
        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }
    } else {
        $data['status'] = self::API_NOK;
        $data['error'] = Yii::t('app', 'User Not Found');
    }

    return $this->sendJsonResponse($data);

}
// [doc:end] actionUpdateOdometerStart
// [doc:start] actionUpdateOdometerEnd - /api/driver/update-odometer-end - Update Odometer End
public function actionUpdateOdometerEnd(){
    $data = [];
    $post = Yii::$app->request->post();
    $headers = isset(\Yii::$app->request->headers['auth_code'])? \Yii::$app->request->headers['auth_code']: Yii::$app->request->getQueryParam('auth_code');
    $auth = new AuthSettings();
    $user_id = $auth->getAuthSession($headers);
    if (!empty($user_id)) {

        try {
            $booking_id = $post['booking_id'];
            $odometer_end = $post['odometer_end'];
            $odometer_end_image = $post['odometer_end_image'];
            $booking_id = $post['booking_id'];
            $model = User::find()->where(['id' => $user_id])->andWhere(['user_role' => User::ROLE_DRIVER])->one();
            if (!empty($model)) {
                $Booking = Booking::find()->where(['driver_id' => $user_id])
                ->andWhere(['id'=>$booking_id])
                ->one();
                if(!empty($Booking)){
                        $Booking->odometer_start = $odometer_end;
                        $Booking->odometer_start_image = $odometer_end_image;
                        $Booking->save(false);
                        $data['status'] = self::API_OK;
                        $data['details'] = $Booking->asJson();

                }else{
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t('app', 'booking data not found');
                }

            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t('app', 'Driver data not found');
            }
        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }
    } else {
        $data['status'] = self::API_NOK;
        $data['error'] = Yii::t('app', 'User Not Found');
    }

    return $this->sendJsonResponse($data);

}
// [doc:end] actionUpdateOdometerEnd

    // [doc:start] actionAutoCancelSubscription - /api/driver/auto-cancel-subscription - Automatically cancels driver subscriptions after a certain time.
   /**
     * Automatically cancels driver subscriptions after a certain time.
     */
    public function actionAutoCancelSubscription()
    {
        $n = DriverSubscription::expireEnded();
        echo $n > 0 ? $n . " subscription(s) ended and moved to inactive.
" : "No subscriptions to cancel.
";
    }
    // [doc:end] actionAutoCancelSubscription

   // [doc:start] actionCurrentCity - /api/driver/current-city - Current City
   public function actionCurrentCity()
    {
        $data = [];
        try {
            $post = Yii::$app->request->post();
            $headers = isset(Yii::$app->request->headers['auth_code'])
                ? Yii::$app->request->headers['auth_code']
                : Yii::$app->request->getQueryParam('auth_code');

            $auth = new AuthSettings();
            $user_id = $auth->getAuthSession($headers);
            $user = User::find()->where(['id'=>$user_id])->one();

            if (!empty($user_id)) {
                $pickupLat = $post['lat'];
                $pickupLng = $post['lng'];

                // Retrieve all city boundaries from the database
                $cities = City::find()->innerJoinWith('cityRateCards')->andWHere(['city.status'=>City::STATUS_ACTIVE])
                ->andWHere(['city_rate_card.status'=>CityRateCard::STATUS_ACTIVE])
                ->all();
                $selectedCity = null;
                $selectedCityId = null;

                foreach ($cities as $city) {
                    $data['postData'] = $city->name;
                    $boundary = Json::decode($city->boundary);
                    $polygonArray = json_decode($boundary, true);
                    // Check if pickup and drop points are inside the boundary
                    if (
                        $this->isPointInsideBoundary(
                            $pickupLat,
                            $pickupLng,
                            $polygonArray
                        )
                    ) {
                        $selectedCity = $city->name;
                        $selectedCityId = $city->id;

                        break;
                    }
                }
                if (!empty($selectedCityId )) {
                    $user->city_id =  $selectedCityId;
                } else {
                    // Handle the case when the city name is not found
                    $user->city_id = null;
                }

                if ($selectedCity !== null && $selectedCityId != null && $user->save(false)) {
                    $data['status'] = self::API_OK;
                    $data['selected_city'] = $selectedCity;
                    $data['selected_city_id'] = $selectedCityId;
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] =
                        'Selected location is not inside any city boundary';
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t('app', 'No User found');
            }
        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }

        return $this->sendJsonResponse($data);
    }
   // [doc:end] actionCurrentCity

    // [doc:start] isPointInsideBoundary - Is Point Inside Boundary
    public function isPointInsideBoundary($latitude, $longitude, $polygon)
    {
        $verticesX = [];
        $verticesY = [];
        foreach ($polygon as $point) {
            $verticesX[] = $point[0];
            $verticesY[] = $point[1];
        }

        $count = count($verticesX);
        $i = $j = $c = 0;

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            if (
                $verticesY[$i] > $longitude != $verticesY[$j] > $longitude &&
                $latitude <
                    (($verticesX[$j] - $verticesX[$i]) *
                        ($longitude - $verticesY[$i])) /
                        ($verticesY[$j] - $verticesY[$i]) +
                        $verticesX[$i]
            ) {
                $c = !$c;
            }
        }

        return $c;
    }
    // [doc:end] isPointInsideBoundary

}
