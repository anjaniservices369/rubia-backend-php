<?php

namespace app\modules\admin\models\base;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
/**
 * This is the base model class for table "city".
 *
 * @property integer $id
 * @property string $name
 * @property string $boundary
 * @property integer $status
 * @property string $created_on
 * @property string $updated_on
 * @property integer $create_user_id
 * @property integer $update_user_id
 */
class City extends \yii\db\ActiveRecord
{
    use \mootensai\relation\RelationTrait;
    
   
    /**
     * This function helps \mootensai\relation\RelationTrait runs faster
     * @return array relation names of this model
     */

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
            [['boundary', 'name', 'States_id'], 'required'],
            [['status', 'create_user_id', 'update_user_id', 'States_id'], 'integer'],
            [['created_on', 'updated_on', 'boundary'], 'safe'],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
    
        // Define the "create" scenario
        $scenarios['create'] = ['name', 'States_id', 'boundary']; // Add other attributes if needed
    
        // Add the "update" scenario
        $scenarios['update'] = ['name', 'States_id', 'boundary']; // Adjust as needed
    
        return $scenarios;
    }
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'city';
    }

    public function relationNames()
    {
        return ['bookings', 'cityRateCards'];
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
        } elseif ($this->status == self::STATUS_DELETE) {
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
            'id' => 'ID',
            'name' => 'Name',
            'boundary' => 'Boundary',
            'status' => 'Status',
            'created_on' => 'Created On',
            'updated_on' => 'Updated On',
            'create_user_id' => 'Create User ID',
            'update_user_id' => 'Update User ID',
        ];
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
     * @inheritdoc
     * @return \app\modules\admin\models\CityQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\admin\models\CityQuery(get_called_class());
    }
    public function asJson()
    {
        $data = [];
        $data['id'] = $this->id;

        $data['name'] = $this->name;

        $data['boundary'] = $this->boundary;

        $data['status'] = $this->status;

        $data['created_on'] = $this->created_on;

        $data['updated_on'] = $this->updated_on;

        $data['create_user_id'] = $this->create_user_id;

        $data['update_user_id'] = $this->update_user_id;

        $data['States_id'] = $this->States_id;

        return $data;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBookings()
    {
        return $this->hasMany(\app\modules\admin\models\Booking::className(), [
            'city_id' => 'id',
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCityRateCards()
    {
        return $this->hasMany(
            \app\modules\admin\models\CityRateCard::className(),
            ['city_id' => 'id']
        );
    }
}
