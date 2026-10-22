<?php


namespace app\modules\admin\models\base;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
/**
 * This is the base model class for table "user_wallet".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $amount
 * @property integer $payment_type
 * @property string $method_reason
 * @property integer $status
 * @property string $created_on
 * @property string $updated_on 
 * @property integer $create_user_id
 * @property integer $update_user_id
 *
 * @property \app\modules\admin\models\User $user
 * @property \app\modules\admin\models\User $createUser
 * @property \app\modules\admin\models\User $updateUser
 */
class UserWallet extends \yii\db\ActiveRecord
{
    use \mootensai\relation\RelationTrait;


    /**
    * This function helps \mootensai\relation\RelationTrait runs faster
    * @return array relation names of this model
    */
    public function relationNames()
    {
        return [
            'user',
            'createUser',
            'updateUser'
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
            [['id', 'user_id', 'amount', 'payment_type'], 'required'],
            [['id', 'user_id', 'payment_type', 'status', 'create_user_id', 'update_user_id'], 'integer'],
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
        return 'user_wallet';
    }

    public function getStateOptions()
    {
        return [

        

        ];
    }
    public function getStateOptionsBadges()
    {

     
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
     * The customer of this record, asked from the user-app system (never read from a local customer table).
     *
     * @return array [id, name, phone, email, dob, gender] with empty strings when unknown
     */
    public function getCustomer()
    {
        return \app\modules\customerapi\services\CustomerClient::profileCached($this->user_id);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(\app\modules\admin\models\User::className(), ['id' => 'user_id']);
    }
        
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreateUser()
    {
        return $this->hasOne(\app\modules\admin\models\User::className(), ['id' => 'create_user_id']);
    }
        
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdateUser()
    {
        return $this->hasOne(\app\modules\admin\models\User::className(), ['id' => 'update_user_id']);
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
     * The customer's balance. A customer has ONE wallet for all services (it belongs to the user app); `module` on an
     * entry only records which service the money moved in. $module is kept for old callers and ignored.
     */
    public static function availableBalance($driver_id, $module = null){
        $module = null;

        $driver_wallet_credit = UserWallet::find()->where(['user_id'=>$driver_id])->andFilterWhere(['module'=>$module])->andWhere(['payment_type'=>UserWallet::payment_type_credit])->andWhere(['status'=>UserWallet::STATUS_SUCCESS])->sum('amount');
        $driver_wallet_debit = UserWallet::find()->where(['user_id'=>$driver_id])->andFilterWhere(['module'=>$module])
        ->andWhere(['payment_type'=>UserWallet::payment_type_debit])->andWhere(['in','status',[UserWallet::STATUS_PENDING,UserWallet::STATUS_SUCCESS]])->sum('amount');
        $total = $driver_wallet_credit-$driver_wallet_debit;
        return !empty($total)?round($total,2):0;

    }




    /**
     * @inheritdoc
     * @return \app\modules\admin\models\UserWalletQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\admin\models\UserWalletQuery(get_called_class());
    }
public function asJson(){
    $data = [] ; 
            $data['id'] =  $this->id;
        
                $data['user_id'] =  $this->user_id;
        
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


