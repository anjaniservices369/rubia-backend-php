<?php

namespace app\modules\admin\models\base;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

/**
 * This is the base model class for table "page".
 *
 * @property integer $id
 * @property string $title
 * @property string $slug
 * @property string $description
 * @property integer $state_id
 * @property integer $type_id
 * @property string $created_on
 * @property string $updated_on
 * @property integer $create_user_id
 */
class Page extends \yii\db\ActiveRecord
{
    use \mootensai\relation\RelationTrait;


    /**
    * This function helps \mootensai\relation\RelationTrait runs faster
    * @return array relation names of this model
    */
   
    CONST TYPE_PRIVACY = 1;
    CONSt TYPE_TERMS = 3;
    CONST TYPE_HELP = 9;
    CONST TYPE_ABOUT = 2;
    CONST TYPE_FAQ = 10;



    const STATE_INACTIVE = 2;

    const STATE_ACTIVE = 1;

   // const STATE_DELETED = 2;

    public function getStateOptions()
    {
        return [
            self::STATE_ACTIVE => \Yii::t('app', 'Active'),
            self::STATE_INACTIVE => \Yii::t('app', 'In Active'),
          //  self::STATE_DELETED => \Yii::t('app', 'Deleted')
        ];
    }
    public function stateBadges()
    {
        $states = $this->getStateOption();
        if ($this->state_id == self::STATE_ACTIVE) {
            return '<span class="label label-success">' . $states[self::STATE_ACTIVE] . '</span>';
        } elseif ($this->state_id == self::STATE_INACTIVE) {
            return '<span class="label label-default">' . $states[self::STATE_INACTIVE] . '</span>';
        } 
    }
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title', 'slug', 'create_user_id'], 'required'],
            [['description'], 'string'],
            [['state_id', 'type_id', 'create_user_id'], 'integer'],
            [['created_on', 'updated_on'], 'safe'],
            [['title'], 'string', 'max' => 255],
            [['slug'], 'string', 'max' => 225]
        ];
    }

    public static function gettypeOption()
    {
        return [
            self::TYPE_ABOUT => \Yii::t('app', 'About Us'),
            self::TYPE_PRIVACY => \Yii::t('app', 'Privacy'),
            self::TYPE_TERMS => \Yii::t('app', 'Terms and Condtion'),
            self::TYPE_HELP => \Yii::t('app', 'Help'),
            self::TYPE_FAQ => \Yii::t('app', 'FAQs'),
           // self::TYPE_SHIPPING => \Yii::t('app', 'Fast shipping'),
           // self::TYPE_GUARANTEE => \Yii::t('app', '365 Day Guarantee')
        ];
    }

    public function getType()
    {
        $types = self::gettypeOption();
        
        return isset($types[$this->type_id]) ? $types[$this->type_id] : \Yii::t('app', 'Not select');
    }


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'page';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'title' => Yii::t('app', 'Title'),
            'slug' => Yii::t('app', 'Slug'),
            'description' => Yii::t('app', 'Description'),
            'state_id' => Yii::t('app', 'State ID'),
            'type_id' => Yii::t('app', 'Type ID'),
            'created_date' => Yii::t('app', 'Created On'),
            'updated_date' => Yii::t('app', 'Updated On'),
            'create_user_id' => Yii::t('app', 'Create User ID'),
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
                'createdAtAttribute' => 'created_date',
                'updatedAtAttribute' => 'updated_date',
                'value' => date('Y-m-d'),
            ],
            'blameable' => [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'create_user_id',
                'updatedByAttribute' => false,
            ],
        ];
    }


    /**
     * @inheritdoc
     * @return \app\modules\admin\models\PageQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\admin\models\PageQuery(get_called_class());
    }
}
