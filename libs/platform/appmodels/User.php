<?php

namespace app\models;

use app\traits\models\WithStatus;
use Yii;
use yii\base\NotSupportedException;
use yii\web\IdentityInterface;
use app\modules\admin\models\WebSetting;
use app\modules\admin\models\AuthSession;
use app\modules\admin\models\Booking;
use app\modules\admin\models\DriverAadharCard;
use app\modules\admin\models\DriverBankDetails;
use app\modules\admin\models\DriverBookingRatings;
use app\modules\admin\models\DriverDetails;
use app\modules\admin\models\DriverEmergencyContacts;
use app\modules\admin\models\DriverPanCards;
use app\modules\admin\models\DriverVehicleTypes;
use app\modules\admin\models\DriverWallet;
use app\modules\admin\models\DrivingLicense;
use app\modules\admin\models\RideEarnings;
use app\modules\admin\models\UserEmergencyContacts;


/**
 * User model
 *
 * @property integer $id
 * @property string $username
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $email
 * @property string $first_name
 * @property string $last_name
 * @property string $auth_key
 * @property integer $status
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $password write-only password
 * @property-read string fullName
 * @property-read string shortName
 */
class User extends ActiveRecord implements IdentityInterface
{
   //  public $city_id;
   // public $lat;
  //  public $lng;
  //  public $address;
   public $latitude;  // Add latitude property
    public $longitude; 
  
    public function relationNames()
    {
        return [
            'auths',
            'authSessions',
            'bookings',
            'coupons',
            'driverAadharCards',
            'driverBankDetails',
            'driverBookingRatings',
            'driverEmergencyContacts',
            'driverPanCards',
            'driverRequests', 
            'driverVehicleTypes',
            'drivingLicenses',
            'favouriteLocations',
            'fcmNotifications',
            'services',
            'userBookingRatings',
            'userEmergencyContacts',
            'userWallets',
            'vehicleTypes',
            'vehicleVerifications',
            'driverDetails'
        ];
    }

    public $confirm_password;
    public $newPassword;
    // public $city_id;

    use WithStatus;

    const STATUS_ACTIVE = 10;
    const STATUS_BLOCKED = 0;
    const STATUS_INACTIVE = 1;
    /** Account removed by an administrator. Rows are kept so rides, wallets and ratings stay consistent. */
    const STATUS_DELETED = 2;

    const police_verification_yes = 1;
    const police_verification_no = 2;
    const police_verification_rejected = 3;

    const is_paid_joining_fee_yes = 1;
    const is_paid_joining_fee_no = 2;

    const profile_verified_yes = 1;
    const profile_verified_no = 2;
    const profile_verified_rejected = 3;

    const online_status_yes = 1;
    const online_status_no = 2;

    //const ROLE_USER = 'User';

    const ROLE_ADMIN = 'admin';
    const ROLE_SUBADMIN = 'Subadmin';
    const ROLE_VENDOR = 'Vendor';
    const ROLE_DRIVER = 'Driver';
    const ROLE_MANAGER = 'Manager';
    const ROLE_USER = 'User';
    const ROLE_TAXI='taxi';
    /** @deprecated use ROLE_TAXI */
    const ROLE_SKIPPER = self::ROLE_TAXI;
    const ROLE_FRANCHISE='city franchise';

    const TYPE_PERSONAL = 'personal';
    const TYPE_BUSINESS = 'Business';

    const SIGNUP_TYPE_SOCIAL_MEDIA = 1;
    const SIGNUP_TYPE_MOBILE = 2;
    const SIGNUP_TYPE_SITE = 0;

    const ONLINE = 1;
    const OFFLINE = 2;




    const PERSONAL_DETAIL = 1;
	const VEHICAL_SELECTION = 2;
	const DOCUMENT_UPLOADS = 3;
	const BANK_DETAILS = 4;
	const EMERGENCY_CONTACT = 5;
	const TAXI_LOCATION = 6;
    /** @deprecated use TAXI_LOCATION */
    const SKIPPER_LOCATION = self::TAXI_LOCATION;

	const SELFIE_ADDED = 1;
	const DRIVING_LICENCE = 2;
	const RC_UPLOAD = 3;
	const ADHAAR_CARD = 4;
	const PAN_CARD = 5;
	const CHASSIS_NUMBER = 6;




    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{user}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            [
                'status',
                'in',
                'range' => [self::STATUS_ACTIVE, self::STATUS_BLOCKED, self::STATUS_INACTIVE, self::STATUS_DELETED],
            ],
            [
                [
                    'first_name',
                    'last_name',
                    'username',
                    'user_role',
                    'oauth_client_user_id',
                    'oauth_client',
                    'profile_image',
                    'access_token',
                    'device_token',
                ],
                'string',
            ],
            [
                ['contact_no'],
                'integer',
                'message' => 'Phone number must be a valid one.',
            ],
            ['contact_no', 'string', 'length' => 10],

            [['username'], 'required'],

            [['username'], 'unique'],

            ['user_role', 'in', 'range' => self::roles(), 'skipOnEmpty' => true],
            ['email', 'email'],
            [['email'], 'unique'],
            [['contact_no', 'device_type'], 'integer'],
            [
                [
                    'referal_id',
                    'referal_code',
                    'noline_status',
                    'signup_type',
                    'city_id',
                    'business_name',
                    'gst_number',
                    'vehicle_id'
                ],
                'safe',
            ],
         [['city_id'], 'integer'],
            [['lat', 'lng'], 'safe'],
          
            [['email', 'first_name'], 'required', 'on' => ['add-user']],
            [
                ['first_name'],
                'string',
                'message' => 'Username cannot be blank.',
            ],
            [
                'newPassword',
                'compare',
                'compareAttribute' => 'confirm_password',
                'on' => ['changepassword'],
            ],
          
           [
                ['joining_date'],'safe'
            ],
          
            [
                ['city_name'],'safe'
            ],
        ];
    }
  
    /**
     * Fields of an account that may leave the server when the model is returned in an API response
     * (e.g. `$data['details'] = $user`). Credentials and tokens are never part of it.
     */
    public function fields()
    {
        $fields = parent::fields();
        foreach (['password_hash', 'auth_key', 'password_reset_token', 'access_token'] as $secret) {
            unset($fields[$secret]);
        }

        return $fields;
    }
   public function attributeLabels()
    {
        return [
          'lat' => 'Latitude',
            'lng' => 'Longitude',
            'city_id' => 'City ID',
            'address' => 'Address',
          ];
    }
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['changepassword'] = [
            'newPassword',
            'confirm_password',
            'password',
        ];
        $scenarios['add-user'] = [
            'email',
            'username',
            'first_name',
            'password',
            'referal_id',
            'referal_code',
            'access_token',
        ];
        $scenarios['update-profile'] = [
            //'email',
            'first_name',
            'contact_no',
            'profile_image',
            'email',
        ];

        $scenarios['facebook-login'] = [
            'email',
            'username',
            'oauth_client_user_id',
            'first_name',
            'oauth_client',
            'profile_image',
            'user_role',
            'status',
            'signup_type',
            //'access_token'
        ];
        $scenarios['phone-login'] = [
            'contact_no',
            'device_token',
            'device_type',
            'oauth_client',
            'oauth_client_user_id',
        ];
        $scenarios['rest-user'] = ['email', 'contact_no'];
      
       $scenarios['update'] = ['first_name', 'last_name', 'email', 'city_id', 'lat', 'lng', /* other attributes */];

        $scenarios['update-latlong'] = [
            'latitude',
            // 'username',
            'longitude',
        ];

        return $scenarios;
    }
    /**
     * User full name
     * (as first/last name)
     *
     * @return string
     */
    public function getFullName()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * User short name
     * (as first name, last name first letter)
     *
     * @return string
     */
    public function getShortName()
    {
        return trim(
            $this->first_name .
                ' ' .
                ($this->last_name ? substr($this->last_name, 0, 1) . '.' : '')
        );
    }

    public function getChangeStatus()
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'In active',
        ];
    }

    /**
     * List of user status aliases
     *
     * @return array
     */
    public static function getStatusesList()
    {
        return [
            static::STATUS_ACTIVE => 'Active',
            static::STATUS_BLOCKED => 'Blocked',
        ];
    }




    public function stateBadges()
    {
        $states = $this->getStatusesList();
        if ($this->status == self::STATUS_ACTIVE) {
            return '<span class="badge badge-success">' .
                $states[self::STATUS_ACTIVE] .
                '</span>';
        } elseif ($this->status == self::STATUS_BLOCKED) {
            return '<span class="badge badge-default">' .
                $states[self::STATUS_BLOCKED] .
                '</span>';
        } /*else if ($this->status == self::STATE_DELETED) {
				return '<span class="badge badge-danger">' . $states[self::STATE_DELETED] . '</span>';
			}*/
    }

    public function onlineBadges()
    {
        $states = $this->getStatusesList();
        if ($this->online_status == self::online_status_yes) {
            return '<span class="badge badge-success">Online</span>';
        } elseif ($this->online_status == self::online_status_no) {
            return '<span class="badge badge-default">Offline</span>';
        }
    }
    public function profileVerificationStatus()
    {
        $statuses = [
            self::profile_verified_yes => 'Verified',
            self::profile_verified_yes => 'Pending',
            self::profile_verified_rejected => 'Rejected',
        ];

        return $statuses;
    }

    public function profileVerificationBadges()
    {
        if ($this->profile_verified == self::profile_verified_yes) {
            return '<span class="badge badge-success">Verified</span>';
        } elseif ($this->profile_verified == self::profile_verified_yes) {
            return '<span class="badge badge-default">Pending</span>';
        } elseif ($this->profile_verified == self::profile_verified_rejected) {
            return '<span class="badge badge-danger">Rejected</span>';
        }
    }
    public function policeVerificationStatus()
    {
        $statuses = [
            self::profile_verified_yes => 'Verified',
            self::profile_verified_yes => 'Pending',
            self::profile_verified_rejected => 'Rejected',
        ];

        return $statuses;
    }

    public function policeVerificationBadges()
    {
        if ($this->is_done_police_verification == self::profile_verified_yes) {
            return '<span class="badge badge-success">Verified</span>';
        } elseif (
            $this->is_done_police_verification == self::profile_verified_no
        ) {
            return '<span class="badge badge-default">Pending</span>';
        } elseif (
            $this->is_done_police_verification ==
            self::police_verification_rejected
        ) {
            return '<span class="badge badge-danger">Rejected</span>';
        }
    }

    public static function getOnlineStatus()
    {
        return [
            static::ONLINE => 'online',
            static::OFFLINE => 'offline',
        ];
    }
    public function getOnlineStatusBadges()
    {
        if ($this->online_status == self::ONLINE) {
            return '<span class="badge badge-success">Online</span>';
        } elseif ($this->online_status == self::OFFLINE) {
            return '<span class="badge badge-default">Offline</span>';
        } else{
            return '<span class="badge badge-default">Offline</span>';

        }
    }
    public static function getBusinessType()
    {
        return [
            static::TYPE_PERSONAL => 'personal',
            static::TYPE_BUSINESS => 'Business',
        ];
    }


    public static function isVendor()
    {
        if (empty(\Yii::$app->user->identity)) {
            return false;
        }
        return \Yii::$app->user->identity->user_role == self::ROLE_VENDOR;
    }

    public static function getRoles()
    {
        return [
            self::ROLE_ADMIN => 'admin',
            //self::ROLE_SUBADMIN => 'subadmin',
            self::ROLE_VENDOR => 'vendor',
            self::ROLE_DRIVER => 'Driver',
            // self:: ROLE_MANAGER => 'manager',
            // self::ROLE_PRIEST => 'Priest',
            self::ROLE_USER => 'User',
            self::ROLE_FRANCHISE => 'City Franchise',
        ];
    }
    /**
     * @return array
     */
    // public static function getRolesList()
    // {
    // 	$roles = array_keys(Yii::$app->authManager->getRoles());

    // 	return array_combine($roles, $roles);
    // }

    /**
     * Assign a role to user 
     *
     * @param string $role
     *
     * @return bool
     */
    public function assignRole($role)
    {
        if (!Yii::$app->authManager->checkAccess($this->id, $role)) {
            $authRole = Yii::$app->authManager->getRole($role);
            Yii::$app->authManager->assign($authRole, $this->id);

            return true;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        if (!is_string($token) || $token === '') {
            return null;
        }

        return static::findOne(['access_token' => $token, 'status' => self::STATUS_ACTIVE]);
    }
    /**
     * Finds user by username
     *
     * @param string $username
     *
     * @return static|null
     */
    public static function findByUsername($username)
    {
        //return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
        /*return static::findOne(['OR',
         [ 'username' => $username],['contact_no'=>$username]]);*/

        $user = User::find()
            ->where(['username' => $username])
            ->andWhere(['status' => self::STATUS_ACTIVE])
            ->one();

        return $user;
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     *
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
            'password_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     *
     * @return bool
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }
        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = '3600';

        return $timestamp + $expire >= time();
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     *
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        /*if(Yii::$app->security->validatePassword($password, $this->password_hash)){
			return true;
		}else{ return false;}*/
        if (empty($this->password_hash) || !is_string($password) || $password === '') {
            return false;
        }

        return Yii::$app->security->validatePassword(
            $password,
            $this->password_hash
        );
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash(
            $password
        );
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token =
            Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }
    //Check Auth

    function GenerateRandString1(
        $len,
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
    ) {
        $string = '';
        for ($i = 0; $i < $len; $i++) {
            $pos = rand(0, strlen($chars) - 1);
            $string .= $chars[$pos];
        }
        return $string;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuths()
    {
        return $this->hasMany(\app\modules\admin\models\Auth::className(), [
            'user_id' => 'id',
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuthSessions()
    {
        return $this->hasMany(
            \app\modules\admin\models\AuthSession::className(),
            ['create_user_id' => 'id']
        );
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBookings()
    {
        return $this->hasMany(\app\modules\admin\models\Booking::className(), [
            'driver_id' => 'id',
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCoupons()
    {
        return $this->hasMany(\app\modules\admin\models\Coupon::className(), [
            'update_user_id' => 'id',
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDriverAadharCards()
    {
        return $this->hasMany(
            \app\modules\admin\models\DriverAadharCard::className(),
            ['update_user_id' => 'id']
        );
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDriverBankDetails()
    {
        return $this->hasMany(
            \app\modules\admin\models\DriverBankDetails::className(),
            ['create_user_id' => 'id']
        );
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDriverBookingRatings()
    {
        return $this->hasMany(
            \app\modules\admin\models\DriverBookingRatings::className(),
            ['user_id' => 'id']
        );
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDriverEmergencyContacts()
    {
        return $this->hasMany(
            \app\modules\admin\models\DriverEmergencyContacts::className(),
            ['create_user_id' => 'id']
        );
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDriverPanCards()
    {
        return $this->hasMany(
            \app\modules\admin\models\DriverPanCards::className(),
            ['update_user_id' => 'id']
        );
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDriverRequests()
    {
        return $this->hasMany(
            \app\modules\admin\models\DriverRequests::className(),
            ['update_user_id' => 'id']
        );
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDriverVehicleTypes()
    {
        return $this->hasMany(
            \app\modules\admin\models\DriverVehicleTypes::className(),
            ['driver_id' => 'id']
        );
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDrivingLicenses()
    {
        return $this->hasMany(
            \app\modules\admin\models\DrivingLicense::className(),
            ['update_user_id' => 'id']
        );
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFavouriteLocations()
    {
        return $this->hasMany(
            \app\modules\admin\models\FavouriteLocation::className(),
            ['user_id' => 'id']
        );
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFcmNotifications()
    {
        return $this->hasMany(
            \app\modules\admin\models\FcmNotification::className(),
            ['user_id' => 'id']
        );
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getServices()
    {
        return $this->hasMany(\app\modules\admin\models\Services::className(), [
            'update_user_id' => 'id',
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserBookingRatings()
    {
        return $this->hasMany(
            \app\modules\admin\models\UserBookingRatings::className(),
            ['driver_id' => 'id']
        );
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserEmergencyContacts()
    {
       return $this->hasMany(
           \app\modules\admin\models\UserEmergencyContacts::className(),
          ['update_user_id' => 'id']
        // ['user_id' => 'id']
      
      
        );
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserWallets()
    {
        return $this->hasMany(
            \app\modules\admin\models\UserWallet::className(),
            ['update_user_id' => 'id']
        );
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVehicleTypes()
    {
        return $this->hasMany(
            \app\modules\admin\models\VehicleTypes::className(),
            ['update_user_id' => 'id']
        );
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVehicleVerifications()
    {
        return $this->hasMany(
            \app\modules\admin\models\VehicleVerification::className(),
            ['update_user_id' => 'id']
        );
    }

    /**
     * @return \yii\db\ActiveQuery
     */

    public function profileImage($profile_image, $user_name)
    {
        $image = str_replace('data:image/png;base64,', '', $profile_image);
        // $ext = explode(';',explode('data:image/',$_POST['image'])[1])[0];
        if (!empty($image)) {
            $ext = 'png';
            $image = str_replace(' ', '+', $image);

            // Decode the Base64 encoded Image
            $data1 = base64_decode($image);
            // Create Image path with Image name and Extension

            //	$image_name = \Yii::$app->urlManager->createAbsoluteUrl('uploads').'/'.$user_name.'_'.mt_rand().'.'.$ext;
            $image_name = $user_name . '_' . mt_rand() . '.' . $ext;
            $file = 'uploads/' . $image_name;
            // Save Image in the Image Directory
            $success = file_put_contents($file, $data1);
            if ($success === false) {
                $data['profile_image'] = 'Not saved';
            } else {
                return \Yii::$app->urlManager->createAbsoluteUrl('uploads') .
                    '/' .
                    $image_name;
            }
        }
    }
    public function UserNotification($user_id, $title, $body, $type, $api_key)
    {
        $auth_sess = new \app\modules\admin\models\AuthSession();
        $device_token = $auth_sess->getDeviceToken($user_id);

        // $api_key is unused: pushes go through the configured push driver (see app\modules\messaging\services\PushNotifier).
        return \app\modules\messaging\services\PushNotifier::send($device_token, $title, $body, ['type' => $type], \app\modules\messaging\services\PushNotifier::appFor($user_id));
    }

    /**
     * Every role the system knows. Used to validate user_role.
     *
     * @return string[]
     */
    public static function roles()
    {
        return [
            self::ROLE_ADMIN, self::ROLE_SUBADMIN, self::ROLE_MANAGER, self::ROLE_FRANCHISE,
            self::ROLE_VENDOR, self::ROLE_DRIVER, self::ROLE_TAXI, self::ROLE_USER,
        ];
    }

    /**
     * Roles $actor may give to another account. Nobody can hand out a role as powerful as their own,
     * except an administrator.
     *
     * @return string[]
     */
    public static function assignableRoles(self $actor)
    {
        switch ($actor->user_role) {
            case self::ROLE_ADMIN:
                return self::roles();
            case self::ROLE_SUBADMIN:
                return [self::ROLE_MANAGER, self::ROLE_FRANCHISE, self::ROLE_VENDOR, self::ROLE_DRIVER, self::ROLE_TAXI, self::ROLE_USER];
            case self::ROLE_MANAGER:
                return [self::ROLE_DRIVER, self::ROLE_TAXI, self::ROLE_USER];
            default:
                return [];
        }
    }

    /**
     * Whether $actor may view or change the account $target (profile, status, password, documents).
     *  - everybody manages their own account;
     *  - admin: everyone;
     *  - sub-admin: everyone except admins and other sub-admins;
     *  - manager: drivers, taxi drivers and riders only;
     *  - city franchise: drivers, taxi drivers and riders of its own city only.
     */
    public static function canManage(self $actor, self $target)
    {
        if ((int) $actor->id === (int) $target->id) {
            return true;
        }

        switch ($actor->user_role) {
            case self::ROLE_ADMIN:
                return true;
            case self::ROLE_SUBADMIN:
                return !in_array($target->user_role, [self::ROLE_ADMIN, self::ROLE_SUBADMIN], true);
            case self::ROLE_MANAGER:
                return in_array($target->user_role, [self::ROLE_DRIVER, self::ROLE_TAXI, self::ROLE_USER], true);
            case self::ROLE_FRANCHISE:
                return !empty($actor->city_id)
                    && (int) $actor->city_id === (int) $target->city_id
                    && in_array($target->user_role, [self::ROLE_DRIVER, self::ROLE_TAXI, self::ROLE_USER], true);
            default:
                return false;
        }
    }
    public static function isAdmin()
    {
        if (empty(\Yii::$app->user->identity)) {
            return false;
        }
        return \Yii::$app->user->identity->user_role == self::ROLE_ADMIN;
    }
    
    public static function isFranchise()
    {
        if (empty(\Yii::$app->user->identity)) {
            return false;
        }
        return \Yii::$app->user->identity->user_role == self::ROLE_FRANCHISE;
    }

    public static function isSubAdmin()
    {
        if (empty(\Yii::$app->user->identity)) {
            return false;
        }
        return \Yii::$app->user->identity->user_role == self::ROLE_SUBADMIN;
    }

    public static function isManager()
    {
        if (empty(\Yii::$app->user->identity)) {
            return false;
        }
        return \Yii::$app->user->identity->user_role == self::ROLE_MANAGER;
    }


    public static function isUser()
    {
        if (empty(\Yii::$app->user->identity)) {
            return false;
        }
        return \Yii::$app->user->identity->user_role == self::ROLE_USER;
    }

    public static function isDriver()
    {
        if (empty(\Yii::$app->user->identity)) {
            return false;
        }
        return \Yii::$app->user->identity->user_role == self::ROLE_DRIVER;
    }

    public static function getUserAddress($id)
    {
        if (!empty($id)) {
        }
    }
    function getReferredUserscount($id)
    {
        $referreduserscount = User::find()
            ->where([
                'referal_id' => $id,
            ])
            ->count();
        return $referreduserscount;
    }

    public function asJson()
    {
       
        $data = [];
        $data['id'] = $this->id;

        $data['username'] = $this->username;

        $data['first_name'] = $this->first_name;

        $data['last_name'] = $this->last_name;

        $data['gender'] = $this->gender;

        $data['email'] = $this->email;
        $data['contact_no'] = $this->contact_no;
        $data['address'] = $this->address;
        $data['date_of_birth'] = $this->date_of_birth;
        $data['selfie'] = $this->profile_image;
        $data['user_role'] = $this->user_role;
        $data['referal_code'] = $this->referal_code;
      
      $data['city_id'] = $this->city_id;
        $data['lat'] = $this->lat;
        $data['lng'] = $this->lng;

        $data['created_at'] = date('d-m-Y', $this->created_at);
        
        if ($this->user_role == self::ROLE_USER) {
            $data['personal_details'] = trim((string) $this->first_name) !== ''
                && filter_var(trim((string) $this->email), FILTER_VALIDATE_EMAIL) !== false;
            $user_emergency_contact_phones = UserEmergencyContacts::find()
                ->select('phone_number')
                ->where(['user_id' => $this->id])
                ->andWhere(['status' => UserEmergencyContacts::STATUS_ACTIVE])
                ->column();
            $user_emergency_contact_phones = array_filter(array_map(static function ($phone) {
                $phone = preg_replace('/\D+/', '', (string) $phone);
                return preg_match('/^[6-9]\d{9}$/', $phone) ? $phone : null;
            }, $user_emergency_contact_phones));
            $data['user_emergency_contacts'] = count(array_unique($user_emergency_contact_phones)) >= 2;
        }
    // var_dump(User::isDriver());exit;
        if ($this->user_role == self::ROLE_DRIVER) {



            $settings = new WebSetting();
            $driver_min_wallet_bal = $settings->getSettingBykey('driver_min_wallet_bal');
            $data['driver_min_wallet_bal'] = $driver_min_wallet_bal;
            $data['driver_wallet_bal'] = DriverWallet::availableBalance($this->id);
            if( $data['driver_min_wallet_bal']<=$data['driver_wallet_bal']){
                $data['booking_eligibility'] = true;
            }else{
                $data['booking_eligibility'] = false;

            }

            if (!empty($this->online_status)) {
                $data['online_status'] = $this->online_status;
            } else {
                $data['online_status'] = User::online_status_no;
            }

            if (!empty($this->first_name)) {
                $data['personal_details'] = true;
            } else {
                $data['personal_details'] = false;
            }

            // The joining fee is the amount set in the dashboard (Payments screen); it can change at any time and 0 means no fee.
            // With no fee there is nothing to pay, so the step counts as done and no driver is stopped at the payment screen.
            $joiningFee = \app\modules\payments\services\PaymentOrders::joiningFee();
            $data['joining_fee_amount'] = $joiningFee;
            if ($joiningFee <= 0) {
                $data['is_paid_joining_fee'] = true;
            } elseif (!empty($this->is_paid_joining_fee)) {
                if (
                    $this->is_paid_joining_fee == User::is_paid_joining_fee_yes
                ) {
                    $data['is_paid_joining_fee'] = true;
                } else {
                    $data['is_paid_joining_fee'] = false;
                }
            } else {
                $data['is_paid_joining_fee'] = false;
            }

            if (!empty($this->profile_verified)) {
                $data['profile_verified'] = $this->profile_verified;
            } else {
                $data['profile_verified'] = User::profile_verified_no;
            }
          if ($this->status == self::STATUS_ACTIVE) {
                $data['status'] = User::STATUS_ACTIVE;
            }elseif ($this->status == self::STATUS_BLOCKED){
                $data['status'] = User::STATUS_BLOCKED;
            }
            $data['rejection_reason'] = $this->rejection_reason;

            $driver_emergency_contacts = DriverEmergencyContacts::find()
                ->where(['driver_id' => $this->id])
                ->one();
            if (!empty($driver_emergency_contacts)) {
                $data['driver_emergency_contacts'] = true;
            } else {
                $data['driver_emergency_contacts'] = false;
            }

            $driver_emergency_contacts = DriverEmergencyContacts::find()
                ->where(['driver_id' => $this->id])
                ->one();
            if (!empty($driver_emergency_contacts)) {
                $data['driver_emergency_contacts'] = true;
            } else {
                $data['driver_emergency_contacts'] = false;
            }

            $driver_vehicle_types = DriverVehicleTypes::find()
                ->where(['driver_id' => $this->id])
                ->one();
            if (!empty($driver_vehicle_types)) {
                $data['driver_vehicle_types'] = true;
            } else {
                $data['driver_vehicle_types'] = false;
            }

            $driver_bank_details = DriverBankDetails::find()
                ->where(['driver_id' => $this->id])
                ->one();
            if (!empty($driver_bank_details)) {
                $data['driver_bank_details'] = true;
            } else {
                $data['driver_bank_details'] = false;
            }

            $driver_pan_cards = DriverPanCards::find()
                ->where(['driver_id' => $this->id])
                ->one();
            if (!empty($driver_pan_cards)) {
                $data['driver_pan_cards'] = true;
            } else {
                $data['driver_pan_cards'] = false;
            }

            $driver_aadhar_card = DriverAadharCard::find()
                ->where(['driver_id' => $this->id])
                ->one();
            if (!empty($driver_aadhar_card)) {
                $data['driver_aadhar_card'] = true;
            } else {
                $data['driver_aadhar_card'] = false;
            }

            $driving_license = DrivingLicense::find()
                ->where(['driver_id' => $this->id])
                ->one();
            if (!empty($driving_license)) {
                $data['driving_license'] = true;
            } else {
                $data['driving_license'] = false;
            }
            if (!empty($this->is_done_police_verification)) {
                if (
                    $this->is_done_police_verification ==
                    User::police_verification_yes
                ) {
                    $data['police_verification'] = true;
                } else {
                    $data['police_verification'] = false;
                }
            } else {
                $data['police_verification'] = false;
            }

            if (!empty($this->profile_image)) {
                $data['profile_image'] = true;
            } else {
                $data['profile_image'] = false;
            }

            if (
                $data['profile_image'] == true &&
                $data['police_verification'] == true &&
                $data['driving_license'] == true &&
                $data['driver_aadhar_card'] == true &&
                $data['driver_pan_cards']
            ) {
                $data['document_verification'] = true;
            } else {
                $data['document_verification'] = false;
            }

            if (
                $data['personal_details'] == true &&
                $data['driver_emergency_contacts'] == true &&
                $data['driver_vehicle_types'] == true &&
                $data['driver_bank_details'] == true &&
                $data['document_verification'] == true
            ) {
                $data['all_submit'] = true;
            } else {
                $data['all_submit'] = false;
            }

            $driver_booking_ratings = DriverBookingRatings::find()->where(['driver_id'=>$this->id])->count();
            $driver_booking_ratings_average = DriverBookingRatings::find()->where(['driver_id'=>$this->id])->average('rating');
            $booking = Booking::find()->where(['driver_id'=>$this->id])->count();

            

            $data['rating_count']= $driver_booking_ratings;
            $data['average_rating']= $driver_booking_ratings_average;
            $data['rides_count']= $booking;






        }

        return $data;
    }


    
	public function asTaxiJson()
	{
		$data = [];
		$data['id'] = $this->id;
		$data['username'] = $this->username;
		$data['first_name'] = $this->first_name;
		$data['last_name'] = $this->last_name;
		$data['email'] = $this->email;
		$data['contact_no'] = $this->contact_no;
		$data['address'] = $this->address;
		$data['date_of_birth'] = $this->date_of_birth;
		$data['profile_image'] = $this->profile_image;
		$data['user_role'] = $this->user_role;
		$data['referal_code'] = $this->referal_code;
		$data['form_steps'] = $this->form_steps;
		$data['document_steps'] = $this->document_steps;
		$data['online_status'] = $this->online_status;
		$data['lat'] = $this->lat;
		$data['lng'] = $this->lng;
		$data['created_at'] = date('d-m-Y', $this->created_at);
		$taxiDetail = DriverDetails::find()->where(['user_id' => $this->id])->one();
		if (!empty($taxiDetail)) {
			$data['verify_status'] = $taxiDetail['is_verified'];
            $data['rejection_reason'] = $taxiDetail['rejection_reason'];
			if (!empty($taxiDetail->vehical)) {
				$data['vehicle_detail'] = $taxiDetail->vehical->asVehicleListJson();
			} else {
				$data['vehicle_detail'] = Null;
			}
		} else {
			$data['verify_status'] = 2;
            $data['rejection_reason'] = Null;
		}


        $settings = new WebSetting();
        $driver_min_wallet_bal = $settings->getSettingBykey('taxi_min_wallet_balance');
        $data['driver_min_wallet_bal'] = $driver_min_wallet_bal;
        $data['driver_wallet_bal'] = (int)(new DriverWallet())->availableBalance($this->id);
        // $data['driver_wallet_bal'] = (string)(new RideEarnings())->availableAmount($this->id);
       

        if( $data['driver_min_wallet_bal']<=$data['driver_wallet_bal']){
            $data['booking_eligibility'] = true;
        }else{
            $data['booking_eligibility'] = false;

        }




		return $data;
	}
    public function getDriverDetails()
	{
		return $this->hasOne(\app\modules\admin\models\DriverDetails::className(), ['user_id' => 'id']);
	}
    

 
  
  
    
}
