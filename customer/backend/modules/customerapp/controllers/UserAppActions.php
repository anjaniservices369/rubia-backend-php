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
use app\modules\geo\services\AddressCity;
use app\modules\admin\models\Auth;
use app\modules\admin\models\WebSetting;
use app\modules\admin\models\AuthSession;
use app\modules\admin\models\BannerCategorys;
use app\modules\admin\models\Booking;
use app\modules\admin\models\RideRequest;
use app\modules\admin\models\City;
use app\modules\admin\models\Coupon;
use app\modules\admin\models\DriverBookingRatings;
use app\modules\admin\models\DriverRequests;
use app\modules\admin\models\DriverWallet;
use app\modules\admin\models\FavouriteLocation;
use app\modules\admin\models\FcmNotification;
use app\modules\admin\models\Notification;
use app\modules\admin\models\Page;
use app\modules\admin\models\LegalPageApi;
use app\modules\admin\models\Services;
use app\modules\admin\models\UserBookingRatings;
use app\modules\admin\models\UserEmergencyContacts;
use app\modules\admin\models\UserReferralEarnings;
use app\modules\admin\models\VehicleTypes;
use app\modules\admin\models\CityRateCard;
use app\modules\admin\models\base\UserWallet;
use app\modules\admin\models\base\CancelRideCharges;


use Exception;
use kartik\mpdf\Pdf;
use yii\helpers\Json;
/**
 * The user app's own endpoints under /api/user/*: login (OTP), logout, profile, emergency contacts, favourite locations,
 * referral code, notifications, banners, the list of services. Used by UserController (the customer service) and, while
 * the shared process still exists, by app/glue/UserController next to the Halting drivers booking flow.
 */
trait UserAppActions
{
    // [doc:end] behaviors

    // [doc:start] actionIndex - /api/user/index - Index
    public function actionIndex()
    {
        $data['details'] = ['hello'];
        return $this->sendJsonResponse($data);
    }

    // [doc:end] actionIndex

    // [doc:start] actionLogout - /api/user/logout - Logout
    public function actionLogout()
    {
        $data = [];
        $headers = isset(\Yii::$app->request->headers['auth_code'])
            ? \Yii::$app->request->headers['auth_code']
            : Yii::$app->request->getQueryParam('auth_code');
        //$userID = Yii::$app->request->post();
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
    // [doc:start] actionCheck - /api/user/check - Is my login still valid? The app calls this at start-up and on every screen change.
    /**
     * Is my login still valid? The app calls this at start-up and on every screen change.
     *
     * POST /api/user/check   (header auth_code)   optionally AuthSession[device_token] to refresh the push id
     * OK + the customer's profile when the session is alive; NOK "session not found" when it is not
     * (logged in elsewhere, or the account was blocked - the app then logs the customer out).
     */
    public function actionCheck()
    {
        $data = [];

        $headers = getallheaders();
        $auth_code = isset($headers['auth_code'])
            ? $headers['auth_code']
            : null;
        if ($auth_code == null) {
            // The code can also come as ?auth_code=.
            $auth_code = \Yii::$app->request->get('auth_code');
        }
        if ($auth_code) {
            // Sessions of blocked / inactive accounts are invisible to this query (ActiveUserSessionQuery), so they land in "session not found".
            $auth_session = AuthSession::find()
                ->where([
                    'auth_code' => $auth_code,
                ])
                ->one();
            if ($auth_session) {
                $user = $auth_session->createUser;
                $data['status'] = self::API_OK;
                $data['detail'] = $user->asJson();
                // The app sends its OneSignal subscription id here; it is where push notifications are delivered.
                if (isset($_POST['AuthSession'])) {
                    $auth_session->device_token =
                        $_POST['AuthSession']['device_token'];
                    if ($auth_session->save()) {
                        $data['auth_session'] = Yii::t(
                            'app',
                            'Auth Session updated'
                        );
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

    // [doc:start] actionSendOtp - /api/user/send-otp - Send Otp
    public function actionSendOtp()
    {
        return $this->sendJsonResponse(Yii::$app->otp->send(Yii::$app->request->post()));
    }

    // [doc:end] actionSendOtp

    // [doc:start] actionResendOtp - /api/user/resend-otp - Resend Otp
    public function actionResendOtp()
    {
        return $this->sendJsonResponse(Yii::$app->otp->resend(Yii::$app->request->post()));
    }

    // [doc:end] actionResendOtp

    // [doc:start] actionVerifyOtp - /api/user/verify-otp - Login / sign-up of a customer: the OTP typed by the customer is checked and, if right, the app gets its auth_code.
    /**
     * Login / sign-up of a customer: the OTP typed by the customer is checked and, if right, the app gets its auth_code.
     *
     * POST /api/user/verify-otp   contact_no, session_code (from send-otp), otp_code, device_token, device_type
     * OK: details = the customer's profile (secrets are never included), auth_code = the login code to keep.
     * A phone number logging in for the first time creates the customer account.
     */
    public function actionVerifyOtp()
    {
        $data = [];
        $post = Yii::$app->request->post();

        if (!empty($post)) {
            try {
                // 1. BLOCKED ACCOUNTS: refused before the OTP is even checked.
                $contact_no = $post['contact_no'];

                if (User::find()->where(['contact_no' => $contact_no, 'user_role' => User::ROLE_USER, 'status' => User::STATUS_BLOCKED])->exists()) {
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
                    $providerId = 'phone';

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
                        //if($isNewUser == "true"){
                        $check = User::find()->where(['contact_no' => $number])->andWhere(['user_role'=>User::ROLE_USER])->one();

                        if (empty($check)) {
                            // NEW ACCOUNT: first login of this phone number creates the customer account.
                            $model = new User();
                            $model->username = $number;
                            $model->contact_no = $number;
                            $model->device_token = ($post['device_token'] ?? '');
                            $model->device_type = ($post['device_type'] ?? 0);
                            $model->referal_code = rand(11111111, 99999999);
                            $model->user_role = User::ROLE_USER;

                            if ($model->validate()) {
                                // $model->roles = array($model->user_role);
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
            } catch (Exception $e) {
                $data['status'] = self::API_NOK;
                $data['error'] = $e->getMessage();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = Yii::t('app', 'No  Data Posted');
        }
        return $this->sendJsonResponse($data);
    }

    // [doc:end] actionVerifyOtp

    // [doc:start] actionUpdateProfile - /api/user/update-profile - Update required name/email and optional profile fields without clearing omitted values.
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
                        ->andWhere(['user_role' => User::ROLE_USER])
                        ->one();
                    if (!empty($model)) {
                        foreach (['first_name', 'last_name', 'email', 'date_of_birth', 'gender'] as $field) {
                            if (array_key_exists($field, $post) && !is_scalar($post[$field])) {
                                $data['status'] = self::API_NOK;
                                $data['error'] = 'Profile details are invalid.';
                                return $this->sendJsonResponse($data);
                            }
                        }
                        $first_name = array_key_exists('first_name', $post)
                            ? trim((string) $post['first_name'])
                            : trim((string) $model->first_name);
                        $email = array_key_exists('email', $post)
                            ? trim((string) $post['email'])
                            : trim((string) $model->email);
                        $gender = array_key_exists('gender', $post)
                            ? trim((string) $post['gender'])
                            : trim((string) $model->gender);
                        if ($first_name === '') {
                            $data['status'] = self::API_NOK;
                            $data['error'] = 'A full name is required.';
                            return $this->sendJsonResponse($data);
                        }
                        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $data['status'] = self::API_NOK;
                            $data['error'] = 'A valid e-mail address is required.';
                            return $this->sendJsonResponse($data);
                        }
                        if (!in_array($gender, ['', 'Male', 'Female', 'Other', 'Others'], true)) {
                            $data['status'] = self::API_NOK;
                            $data['error'] = 'Choose Male, Female or Other.';
                            return $this->sendJsonResponse($data);
                        }
                        if (User::find()->where(['email' => $email, 'user_role' => User::ROLE_USER])
                            ->andWhere(['<>', 'id', $model->id])->exists()) {
                            $data['status'] = self::API_NOK;
                            $data['error'] = 'Another customer already uses this e-mail address.';
                            return $this->sendJsonResponse($data);
                        }
                        $model->first_name = $first_name;
                        if (array_key_exists('last_name', $post)) {
                            $model->last_name = trim((string) $post['last_name']);
                        }
                        $model->email = $email;
                        if (array_key_exists('date_of_birth', $post)) {
                            $model->date_of_birth = trim((string) $post['date_of_birth']);
                        }
                        $model->gender = $gender;
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
                        $data['error'] = Yii::t('app', 'User Data Not Posted');
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

    // [doc:start] actionUpdateProfilePic - /api/user/update-profile-pic - Update Profile Pic
    public function actionUpdateProfilePic()
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
                        ->andWhere(['user_role' => User::ROLE_USER])
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
                        $data['error'] = Yii::t('app', 'User Data Not Posted');
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

    // [doc:end] actionUpdateProfilePic
    // [doc:start] actionUpdateLatLng - /api/user/update-lat-lng - Update Lat Lng
    public function actionUpdateLatLng()
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
                if (!empty($user)) {
                    $user->lat = $post['latitude'];
                    $user->lng = $post['longitude'];
                    // The app only sends coordinates here (no typed address), so the city comes from
                    // whichever active city's boundary contains this point -- same lookup the dashboard's
                    // "Joined in" map already uses for an address-based point.
                    $found = AddressCity::cityAt((float) $post['latitude'], (float) $post['longitude']);
                    if ($found !== null) {
                        $user->city_id = $found[0];
                        $user->city_name = $found[1];
                    }
                    if ($user->save(false)) {
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
        return $this->sendJsonResponse($data);
    } 

    // [doc:end] actionUpdateLatLng

    // [doc:start] actionAddUserEmergencyContacts - /api/user/add-user-emergency-contacts - Save two distinct valid numbers atomically; names and relationships are optional.
    public function actionAddUserEmergencyContacts()
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
                    ->where(['id' => $user_id, 'user_role' => User::ROLE_USER])
                    ->one();
                if (!empty($user)) {
                    $people = [
                        [
                            $post['person_name_one'] ?? '',
                            $post['person_phone_number_one'] ?? '',
                            $post['person_relationship_one'] ?? '',
                        ],
                        [
                            $post['person_name_two'] ?? '',
                            $post['person_phone_number_two'] ?? '',
                            $post['person_relationship_two'] ?? '',
                        ],
                    ];
                    $normalizedPeople = [];
                    $phones = [];
                    foreach ($people as $person) {
                        foreach ($person as $value) {
                            if (!is_scalar($value)) {
                                $data['status'] = self::API_NOK;
                                $data['details'] = 'Emergency contact details are invalid.';
                                return $this->sendJsonResponse($data);
                            }
                        }
                        $phone = preg_replace('/\D+/', '', (string) $person[1]);
                        if (!preg_match('/^[6-9]\d{9}$/', $phone)) {
                            $data['status'] = self::API_NOK;
                            $data['details'] = 'Enter two valid 10 digit emergency contact numbers.';
                            return $this->sendJsonResponse($data);
                        }
                        if (in_array($phone, $phones, true)) {
                            $data['status'] = self::API_NOK;
                            $data['details'] = 'Emergency contact numbers must be different.';
                            return $this->sendJsonResponse($data);
                        }
                        $phones[] = $phone;
                        $normalizedPeople[] = [
                            mb_substr(trim((string) $person[0]), 0, 100),
                            $phone,
                            mb_substr(trim((string) $person[2]), 0, 50),
                        ];
                    }

                    // Validate both contacts before replacing existing data, then save them atomically.
                    $transaction = Yii::$app->db->beginTransaction();
                    try {
                        UserEmergencyContacts::deleteAll(['user_id' => $user_id]);
                        foreach ($normalizedPeople as $person) {
                            $contact = new UserEmergencyContacts();
                            $contact->name = $person[0];
                            $contact->user_id = $user_id;
                            $contact->phone_number = $person[1];
                            $contact->relationship = $person[2];
                            $contact->status = UserEmergencyContacts::STATUS_ACTIVE;
                            if (!$contact->save(false)) {
                                throw new \RuntimeException('Emergency contact insert failed.');
                            }
                        }
                        $transaction->commit();
                    } catch (\Throwable $e) {
                        if ($transaction->isActive) {
                            $transaction->rollBack();
                        }
                        Yii::error('customer emergency contacts: ' . $e->getMessage(), 'profile');
                        $data['status'] = self::API_NOK;
                        $data['details'] = 'Could not save emergency contacts. Existing contacts were not changed.';
                        return $this->sendJsonResponse($data);
                    }

                    $data['status'] = self::API_OK;
                    $data['details'] = 'Emergency contacts updated successfully';
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

    // [doc:end] actionAddUserEmergencyContacts
    // [doc:start] actionAddEmergencyContact - /api/user/add-emergency-contact - Add Emergency Contact
    public function actionAddEmergencyContact()
    {
        try {
            $data = [];
            $list = [];
            $post = Yii::$app->request->post();
            $headers = isset(Yii::$app->request->headers['auth_code'])
                ? Yii::$app->request->headers['auth_code']
                : Yii::$app->request->getQueryParam('auth_code');
            $auth = new AuthSettings();
            $user_id = $auth->getAuthSession($headers);
            if (!empty($user_id)) {
                $model = new UserEmergencyContacts();
                $model->user_id = $user_id;
                $model->name = $post['name'] ?? '';
                $model->phone_number = $post['phone_number'];
                $model->relationship = $post['relationship'] ?? '';

                if ($model->save()) {
                    $data['status'] = self::API_OK;
                    $data['details'] = $model;
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = $model->getErrors();
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t('app', 'No user found');
            }
        } catch (\Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }

        return $this->sendJsonResponse($data);
    }

    // [doc:end] actionAddEmergencyContact

    // Update Action
    // [doc:start] actionUpdateEmergencyContact - /api/user/update-emergency-contact - Update Emergency Contact
    public function actionUpdateEmergencyContact($id)
    {
        try {
            $data = [];
            $list = [];
            $post = Yii::$app->request->post();
            $headers = isset(Yii::$app->request->headers['auth_code'])
                ? Yii::$app->request->headers['auth_code']
                : Yii::$app->request->getQueryParam('auth_code');
            $auth = new AuthSettings();
            $user_id = $auth->getAuthSession($headers);
            if (!empty($user_id)) {
                $model = UserEmergencyContacts::findOne([
                    'id' => $id,
                    'user_id' => $user_id,
                ]);
                if ($model) {
                    $model->name = !empty($post['name'])?$post['name']:'';
                    $model->phone_number = $post['phone_number'];
                    $model->relationship = $post['relationship'] ?? '';

                    if ($model->save()) {
                        $data['status'] = self::API_OK;
                        $data['details'] = $model;
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = $model->getErrors();
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = Yii::t(
                        'app',
                        'User emergency contact not found'
                    );
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t('app', 'No user found');
            }
        } catch (\Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }

        return $this->sendJsonResponse($data);
    }

    // [doc:end] actionUpdateEmergencyContact

    // List Emergency Contacts Action
    // [doc:start] actionListEmergencyContacts - /api/user/list-emergency-contacts - List Emergency Contacts
    public function actionListEmergencyContacts()
    {
        try {
            $data = [];
            $list = [];
            $post = Yii::$app->request->post();
            $headers = isset(Yii::$app->request->headers['auth_code'])
                ? Yii::$app->request->headers['auth_code']
                : Yii::$app->request->getQueryParam('auth_code');
            $auth = new AuthSettings();
            $user_id = $auth->getAuthSession($headers);
            if (!empty($user_id)) {
                $list = UserEmergencyContacts::find()
                    ->where(['user_id' => $user_id])
                    ->all();

                if (!empty($list)) {
                    $data['status'] = self::API_OK;
                    $data['details'] = $list;
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = Yii::t(
                        'app',
                        'No emergency contacts found'
                    );
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t('app', 'No user found');
            }
        } catch (\Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }

        return $this->sendJsonResponse($data);
    }

    // [doc:end] actionListEmergencyContacts

    // Delete Emergency Contact Action
    // [doc:start] actionDeleteEmergencyContact - /api/user/delete-emergency-contact - Delete Emergency Contact
    public function actionDeleteEmergencyContact($id)
    {
        try {
            $data = [];
            $list = [];
            $post = Yii::$app->request->post();
            $headers = isset(Yii::$app->request->headers['auth_code'])
                ? Yii::$app->request->headers['auth_code']
                : Yii::$app->request->getQueryParam('auth_code');
            $auth = new AuthSettings();
            $user_id = $auth->getAuthSession($headers);
            if (!empty($user_id)) {
                $model = UserEmergencyContacts::findOne([
                    'id' => $id,
                    'user_id' => $user_id,
                ]);
                if ($model) {
                    if ($model->delete()) {
                        $data['status'] = self::API_OK;
                        $data['route'] = 'delete-emergency-contact';
                        $data['message'] =
                            'Emergency contact deleted successfully';
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = Yii::t(
                            'app',
                            'Failed to delete emergency contact'
                        );
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = Yii::t(
                        'app',
                        'User emergency contact not found'
                    );
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t('app', 'No user found');
            }
        } catch (\Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }

        return $this->sendJsonResponse($data);
    }

    // [doc:end] actionDeleteEmergencyContact

    // [doc:start] actionApplyReferral - /api/user/apply-referral - Apply Referral
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
                $user = User::find()
                    ->where(['id' => $user_id])
                    ->one();
                if (!empty($user)) {
                    $referal_code = !empty($post['referal_code'])? $post['referal_code']: '';
                    $referral_user_data = User::find()->where(['referal_code' => $referal_code])->one();
                    if (!empty($referral_user_data)) {
                        $referal_id = $referral_user_data->id;
                        $user->referal_id = $referal_id;

                        if ($user->save(false)) {
                            $data['status'] = self::API_OK;
                            $data['details'] = 'Referral code apply success';
                        } else {
                            $data['status'] = self::API_NOK;
                            $data['error'] = 'Referral code apply failed';
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

    // [doc:start] actionServices - /api/user/services - Services
    public function actionServices()
    {
        $data = [];
        $post = \Yii::$app->request->post();

        $headers = isset(\Yii::$app->request->headers['auth_code'])
            ? \Yii::$app->request->headers['auth_code']
            : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            $services = Services::find()
                ->where(['status' => Services::STATUS_ACTIVE])
                ->all();
            if (!empty($services)) {
                foreach ($services as $servicesData) {
                    $list[] = $servicesData->asJson();
                }

                if (!empty($list)) {
                    $data['status'] = self::API_OK;
                    $data['details'] = $list;
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = 'Services Data Not Found';
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = 'Services Data Not Found';
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = 'User Not Found';
        }

        return $this->sendJsonResponse($data);
    }

    // [doc:end] actionServices
    // Favourite Location

    // [doc:start] actionLegalPage - /api/user/legal-page - Terms and Conditions, Privacy Policy or Refund Policy of the customer app
    /**
     * GET /api/user/legal-page?type=terms|privacy|refund   (no login: it is shown before login)
     * The text is written in the admin dashboard (Terms & Policies, app = Customer app); the newest
     * active page of that type is returned. `description` is HTML from the editor. Every other app
     * (driver partner, taxi partner) has its own endpoint and its own pages.
     */
    public function actionLegalPage()
    {
        $body = LegalPageApi::response(Page::APP_CUSTOMER, Yii::$app->request->get('type'));
        return $this->sendJsonResponse($body);
    }

    // [doc:end] actionLegalPage

    // [doc:start] actionMyTransactions - /api/user/my-transactions - Everything the customer paid, across all services, by day and by month
    /**
     * GET /api/user/my-transactions?month=YYYY-MM   (header or query auth_code; month defaults to this month)
     *
     * One list for the whole app, whatever the service: completed Driver bookings, completed Bike / Auto / Car
     * taxi rides and Wallet movements (recharges, referral earnings, cancellation charges). Wallet debits that
     * only pay for a ride (type "ride booking") are left out: the ride itself is already in the list.
     * Each source is read on its own, so a service that cannot be reached is skipped, not fatal.
     *
     * details: month, summary {total_spent, wallet_added, count, by_service[]}, days[{date, total_spent,
     * items[]}] newest first, monthly[] = the last 6 months' totals (newest first).
     */
    public function actionMyTransactions()
    {
        $authCode = Yii::$app->request->headers->get('auth_code', Yii::$app->request->getQueryParam('auth_code'));
        $user_id = (new AuthSettings())->getAuthSession($authCode);
        if (empty($user_id)) {
            return $this->sendJsonResponse(['status' => self::API_NOK, 'error' => Yii::t('app', 'User Not Found')]);
        }

        $month = (string) Yii::$app->request->get('month', date('Y-m'));
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            $month = date('Y-m');
        }
        $start = $month . '-01 00:00:00';
        $end = date('Y-m-t 23:59:59', strtotime($start));

        $labels = ['driver' => 'Driver', 'bike' => 'Bike', 'auto' => 'Auto', 'car' => 'Car', 'wallet' => 'Wallet'];
        // vehicals.vehicle_type: the service a taxi vehicle belongs to (ServiceModules: 2 car, 3 auto, 4 bike)
        $taxiService = [2 => 'car', 3 => 'auto', 4 => 'bike'];
        $db = Yii::$app->db;
        $items = [];
        $short = function ($text) {
            $text = trim((string) $text);
            return mb_strlen($text) > 60 ? mb_substr($text, 0, 57) . '...' : $text;
        };

        // 1. Driver service: completed bookings, what the customer pays is `subtotal` (final_price is the driver's share)
        try {
            $rows = $db->createCommand(
                'SELECT id, booking_number, subtotal, drop_address, updated_on FROM booking
                 WHERE user_id = :u AND status = 7 AND updated_on BETWEEN :s AND :e',
                [':u' => $user_id, ':s' => $start, ':e' => $end]
            )->queryAll();
            foreach ($rows as $r) {
                $items[] = [
                    'id' => 'driver-' . $r['id'],
                    'service' => 'driver',
                    'title' => 'Driver booking #' . ($r['booking_number'] ?: $r['id']),
                    'subtitle' => $short($r['drop_address']),
                    'amount' => round((float) $r['subtotal'], 2),
                    'direction' => 'debit',
                    'date' => $r['updated_on'],
                ];
            }
        } catch (\Throwable $e) {
            Yii::warning('my-transactions: driver bookings skipped: ' . $e->getMessage());
        }

        // 2. Taxi services (bike / auto / car): completed (9) and paid (10) rides
        try {
            $rows = $db->createCommand(
                'SELECT r.id, r.total_fare, r.drop_address, r.updated_on, v.vehicle_type FROM ride_request r
                 LEFT JOIN vehicals v ON v.id = r.vehical_type
                 WHERE r.user_id = :u AND r.status IN (9, 10) AND r.updated_on BETWEEN :s AND :e',
                [':u' => $user_id, ':s' => $start, ':e' => $end]
            )->queryAll();
            foreach ($rows as $r) {
                $service = $taxiService[(int) $r['vehicle_type']] ?? 'car';
                $items[] = [
                    'id' => $service . '-' . $r['id'],
                    'service' => $service,
                    'title' => $labels[$service] . ' ride #' . $r['id'],
                    'subtitle' => $short($r['drop_address']),
                    'amount' => round((float) $r['total_fare'], 2),
                    'direction' => 'debit',
                    'date' => $r['updated_on'],
                ];
            }
        } catch (\Throwable $e) {
            Yii::warning('my-transactions: taxi rides skipped: ' . $e->getMessage());
        }

        // 3. Wallet: recharge (1), cancellation charge (2), referral earning (3); successful entries only
        try {
            $walletTitle = [1 => 'Wallet recharge', 2 => 'Cancellation charge', 3 => 'Referral earning'];
            $rows = $db->createCommand(
                'SELECT id, amount, payment_type, type, method_reason, created_on FROM user_wallet
                 WHERE user_id = :u AND status = 1 AND type IN (1, 2, 3) AND created_on BETWEEN :s AND :e',
                [':u' => $user_id, ':s' => $start, ':e' => $end]
            )->queryAll();
            foreach ($rows as $r) {
                $items[] = [
                    'id' => 'wallet-' . $r['id'],
                    'service' => 'wallet',
                    'title' => $walletTitle[(int) $r['type']] ?? 'Wallet',
                    'subtitle' => $short($r['method_reason']),
                    'amount' => round((float) $r['amount'], 2),
                    'direction' => ((int) $r['payment_type'] === 1) ? 'credit' : 'debit',
                    'date' => $r['created_on'],
                ];
            }
        } catch (\Throwable $e) {
            Yii::warning('my-transactions: wallet skipped: ' . $e->getMessage());
        }

        usort($items, function ($a, $b) {
            return strcmp($b['date'], $a['date']);
        });

        // Group by day and total up.
        $days = [];
        $spent = 0.0;
        $added = 0.0;
        $byService = [];
        foreach ($items as $it) {
            $day = substr($it['date'], 0, 10);
            if (!isset($days[$day])) {
                $days[$day] = ['date' => $day, 'total_spent' => 0.0, 'items' => []];
            }
            $it['service_label'] = $labels[$it['service']];
            $days[$day]['items'][] = $it;
            if ($it['direction'] === 'debit') {
                $days[$day]['total_spent'] += $it['amount'];
                $spent += $it['amount'];
                $s = $it['service'];
                $byService[$s] = $byService[$s] ?? ['service' => $s, 'label' => $labels[$s], 'amount' => 0.0, 'count' => 0];
                $byService[$s]['amount'] += $it['amount'];
                $byService[$s]['count']++;
            } elseif ($it['service'] === 'wallet') {
                $added += $it['amount'];
            }
        }
        foreach ($days as &$d) {
            $d['total_spent'] = round($d['total_spent'], 2);
        }
        unset($d);
        foreach ($byService as &$b) {
            $b['amount'] = round($b['amount'], 2);
        }
        unset($b);

        // The last 6 months (this month back), what was spent in each: same sources, grouped by month.
        $monthly = [];
        for ($i = 0; $i < 6; $i++) {
            $monthly[date('Y-m', strtotime(date('Y-m-01') . " -$i month"))] = ['month' => date('Y-m', strtotime(date('Y-m-01') . " -$i month")), 'total_spent' => 0.0, 'count' => 0];
        }
        $from = date('Y-m-01 00:00:00', strtotime(date('Y-m-01') . ' -5 month'));
        $to = date('Y-m-t 23:59:59');
        $sums = [
            'SELECT DATE_FORMAT(updated_on, "%Y-%m") m, SUM(subtotal) a, COUNT(*) c FROM booking
             WHERE user_id = :u AND status = 7 AND updated_on BETWEEN :s AND :e GROUP BY m',
            'SELECT DATE_FORMAT(updated_on, "%Y-%m") m, SUM(total_fare) a, COUNT(*) c FROM ride_request
             WHERE user_id = :u AND status IN (9, 10) AND updated_on BETWEEN :s AND :e GROUP BY m',
            'SELECT DATE_FORMAT(created_on, "%Y-%m") m, SUM(amount) a, COUNT(*) c FROM user_wallet
             WHERE user_id = :u AND status = 1 AND type = 2 AND payment_type = 2 AND created_on BETWEEN :s AND :e GROUP BY m',
        ];
        foreach ($sums as $sql) {
            try {
                foreach ($db->createCommand($sql, [':u' => $user_id, ':s' => $from, ':e' => $to])->queryAll() as $r) {
                    if (isset($monthly[$r['m']])) {
                        $monthly[$r['m']]['total_spent'] += (float) $r['a'];
                        $monthly[$r['m']]['count'] += (int) $r['c'];
                    }
                }
            } catch (\Throwable $e) {
                Yii::warning('my-transactions: monthly totals skipped: ' . $e->getMessage());
            }
        }
        foreach ($monthly as &$m) {
            $m['total_spent'] = round($m['total_spent'], 2);
        }
        unset($m);

        return $this->sendJsonResponse([
            'status' => self::API_OK,
            'details' => [
                'month' => $month,
                'summary' => [
                    'total_spent' => round($spent, 2),
                    'wallet_added' => round($added, 2),
                    'count' => count($items),
                    'by_service' => array_values($byService),
                ],
                'days' => array_values($days),
                'monthly' => array_values($monthly),
            ],
        ]);
    }
    // [doc:end] actionMyTransactions

    // [doc:start] actionListFavouriteLocation - /api/user/list-favourite-location - List Favourite Location
    public function actionListFavouriteLocation()
    {
        $data = [];

        // Fetch the auth code from the request headers or query parameters
        $authCode = Yii::$app->request->headers->get(
            'auth_code',
            Yii::$app->request->getQueryParam('auth_code')
        );

        // Fetch the user ID based on the auth code
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($authCode);

        if (!empty($user_id)) {
            $favouriteLocations = FavouriteLocation::find()
                ->where(['user_id' => $user_id])
                ->andWhere(['status' => FavouriteLocation::STATUS_ACTIVE])
                ->all();

            // Transform the favourite location data using the asJson() function
            $list = [];
            foreach ($favouriteLocations as $location) {
                $list[] = $location->asJson();
            }

            if (!empty($list)) {
                $data['status'] = self::API_OK;
                $data['details'] = $list;
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t('app', 'No Favourite Locations found');
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = Yii::t('app', 'No User found');
        }

        return $this->sendJsonResponse($data);
    }

    // [doc:end] actionListFavouriteLocation

    // [doc:start] actionCreateFavouriteLocation - /api/user/create-favourite-location - Create Favourite Location
    public function actionCreateFavouriteLocation()
    {
        try {
            $data = [];
            $list = [];
            $post = Yii::$app->request->post();
            $headers = isset(Yii::$app->request->headers['auth_code'])
                ? Yii::$app->request->headers['auth_code']
                : Yii::$app->request->getQueryParam('auth_code');
            $auth = new AuthSettings();
            $user_id = $auth->getAuthSession($headers);
            if (!empty($user_id)) {
                $model = new FavouriteLocation();
                $model->user_id = $user_id;
                $model->pincode = $post['pincode'];
                $model->status = $post['status'];
                $model->update_user_id = $user_id;
                $model->create_user_id = $user_id;
                $model->latitude = $post['latitude'];
                $model->logitude = $post['longitude'];
                $model->address_type = $post['address_type'];
                $model->address = $post['address'];

                if ($model->save()) {
                    $data['status'] = self::API_OK;
                    $data['details'] = $model;
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = $model->getErrors();
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t('app', 'No user found');
            }
        } catch (\Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }

        return $this->sendJsonResponse($data);
    }

    // [doc:end] actionCreateFavouriteLocation
    // [doc:start] actionUpdateFavouriteLocation - /api/user/update-favourite-location - Update Favourite Location
    public function actionUpdateFavouriteLocation($id)
    {
        try {
            $data = [];
            $list = [];
            $post = Yii::$app->request->post();
            $headers = isset(Yii::$app->request->headers['auth_code'])
                ? Yii::$app->request->headers['auth_code']
                : Yii::$app->request->getQueryParam('auth_code');
            $auth = new AuthSettings();
            $user_id = $auth->getAuthSession($headers);
            if (!empty($user_id)) {
                $model = FavouriteLocation::findOne($id);
                if (!$model) {
                    $data['status'] = self::API_NOK;
                    $data['error'] = Yii::t(
                        'app',
                        'Favourite Location not found'
                    );
                } elseif ($model->user_id != $user_id) {
                    $data['status'] = self::API_NOK;
                    $data['error'] = Yii::t('app', 'Unauthorized');
                } else {
                    $model->pincode = $post['pincode'];
                    $model->status = $post['status'];
                    $model->update_user_id = $user_id;
                    $model->latitude = $post['latitude'];
                    $model->logitude = $post['longitude'];
                    $model->address_type = $post['address_type'];
                    $model->address = $post['address'];

                    if ($model->save()) {
                        $data['status'] = self::API_OK;
                        $data['details'] = $model;
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = $model->getErrors();
                    }
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t('app', 'No user found');
            }
        } catch (\Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }

        return $this->sendJsonResponse($data);
    }

    // [doc:end] actionUpdateFavouriteLocation

    // [doc:start] actionDeleteFavouriteLocation - /api/user/delete-favourite-location - Delete Favourite Location
    public function actionDeleteFavouriteLocation($id)
    {
        try {
            $data = [];
            $list = [];
            $headers = isset(Yii::$app->request->headers['auth_code'])
                ? Yii::$app->request->headers['auth_code']
                : Yii::$app->request->getQueryParam('auth_code');
            $auth = new AuthSettings();
            $user_id = $auth->getAuthSession($headers);
            if (!empty($user_id)) {
                $model = FavouriteLocation::findOne($id);
                if (!$model) {
                    $data['status'] = self::API_NOK;
                    $data['error'] = Yii::t(
                        'app',
                        'Favourite Location not found'
                    );
                } elseif ($model->user_id != $user_id) {
                    $data['status'] = self::API_NOK;
                    $data['error'] = Yii::t('app', 'Unauthorized');
                } else {
                    $model->delete();
                    $data['status'] = self::API_OK;
                    $data['message'] = Yii::t(
                        'app',
                        'Favourite Location deleted successfully'
                    );
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t('app', 'No user found');
            }
        } catch (\Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }

        return $this->sendJsonResponse($data);
    }

    // [doc:end] actionDeleteFavouriteLocation

    // [doc:start] actionListFcmNotifications - /api/user/list-fcm-notifications - List Fcm Notifications
    public function actionListFcmNotifications()
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
                $query = FcmNotification::find()
                    ->where(['user_id' => $user_id])
                    ->orderBy(['created_on' => SORT_DESC]);

                $dataProvider = new ActiveDataProvider([
                    'query' => $query,
                    'pagination' => [
                        'pageSize' => 10, // Set the number of items per page
                        'page' => Yii::$app->request->get('page', 0), // Get the page number from the request, default to 1 if not provided
                    ],
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
                        'per_page' => $dataProvider
                            ->getPagination()
                            ->getPageSize(),
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
        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }

        return $this->sendJsonResponse($data);
    }

    // [doc:end] actionListFcmNotifications


  
    // [doc:start] actionClearNotifications - /api/user/clear-notifications - Clear Notifications
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

    // [doc:start] actionBannerWithCategory - /api/user/banner-with-category - Banner With Category
    public function actionBannerWithCategory()
    {
        $data = [];
        $post = \Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code'])? \Yii::$app->request->headers['auth_code']: Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            try {
                $banner_category = BannerCategorys::find()->innerJoinWith(['banners as banner'])->where(['banner_categorys.status' =>BannerCategorys::STATUS_ACTIVE,
                    ])
                    ->andWhere([
                        'banner.status' => BannerCategorys::STATUS_ACTIVE,
                    ])
                    ->all();
                if (!empty($banner_category)) {
                    foreach ($banner_category as $banner_category_data) {
                        $list[] = $banner_category_data->asJson();
                    }
                    if (!empty($list)) {
                        $data['status'] = self::API_OK;
                        $data['details'] = $list;
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'banners not found';
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = 'banners not found';
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
}
