<?php


namespace app\modules\admin\models\base;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
/**
 * This is the base model class for table "driver_wallet".
 *
 * @property integer $id
 * @property integer $driver_id
 * @property string $amount
 * @property integer $payment_type
 * @property string $method_reason
 * @property integer $status
 * @property string $created_on
 * @property string $updated_on
 * @property integer $create_user_id
 * @property integer $update_user_id
 *
 * @property \app\modules\admin\models\User $driver
 * @property \app\modules\admin\models\User $updateUser
 * @property \app\modules\admin\models\User $createUser
 */
class DriverWallet extends \yii\db\ActiveRecord
{
    use \mootensai\relation\RelationTrait;

 
    /**
    * This function helps \mootensai\relation\RelationTrait runs faster
    * @return array relation names of this model
    */
    public function relationNames()
    {
        return [
            'driver', 
            'updateUser',
            'createUser'
        ];
    }

    const STATUS_SUCCESS = 1;
    const STATUS_PENDING = 2;
    const STATUS_FAILED = 3;
    const STATUS_REJECTED = 4;



    const IS_FEATURED = 1;
    const IS_NOT_FEATURED = 0;


    const payment_type_credit = 1;
    const payment_type_debit = 2;


    
    const type_wallet_recharge = 1;
    const type_booking_cancellation = 2;
    const type_referral_earning = 3;
    const type_ride_booking = 4;
    
 
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['driver_id', 'amount', 'payment_type'], 'required'],
            [['driver_id', 'payment_type', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['amount'], 'number'],
            [['created_on', 'updated_on'], 'safe'],
            [['method_reason'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'driver_wallet';
    }

    public function getStateOptions()
    {
        return [

            self::STATUS_SUCCESS => 'Active',
            self::STATUS_PENDING => 'Deleted',

        ];
    }

    public function getPaymentOptions()
    {
        return [

            self::payment_type_credit=> 'Credit',
            self::payment_type_debit => 'Debit',

        ];
    }
    public function getStateOptionsBadges()
    {

        if ($this->status == self::STATUS_SUCCESS) {
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

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'driver_id' => Yii::t('app', 'Driver ID'),
            'amount' => Yii::t('app', 'Amount'),
            'payment_type' => Yii::t('app', 'Payment Type'),
            'method_reason' => Yii::t('app', 'Method Reason'),
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
                'createdAtAttribute' => 'created_on',
                'updatedAtAttribute' => 'updated_on',
                'value' => date('Y-m-d H:i:s'),
            ],
            'blameable' => [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'create_user_id',
                'updatedByAttribute' => 'update_user_id',
            ],
        ];
    }


    /**
     * Balance of a driver's wallet. Every service (1 driver service, 2 taxi, 3 auto, 4 bike) has its own wallet:
     * pass the module to get that service's balance; without it the balance of all the driver's entries.
     */
    public static function availableBalance($driver_id, $module = null){

        $driver_wallet_credit = DriverWallet::find()->where(['driver_id'=>$driver_id])->andFilterWhere(['module'=>$module])->andWhere(['payment_type'=>DriverWallet::payment_type_credit])->andWhere(['status'=>DriverWallet::STATUS_SUCCESS])->sum('amount');
        $driver_wallet_debit = DriverWallet::find()->where(['driver_id'=>$driver_id])->andFilterWhere(['module'=>$module])
        ->andWhere(['payment_type'=>DriverWallet::payment_type_debit])->andWhere(['in','status',[DriverWallet::STATUS_PENDING,DriverWallet::STATUS_SUCCESS]])->sum('amount');
        $total = $driver_wallet_credit-$driver_wallet_debit;
        return !empty($total)?round($total,2):0;

    }



    /**
     * @inheritdoc
     * @return \app\modules\admin\models\DriverWalletQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\admin\models\DriverWalletQuery(get_called_class());
    }
public function asJson(){
    $data = [] ; 
            $data['id'] =  $this->id;
        
                $data['driver_id'] =  $this->driver_id;
        
                $data['amount'] =  $this->amount;
        
                $data['payment_type'] =  $this->payment_type;
        
                $data['method_reason'] =  $this->method_reason;
        
        
        
                $data['status'] =  $this->status;
        
                $data['created_on'] =  $this->created_on;
        
                $data['updated_on'] =  $this->updated_on;
        
                $data['create_user_id'] =  $this->create_user_id;
        
                $data['update_user_id'] =  $this->update_user_id;
        
            return $data;
}


}


