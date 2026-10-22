<?php


namespace app\modules\admin\models\base;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

/**
 * This is the base model class for table "taxi_payout".
 *
 * @property integer $id
 * @property integer $driver_id
 * @property double $amount
 * @property integer $payment_type
 * @property string $method_reason
 * @property integer $type_id
 * @property integer $status
 * @property string $created_on
 * @property string $updated_on
 * @property integer $create_user_id
 * @property integer $update_user_id
 *
 * @property \app\modules\admin\models\User $driver
 */
class TaxiPayout extends \yii\db\ActiveRecord
{
    use \mootensai\relation\RelationTrait;


    /**
     * This function helps \mootensai\relation\RelationTrait runs faster
     * @return array relation names of this model
     */
    public function relationNames()
    {
        return [
            'driver'
        ];
    }

    const STATUS_REJECTED = 0;
    const STATUS_APPROVED = 1;
    const STATUS_PROCESSING = 2; 

    const IS_FEATURED = 1;
    const IS_NOT_FEATURED = 0;


    const PAYMENT_TYPE_CREDIT = 1;
    const PAYMENT_TYPE_DEBIT = 2;

    const NOT_WITHDRAW_REQUEST = 0;
    const WITHDRAW_REQUEST = 1;
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['driver_id', 'amount', 'payment_type', 'method_reason',  'status'], 'required'],
            [['driver_id', 'payment_type', 'type_id', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['amount'], 'number'],
            [['method_reason', 'created_on', 'updated_on'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'taxi_payout';
    }

   public function getStateOptions()
    {
        return [

            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_PROCESSING => 'Processing',

        ];
    }
    public function getStateOptionsBadges()
    {

        if ($this->status == self::STATUS_REJECTED) {
            return '<span class="badge bg-danger">Rejected</span>';
        } elseif ($this->status == self::STATUS_APPROVED) {
            return '<span class="badge bg-success">Approved</span>';
        }elseif ($this->status == self::STATUS_PROCESSING) {
            return '<span class="badge bg-warning">Processing</span>';
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

    public function getPaymentTypeOptions()
    {
        return [

            self::PAYMENT_TYPE_CREDIT => 'Credit',
            self::PAYMENT_TYPE_DEBIT => 'Debit',


        ];
    }
    public function getPaymentTypeOptionsBadges()
    {

        if ($this->payment_type == self::PAYMENT_TYPE_CREDIT) {
            return '<span class="badge bg-info">Credit</span>';
        } elseif ($this->payment_type == self::PAYMENT_TYPE_DEBIT) {
            return '<span class="badge bg-info">Debit</span>';
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
            'type_id' => Yii::t('app', 'Type ID'),
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
        return $this->hasOne(\app\models\User::className(), ['id' => 'driver_id']);
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
     * @return \app\modules\admin\models\TaxiPayoutQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\admin\models\TaxiPayoutQuery(get_called_class());
    }
    public function asJson()
    {
        $data = [];
        $data['id'] =  $this->id;

        $data['driver_id'] =  $this->driver_id;

        $data['amount'] =  $this->amount;

        $data['payment_type'] =  $this->payment_type;

        $data['method_reason'] =  $this->method_reason;

        $data['type_id'] =  $this->type_id;

        $data['status'] =  $this->status;

        $data['created_on'] =  $this->created_on;

        $data['updated_on'] =  $this->updated_on;

        $data['create_user_id'] =  $this->create_user_id;

        $data['update_user_id'] =  $this->update_user_id;

        return $data;
    }
}
