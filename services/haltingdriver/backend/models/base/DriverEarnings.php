<?php


namespace app\modules\admin\models\base;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
/**
 * This is the base model class for table "driver_earnings".
 *
 * @property integer $id
 * @property integer $booking_id
 * @property integer $driver_id
 * @property string $total_distance_km
 * @property double $total_amount
 * @property integer $admin_per
 * @property double $admin_earning
 * @property double $driver_earning
 * @property integer $payment_method
 * @property integer $status
 * @property string $created_on
 * @property string $updated_on
 * @property integer $create_user_id
 * @property integer $update_user_id
 *
 * @property \app\modules\admin\models\User $driver
 * @property \app\modules\admin\models\Booking $booking
 * @property \app\modules\admin\models\User $updateUser
 * @property \app\modules\admin\models\User $createUser
 */
class DriverEarnings extends \yii\db\ActiveRecord
{
    use \mootensai\relation\RelationTrait;

    public $created_at;
    public $updated_at;

    public $updated_user_id; 

    /**
    * This function helps \mootensai\relation\RelationTrait runs faster
    * @return array relation names of this model
    */
    public function relationNames()
    {
        return [
            'driver',
            'booking',
            'updateUser',
            'createUser'
        ];
    }

    const STATUS_APPROVED = 1;
    const STATUS_CANCELED = 2;
    const STATUS_REJECTED = 3;
    const STATUS_PENDING = 4;

const payment_type_credit = 1;
const payment_type_debit = 2;

const is_settled_wallet_yes =1;
const is_settled_wallet_no =2;


const payment_method_wallet = 1;
const payment_method_qr = 2;
const payment_method_cash = 3;


    const IS_FEATURED = 1;
    const IS_NOT_FEATURED = 0;
 
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['booking_id', 'driver_id', 'total_distance_km', 'total_amount', 'admin_per', 'admin_earning', 'driver_earning', 'payment_method', 'status', 'created_on', 'updated_on'], 'required'],
            [['booking_id', 'driver_id', 'admin_per', 'payment_method',  'create_user_id', 'update_user_id'], 'integer'],
            [['total_amount', 'admin_earning', 'driver_earning'], 'number'],
            [['created_on', 'updated_on','update_user_id'], 'safe'],
            [['total_distance_km'], 'string', 'max' => 255],
            [['status'],'string']
        ];
    }

    

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'driver_earnings';
    }

    public function getStateOptions()
    {
        return [

            self::STATUS_APPROVED => 'Active',
            self::STATUS_PENDING => 'Deleted',

        ];
    }
    public function getStateOptionsBadges()
    {

        if ($this->status == self::STATUS_APPROVED) {
            return '<span class="badge text-bg-success">Active</span>';
        } elseif ($this->status == self::STATUS_PENDING) {
            return '<span class="badge text-bg-danger">Deleted</span>';
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

    // Add a function to get payment method options
public function getPaymentMethodOptions()
{
    return [
        self::payment_method_wallet => 'Wallet',
        self::payment_method_qr => 'QR Code',
        self::payment_method_cash => 'Cash',
        // Add more payment methods as needed
    ];
}

// Add a function to get payment method badges
public function getPaymentMethodOptionsBadges()
{
    $paymentMethod = $this->payment_method;

    switch ($paymentMethod) {
        case self::payment_method_wallet:
            return '<span class="badge text-bg-primary">Wallet</span>';
        case self::payment_method_qr:
            return '<span class="badge text-bg-info">QR Code</span>';
        case self::payment_method_cash:
            return '<span class="badge text-bg-warning">Cash</span>';
        // Add more cases as needed
        default:
            return '';
    }
}


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'booking_id' => Yii::t('app', 'Booking ID'),
            'driver_id' => Yii::t('app', 'Driver ID'),
            'total_distance_km' => Yii::t('app', 'Total Distance Km'),
            'total_amount' => Yii::t('app', 'Total Amount'),
            'admin_per' => Yii::t('app', 'Admin Per'),
            'admin_earning' => Yii::t('app', 'Admin Earning'),
            'driver_earning' => Yii::t('app', 'Driver Earning'),
            'payment_method' => Yii::t('app', 'Payment Method'),
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
    public function getDriver()
    {
        return $this->hasOne(\app\modules\admin\models\User::className(), ['id' => 'driver_id']);
    }
        
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBooking()
    {
        return $this->hasOne(\app\modules\admin\models\Booking::className(), ['id' => 'booking_id']);
    }
        
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdateUser()
    {
        return $this->hasOne(\app\modules\admin\models\User::className(), ['id' => 'update_user_id']);
    }
        
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreateUser()
    {
        return $this->hasOne(\app\modules\admin\models\User::className(), ['id' => 'create_user_id']);
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
                'createdAtAttribute' => 'updated_on',
                'updatedAtAttribute' => 'created_on',
                'value' => function () {
                    return date('Y-m-d H:i:s');
                },
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
     * @return \app\modules\admin\models\DriverEarningsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\admin\models\DriverEarningsQuery(get_called_class());
    }
public function asJson(){
    $data = [] ; 
            $data['id'] =  $this->id;
        
                $data['booking_id'] =  $this->booking_id;
        
                $data['driver_id'] =  $this->driver_id;
        
                $data['total_distance_km'] =  $this->total_distance_km;
        
                $data['total_amount'] =  $this->total_amount;
        
                $data['admin_per'] =  $this->admin_per;
        
                $data['admin_earning'] =  $this->admin_earning;
        
                $data['driver_earning'] =  $this->driver_earning;
        
                $data['payment_method'] =  $this->payment_method;
        
                $data['status'] =  $this->status;
        
                $data['created_on'] =  $this->created_on;
        
                $data['updated_on'] =  $this->updated_on;
        
                $data['create_user_id'] =  $this->create_user_id;
        
                $data['update_user_id'] =  $this->update_user_id;
        
            return $data;
}


}


