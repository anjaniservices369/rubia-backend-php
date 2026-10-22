<?php


namespace app\modules\admin\models\base;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
/**
 * This is the base model class for table "favourite_location".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $latitude
 * @property string $logitude
 * @property string $address_type
 * @property string $address
 * @property integer $pincode
 * @property integer $status
 * @property string $created_on
 * @property string $updated_on
 * @property integer $update_user_id
 * @property integer $create_user_id
 */
class FavouriteLocation extends \yii\db\ActiveRecord
{
    use \mootensai\relation\RelationTrait;


    /**
    * This function helps \mootensai\relation\RelationTrait runs faster
    * @return array relation names of this model
    */
    public function relationNames()
    {
        return [
            ''
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
            [['user_id', 'pincode', 'status', 'update_user_id', 'create_user_id'], 'integer'],
            [['created_on', 'updated_on'], 'safe'],
            [['latitude', 'logitude', 'address_type', 'address'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'favourite_location';
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
            'id' => 'ID',
            'user_id' => 'User ID',
            'latitude' => 'Latitude',
            'logitude' => 'Logitude',
            'address_type' => 'Address Type',
            'address' => 'Address',
            'pincode' => 'Pincode',
            'status' => 'Status',
            'created_on' => 'Created On',
            'updated_on' => 'Updated On',
            'update_user_id' => 'Update User ID',
            'create_user_id' => 'Create User ID',
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
     * @return \app\modules\admin\models\FavouriteLocationQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\admin\models\FavouriteLocationQuery(get_called_class());
    }
public function asJson(){
    $data = [] ; 
            $data['id'] =  $this->id;
        
                $data['user_id'] =  $this->user_id;
        
                $data['latitude'] =  $this->latitude;
        
                $data['logitude'] =  $this->logitude;
        
                $data['address_type'] =  $this->address_type;
        
                $data['address'] =  $this->address;
        
                $data['pincode'] =  $this->pincode;
        
                $data['status'] =  $this->status;
        
                $data['created_on'] =  $this->created_on;
        
                $data['updated_on'] =  $this->updated_on;
        
                $data['update_user_id'] =  $this->update_user_id;
        
                $data['create_user_id'] =  $this->create_user_id;
        
            return $data;
}


}


