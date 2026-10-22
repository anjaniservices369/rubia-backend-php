<?php


namespace app\modules\admin\models\base;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
/**
 * This is the base model class for table "banner".
 *
 * @property integer $id
 * @property integer $banner_category_id
 * @property string $title
 * @property string $image
 * @property string $description
 * @property integer $type_id
 * @property integer $sort_order
 * @property integer $status
 * @property string $created_on
 * @property string $updated_on
 * @property integer $create_user_id
 * @property integer $update_user_id
 *
 * @property \app\modules\admin\models\BannerCategorys $bannerCategory
 */
class Banner extends \yii\db\ActiveRecord
{
    use \mootensai\relation\RelationTrait;


    /**
    * This function helps \mootensai\relation\RelationTrait runs faster
    * @return array relation names of this model
    */
    public function relationNames()
    {
        return [
            'bannerCategory'
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
            [['banner_category_id', 'title'], 'required'],
            [['banner_category_id', 'type_id', 'sort_order', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['description'], 'string'],
            [['created_on', 'updated_on'], 'safe'],
            [['title', 'image'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'banner';
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

    
    public function getTypeOptionsBadges()
    {

        if ($this->type_id == self::STATUS_ACTIVE) {
            return '<span class="badge text-bg-success">Active</span>';
        } elseif ($this->type_id == self::STATUS_INACTIVE) {
            return '<span class="badge text-bg-secondary">In Active</span>';
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
            'banner_category_id' => Yii::t('app', 'Banner Category ID'),
            'title' => Yii::t('app', 'Title'),
            'image' => Yii::t('app', 'Image'),
            'description' => Yii::t('app', 'Description'),
            'type_id' => Yii::t('app', 'Type ID'),
            'sort_order' => Yii::t('app', 'Sort Order'),
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
    public function getBannerCategory()
    {
        return $this->hasOne(\app\modules\admin\models\BannerCategorys::className(), ['id' => 'banner_category_id']);
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
     * @return \app\modules\admin\models\BannerQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\admin\models\BannerQuery(get_called_class());
    }
public function asJson(){
    $data = [] ; 
            $data['id'] =  $this->id;
        
                $data['banner_category_id'] =  $this->banner_category_id;
        
                $data['title'] =  $this->title;
        
                $data['image'] =  $this->image;
        
                $data['description'] =  $this->description;
        
                $data['type_id'] =  $this->type_id;
        
                $data['sort_order'] =  $this->sort_order;
        
                $data['status'] =  $this->status;
        
                $data['created_on'] =  $this->created_on;
        
                $data['updated_on'] =  $this->updated_on;
        
                $data['create_user_id'] =  $this->create_user_id;
        
                $data['update_user_id'] =  $this->update_user_id;
        
            return $data;
}


}


