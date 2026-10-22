<?php


namespace app\modules\admin\models\base;

use app\models\User;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use mootensai\behaviors\UUIDBehavior;

/**
 * This is the base model class for table "driver_details".
 *
 * @property integer $id
 * @property integer $user_id
 * @property double $commission_percent
 * @property integer $city_id
 * @property string $address
 * @property string $license_no
 * @property string $proof_of_license
 * @property string $proof_of_license_back
 * @property integer $vehical_id
 * @property string $rc_number
 * @property string $vehical_number
 * @property string $rc_proof
 * @property string $rc_proof_back
 * @property string $adhaar_number
 * @property string $adhaar_front
 * @property string $adhaar_back
 * @property string $pan_number
 * @property string $pan_front
 * @property string $pan_back
 * @property integer $is_verified
 * @property integer $status
 * @property string $created_on
 * @property string $updated_on
 * @property integer $create_user_id
 * @property integer $update_user_id
 *
 * @property \app\modules\admin\models\User $user
 * @property \app\modules\admin\models\Vehicals $vehical
 * @property \app\modules\admin\models\City $city
 */
class DriverDetails extends \yii\db\ActiveRecord
{
    use \mootensai\relation\RelationTrait;
public $first_name;
public $last_name;
public $email;
public $gender;
public $date_of_birth;
    /**
     * This function helps \mootensai\relation\RelationTrait runs faster
     * @return array relation names of this model
     */
    public function relationNames()
    {
        return [
            'user',
            'vehical',
            'city',
            'bankDetail'
        ];
    }

    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_DELETE = 2;

    const IS_FEATURED = 1;
    const IS_NOT_FEATURED = 0;

    const VERIFIED = 1;
    const PENDING = 2;
    const REJECTED = 3;



    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id',  'address', 'license_no',  'vehical_id', 'rc_number', 'vehical_number',  'adhaar_number',  'is_verified', 'status',], 'required'],
            [['user_id', 'city_id', 'vehical_id', 'is_verified', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['commission_percent'], 'number'],
            [['created_on', 'updated_on', 'created_on', 'updated_on', 'update_user_id', 'pan_number', 'pan_front', 'pan_back','vehical_speed','vehical_owner','email','gender','date_of_birth'], 'safe'],
            [['address', 'license_no', 'proof_of_license', 'proof_of_license_back', 'rc_number', 'vehical_number', 'rc_proof', 'rc_proof_back',
             'adhaar_number', 'adhaar_front', 'adhaar_back', 'pan_number', 'pan_front', 'pan_back'], 'string', 'max' => 255],
            [['proof_of_license', 'proof_of_license_back', 'rc_proof', 'rc_proof_back', 'adhaar_front', 'adhaar_back', 'pan_front'], 'file'],
            [['proof_of_license', 'proof_of_license_back', 'rc_proof', 'rc_proof_back', 'adhaar_front', 
            'adhaar_back', 'pan_front'], 'required', 'on' => 'create']

        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'driver_details';
    }

    public function getStateOptions()
    {
        return [
            self::STATUS_ACTIVE => 'Active',

            self::STATUS_INACTIVE => 'In Active',
            self::STATUS_DELETE => 'Deleted',

        ];
    }
    public function getStateOptionsBadges()
    {

        if ($this->status == self::STATUS_ACTIVE) {
            return '<span class="badge text-bg-success">Active</span>';
        } elseif ($this->status == self::STATUS_INACTIVE) {
            return '<span class="badge text-bg-warning">In Active</span>';
        } elseif ($this->status == self::STATUS_DELETE) {
            return '<span class="badge text-bg-danger">Deleted</span>';
        }
    }

    public function getVerifyOptions()
    {
        return [
            self::VERIFIED => 'Verified',

            self::PENDING => 'Pending',
            self::REJECTED => 'Rejected',

        ];
    }
    public function getVerifyOptionsBadges()
    {

        if ($this->is_verified == self::VERIFIED) {
            return '<span class="badge bg-success">Verified</span>';
        } elseif ($this->is_verified == self::PENDING) {
            return '<span class="badge bg-warning">Pending</span>';
        } elseif ($this->is_verified == self::REJECTED) {
            return '<span class="badge bg-danger">Rejected</span>';
        }
    }

    public function getFeatureOptions()
    {
        return [

            self::IS_FEATURED => 'Is Featured',
            self::IS_NOT_FEATURED => 'Not Featured',

        ];
    }

    public function getFeatureOptionsBadges()
    {
        if ($this->is_featured == self::IS_FEATURED) {
            return '<span class="badge text-bg-success">Featured</span>';
        } elseif ($this->is_featured == self::IS_NOT_FEATURED) {
            return '<span class="badge text-bg-danger">Not Featured</span>';
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'commission_percent' => Yii::t('app', 'Commission Percent'),
            'city_id' => Yii::t('app', 'City ID'),
            'address' => Yii::t('app', 'Address'),
            'license_no' => Yii::t('app', 'License No'),
            'proof_of_license' => Yii::t('app', 'Proof Of License'),
            'proof_of_license_back' => Yii::t('app', 'Proof Of License Back'),
            'vehical_id' => Yii::t('app', 'Vehicle ID'),
            'rc_number' => Yii::t('app', 'Rc Number'),
            'vehical_number' => Yii::t('app', 'Vehicle Number'),
            'rc_proof' => Yii::t('app', 'Rc Proof'),
            'rc_proof_back' => Yii::t('app', 'Rc Proof Back'),
            'adhaar_number' => Yii::t('app', 'Adhaar Number'),
            'adhaar_front' => Yii::t('app', 'Adhaar Front'),
            'adhaar_back' => Yii::t('app', 'Adhaar Back'),
            'pan_number' => Yii::t('app', 'Pan Number'),
            'pan_front' => Yii::t('app', 'Pan Front'),
            'pan_back' => Yii::t('app', 'Pan Back'),
            'is_verified' => Yii::t('app', 'Is Verified'),
            'status' => Yii::t('app', 'Status'),
            'created_on' => Yii::t('app', 'Created On'),
            'updated_on' => Yii::t('app', 'Updated On'),
            'create_user_id' => Yii::t('app', 'Create User ID'),
            'update_user_id' => Yii::t('app', 'Update User ID'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(\app\models\User::className(), ['id' => 'user_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVehical()
    {
        return $this->hasOne(\app\modules\admin\models\Vehicals::className(), ['id' => 'vehical_id']);
    }

    public function getbankDetail()
    {
        return $this->hasOne(\app\modules\admin\models\DriverBankDetails::className(), ['user_id' => 'user_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCity()
    {
        return $this->hasOne(\app\modules\admin\models\City::className(), ['id' => 'city_id']);
    }

    /**
     * @inheritdoc
     * @return array mixed
     */
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'created_on',
                'updatedAtAttribute' => 'updated_on',
                'value' => new \yii\db\Expression('NOW()'),
            ],
            'blameable' => [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'create_user_id',
                'updatedByAttribute' => 'update_user_id',
            ],

        ];
    }



    /**
     * @inheritdoc
     * @return \app\modules\admin\models\DriverDetailsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\admin\models\DriverDetailsQuery(get_called_class());
    }
    public function asJson()
    {
        $data = [];
        $data['id'] =  $this->id;

        $data['user_id'] =  $this->user_id;

        $data['commission_percent'] =  $this->commission_percent;

        $data['city_id'] =  $this->city_id;

        $data['address'] =  $this->address;

        $data['license_no'] =  $this->license_no;

        $data['proof_of_license'] =  $this->proof_of_license;

        $data['proof_of_license_back'] =  $this->proof_of_license_back;

        $data['vehical_id'] =  $this->vehical_id;

        $data['rc_number'] =  $this->rc_number;

        $data['vehical_number'] =  $this->vehical_number;

        $data['rc_proof'] =  $this->rc_proof;

        $data['rc_proof_back'] =  $this->rc_proof_back;

        $data['adhaar_number'] =  $this->adhaar_number;

        $data['adhaar_front'] =  $this->adhaar_front;

        $data['adhaar_back'] =  $this->adhaar_back;

        $data['pan_number'] =  $this->pan_number;

        $data['pan_front'] =  $this->pan_front;

        $data['pan_back'] =  $this->pan_back;

        $data['is_verified'] =  $this->is_verified;

        $data['status'] =  $this->status;

        $data['created_on'] =  $this->created_on;

        $data['updated_on'] =  $this->updated_on;

        $data['create_user_id'] =  $this->create_user_id;

        $data['update_user_id'] =  $this->update_user_id;

        return $data;
    }

    // Profile Details

    public function ProfileDetailsJson()
    {
        $data = [];

        $data['id'] =  $this->id;

        $data['user']['first_name'] =  isset($this->user->first_name) ? $this->user->first_name : $this->user->username;
        $data['user']['last_name'] =  isset($this->user->last_name) ? $this->user->last_name : $this->user->username;
        $data['user']['contact_no'] =  isset($this->user->contact_no) ? $this->user->contact_no : "";
        $data['user']['email'] =  isset($this->user->email) ? $this->user->email : "";
        $data['user']['date_of_birth'] =  isset($this->user->date_of_birth) ? $this->user->date_of_birth : "";
        $data['user']['gender'] =  isset($this->user->gender) ? $this->user->gender : "";
        $data['user']['address'] =  $this->address;
     
if ($this->vehical !== null) {
    $data['vehicle_detail']['vehicle'] = $this->vehical->title ?? '';
    $data['vehicle_detail']['image'] = $this->vehical->image ?? '';
    $data['vehicle_detail']['vehicle_type'] = $this->vehical_type ?? '';
} else {
    // Handle the case when $this->vehical is null
    $data['vehicle_detail']['vehicle'] = 'Unknown Vehicle';
    $data['vehicle_detail']['image'] = '';
    $data['vehicle_detail']['vehicle_type'] = 'Unknown Vehicle Type';
}


      $driver_bank_details = DriverBankDetails::find()->where(['driver_id' => $this->user_id])->one();

$data['bank_detail'] = [
    'bank_name' => $driver_bank_details->bank_name ?? "",
    'account_holder_name' => $driver_bank_details->account_holder_name ?? "",
    'account_number' => $driver_bank_details->account_number ?? "",
    'branch_name' => $driver_bank_details->branch_name ?? "",
    'ifsc_code' => $driver_bank_details->ifsc ?? "",
    'upi_id' => $driver_bank_details->upi ?? "",
];

// the taxi tables belong to the taxi services; a service that does not have them reports 0
$rideCount = class_exists(RideRequest::class) ? RideRequest::find()->where(['driver_id' => $this->user_id])->andWhere(['status' => RideRequest::STATUS_RIDE_COMPLETED_PAID])->count() : 0;
$taxiRating = class_exists(TaxiRating::class) ? TaxiRating::find()->where(['taxi_driver_id' => $this->user_id])->count() : 0;
$data['ride_count'] = $rideCount;
$data['rating_count'] = $taxiRating;
$data['license_no'] = $this->license_no ?? "";

$data['proof_of_license'] = $this->proof_of_license ?? "";

$data['license_expiery_date'] = $this->license_expiery_date ?? "";
$data['chassis_number'] = $this->chassis_number ?? "";

$data['chassis_image'] = $this->chassis_image ?? "";

$data['proof_of_license_back'] = $this->proof_of_license_back ?? "";

$data['vehical_speed'] = $this->vehical_speed ?? "";
$data['vehical_owner'] = $this->vehical_owner ?? "";
$data['model_name'] = $this->model_name ?? "";

$data['rc_number'] = $this->rc_number ?? "";

$data['vehical_number'] = $this->vehical_number ?? "";

$data['rc_proof'] = $this->rc_proof ?? "";

$data['rc_proof_back'] = $this->rc_proof_back ?? "";

$data['adhaar_number'] = $this->adhaar_number ?? "";

$data['adhaar_front'] = $this->adhaar_front ?? "";

$data['adhaar_back'] = $this->adhaar_back ?? "";

$data['pan_number'] = $this->pan_number ?? "";

$data['pan_front'] = $this->pan_front ?? "";

$data['pan_back'] = $this->pan_back ?? "";

$data['is_verified'] = $this->is_verified ?? "";



   

        return $data;
    }
}
