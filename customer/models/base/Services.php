<?php


namespace app\modules\admin\models\base;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
/**
 * This is the base model class for table "services".
 *
 * @property integer $id
 * @property string $title
 * @property string $image
 * @property integer $status
 * @property string $created_on
 * @property string $updated_on
 * @property integer $create_user_id
 * @property integer $update_user_id
 *
 * @property \app\modules\admin\models\User $createUser
 * @property \app\modules\admin\models\User $updateUser
 */
class Services extends \yii\db\ActiveRecord
{
    use \mootensai\relation\RelationTrait;


    /**
    * This function helps \mootensai\relation\RelationTrait runs faster
    * @return array relation names of this model
    */
    public function relationNames()
    {
        return [
            'createUser',
            'updateUser'
        ];
    }

    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_DELETE = 2;

    const IS_FEATURED = 1;
    const IS_NOT_FEATURED = 0;


 



const DRIVER = 1;
const TAXI = 2;
const AUTO = 3;
const BIKE = 4;
const OTHERS = 5;


 
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title', 'image','type'], 'required'],
            [['status', 'create_user_id', 'update_user_id'], 'integer'],
            [['created_on', 'updated_on'], 'safe'],
            [['title', 'image'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'services';
    }

    public function getStateOptions()
    {
        return [

            self::STATUS_INACTIVE => 'In Active',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_DELETE => 'Deleted',

        ];
    }

public function getTypeOptions(){


    return [





        self::DRIVER => 'driver',
        self::TAXI => 'taxi',
        self::AUTO => 'auto',
        self::BIKE => 'Bike',
        self::OTHERS => 'other',

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
            'image' => Yii::t('app', 'Image'),
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
     * @return \app\modules\admin\models\ServicesQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\admin\models\ServicesQuery(get_called_class());
    }
public function asJson(){
    $data = [] ; 
            $data['id'] =  $this->id;
        
                $data['title'] =  $this->title;
        
                $data['image'] =  $this->image;
                $data['type'] =  $this->type;

                $data['status'] =  $this->status;
        
                $data['created_on'] =  $this->created_on;
        
                $data['updated_on'] =  $this->updated_on;
        
                $data['create_user_id'] =  $this->create_user_id;
        
                $data['update_user_id'] =  $this->update_user_id;
        
            return $data;
}


}


