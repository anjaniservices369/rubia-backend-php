<?php


namespace app\modules\admin\models\base;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
/**
 * This is the base model class for table "driver_subscription_logs".
 *
 * @property integer $id
 * @property integer $driver_id
 * @property integer $plan_id
 * @property double $plan_price
 * @property integer $validity_in_days
 * @property string $start_date
 * @property string $end_date
 * @property integer $status
 * @property integer $create_user_id
 * @property integer $update_user_id
 * @property string $created_on
 * @property string $updated_on
 *
 * @property \app\modules\admin\models\User $driver
 * @property \app\modules\admin\models\Plans $plan
 * @property \app\modules\admin\models\User $createUser
 * @property \app\modules\admin\models\User $updateUser
 */
class DriverSubscriptionLogs extends \yii\db\ActiveRecord
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
            'plan',
            'createUser',
            'updateUser'
        ];
    }

    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_DELETE = 2;

    const IS_FEATURED = 1;
    const IS_NOT_FEATURED = 0;
 
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['driver_id', 'plan_id', 'plan_price', 'validity_in_days', 'start_date', 'end_date'], 'required'],
            [['driver_id', 'plan_id', 'validity_in_days', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['plan_price'], 'number'],
            [['start_date', 'end_date', 'created_on', 'updated_on'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'driver_subscription_logs';
    }

    public function getStateOptions()
    {
        return [

            self::STATUS_INACTIVE => 'In Active',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_DELETE => 'Deleted',

        ];
    }
    public function getStateOptionsBadges()
    {

        if ($this->status == self::STATUS_ACTIVE) {
            return '<span class="badge text-bg-success">Active</span>';
        } elseif ($this->status == self::STATUS_INACTIVE) {
            return '<span class="badge text-bg-secondary">In Active</span>';
        }elseif ($this->status == self::STATUS_DELETE) {
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
            'plan_id' => Yii::t('app', 'Plan ID'),
            'plan_price' => Yii::t('app', 'Plan Price'),
            'validity_in_days' => Yii::t('app', 'Validity In Days'),
            'start_date' => Yii::t('app', 'Start Date'),
            'end_date' => Yii::t('app', 'End Date'),
            'status' => Yii::t('app', 'Status'),
            'create_user_id' => Yii::t('app', 'Create User ID'),
            'update_user_id' => Yii::t('app', 'Update User ID'),
            'created_on' => Yii::t('app', 'Created On'),
            'updated_on' => Yii::t('app', 'Updated On'),
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
    public function getPlan()
    {
        return $this->hasOne(\app\modules\admin\models\Plans::className(), ['id' => 'plan_id']);
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
     * @inheritdoc
     * @return \app\modules\admin\models\DriverSubscriptionLogsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\admin\models\DriverSubscriptionLogsQuery(get_called_class());
    }
public function asJson(){
    $data = [] ; 
            $data['id'] =  $this->id;
        
                $data['driver_id'] =  $this->driver_id;
        
                $data['plan_id'] =  $this->plan_id;
        
                $data['plan_price'] =  $this->plan_price;
        
                $data['validity_in_days'] =  $this->validity_in_days;
        
                $data['start_date'] =  $this->start_date;
        
                $data['end_date'] =  $this->end_date;
        
                $data['status'] =  $this->status;
        
                $data['create_user_id'] =  $this->create_user_id;
        
                $data['update_user_id'] =  $this->update_user_id;
        
                $data['created_on'] =  $this->created_on;
        
                $data['updated_on'] =  $this->updated_on;
        
            return $data;
}


}


