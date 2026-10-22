<?php


namespace app\modules\admin\models\base;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use DateTime;
/**
 * This is the base model class for table "plans".
 *
 * @property integer $id
 * @property string $title
 * @property string $description
 * @property double $price
 * @property integer $validity_in_days
 * @property integer $status
 * @property string $created_on
 * @property string $updated_on
 * @property integer $create_user_id
 * @property integer $update_user_id
 *
 * @property \app\modules\admin\models\DriverSubscription[] $driverSubscriptions
 * @property \app\modules\admin\models\DriverSubscriptionLogs[] $driverSubscriptionLogs
 * @property \app\modules\admin\models\User $createUser
 * @property \app\modules\admin\models\User $updateUser
 */
class Plans extends \yii\db\ActiveRecord
{
    use \mootensai\relation\RelationTrait;


    /**
    * This function helps \mootensai\relation\RelationTrait runs faster
    * @return array relation names of this model
    */
    public function relationNames()
    {
        return [
            'driverSubscriptions',
            'driverSubscriptionLogs',
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
            [['title', 'price', 'validity_in_days'], 'required'],
            [['description'], 'string'],
            [['price'], 'number'],
            [['validity_in_days', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['created_on', 'updated_on'], 'safe'],
            [['title'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'plans';
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
            'title' => Yii::t('app', 'Title'),
            'description' => Yii::t('app', 'Description'),
            'price' => Yii::t('app', 'Price'),
            'validity_in_days' => Yii::t('app', 'Validity In Days'),
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
    public function getDriverSubscriptions()
    {
        return $this->hasMany(\app\modules\admin\models\DriverSubscription::className(), ['plan_id' => 'id']);
    }
        
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDriverSubscriptionLogs()
    {
        return $this->hasMany(\app\modules\admin\models\DriverSubscriptionLogs::className(), ['plan_id' => 'id']);
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
     * @return \app\modules\admin\models\PlansQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\admin\models\PlansQuery(get_called_class());
    }
public function asJson($driver_id=''){
    $data = [] ;
 
            $data['id'] =  $this->id;
        
                $data['title'] =  $this->title;
        
                $data['description'] =  $this->description;
        
                $data['price'] =  $this->price;
        
                $data['validity_in_days'] =  $this->validity_in_days;

                
        
                $data['status'] =  $this->status;

                if(!empty($driver_id)){

                   // $driver_subscription = DriverSubscription::find()->where(['driver_id'=>$driver_id])->one();
                    $driver_subscription = DriverSubscription::find()->where(['driver_id'=>$driver_id]) ->orderBy(['driver_subscription.id' => SORT_DESC])->one();
                    if(!empty($driver_subscription)){




                $date_now = time(); //current timestamp
                $end_date = strtotime($driver_subscription->end_date);

                 if ($driver_subscription->isCurrent()) {
                    $data['plan_activated'] = true; 
                }else{
                    $data['plan_activated'] = false; 
                }








                    }else{
                        $data['plan_activated'] = false; 

                    }


                }else{
                    $data['plan_activated'] = false; 
                }
        
                $data['created_on'] =  $this->created_on;
        
                $data['updated_on'] =  $this->updated_on;
        
                $data['create_user_id'] =  $this->create_user_id;
        
                $data['update_user_id'] =  $this->update_user_id;

                if($driver_subscription){
                    $data['start_date'] = $driver_subscription->start_date;
                    $data['end_date'] = $driver_subscription->end_date;
                }else{
                  $data['start_date'] = '';
                    $data['end_date'] = '';
                }

        
            return $data;
}


}


