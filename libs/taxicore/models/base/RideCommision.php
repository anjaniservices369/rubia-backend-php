<?php


namespace app\modules\admin\models\base;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
/**
 * This is the base model class for table "ride_commision".
 *
 * @property integer $id
 * @property integer $city_id
 * @property integer $vehicle_id
 * @property integer $base_fare
 * @property double $min_distance
 * @property double $fare_per_distance
 * @property double $waiting_time_limit
 * @property double $charges_per_minute
 * @property double $fare_per_minute
 * @property double $no_of_person
 * @property integer $commision
 * @property integer $status
 * @property string $created_on
 * @property string $updated_on
 * @property integer $create_user_id
 * @property integer $update_user_id
 *
 * @property \app\modules\admin\models\City $city
 * @property \app\modules\admin\models\Vehicals $vehicle
 */
class RideCommision extends \yii\db\ActiveRecord
{
    use \mootensai\relation\RelationTrait;


    /**
    * This function helps \mootensai\relation\RelationTrait runs faster
    * @return array relation names of this model
    */
    public function relationNames()
    {
        return [
            'city',
            'vehicle'
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
            [['city_id', 'vehicle_id', 'base_fare', 'min_distance', 'fare_per_distance', 'waiting_time_limit', 'charges_per_minute', 'fare_per_minute', 'no_of_person', 'commision', 'status', ], 'required'],
            [['city_id', 'vehicle_id', 'base_fare', 'commision', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['created_on', 'updated_on', 'create_user_id', 'update_user_id'], 'safe'],
            [['min_distance', 'fare_per_distance', 'waiting_time_limit', 'charges_per_minute', 'fare_per_minute', 'no_of_person'], 'number'],
            [['created_on', 'updated_on'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ride_commision';
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
            'city_id' => Yii::t('app', 'City'),
            'vehicle_id' => Yii::t('app', 'Vehicle'),
            'base_fare' => Yii::t('app', 'Base Fare(₹)'),
            'min_distance' => Yii::t('app', 'Min Distance(Km)'),
            'fare_per_distance' => Yii::t('app', 'Fare Per Distance(₹)'),
            'waiting_time_limit' => Yii::t('app', 'Waiting Time Limit(min)'),
            'charges_per_minute' => Yii::t('app', 'Charges Per Minute(₹)'),
            'fare_per_minute' => Yii::t('app', 'Fare Per Minute(min)'),
            'no_of_person' => Yii::t('app', 'No Of Person'),
            'commision' => Yii::t('app', 'Admin Commision(%)'),
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
    public function getCity()
    {
        return $this->hasOne(\app\modules\admin\models\City::className(), ['id' => 'city_id']);
    }
        
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVehicle()
    {
        return $this->hasOne(\app\modules\admin\models\Vehicals::className(), ['id' => 'vehicle_id']);
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
     * @return \app\modules\admin\models\RideCommisionQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\admin\models\RideCommisionQuery(get_called_class());
    }
public function asJson(){
    $data = [] ; 
            $data['id'] =  $this->id;
        
                $data['city_id'] =  $this->city_id;
        
                $data['vehicle_id'] =  $this->vehicle_id;
        
                $data['base_fare'] =  $this->base_fare;
        
                $data['min_distance'] =  $this->min_distance;
        
                $data['fare_per_distance'] =  $this->fare_per_distance;
        
                $data['waiting_time_limit'] =  $this->waiting_time_limit;
        
                $data['charges_per_minute'] =  $this->charges_per_minute;
        
                $data['fare_per_minute'] =  $this->fare_per_minute;
        
                $data['no_of_person'] =  $this->no_of_person;
        
                $data['commision'] =  $this->commision;
        
                $data['status'] =  $this->status;
        
                $data['created_on'] =  $this->created_on;
        
                $data['updated_on'] =  $this->updated_on;
        
                $data['create_user_id'] =  $this->create_user_id;
        
                $data['update_user_id'] =  $this->update_user_id;
        
            return $data;
}


}


