<?php

namespace app\modules\admin\models\base;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

/**
 * This is the base model class for table "web_setting".
 *
 * @property integer $setting_id
 * @property string $name
 * @property string $setting_key
 * @property string $value
 * @property integer $type_id
 * @property integer $status
 * @property string $created_date
 * @property string $updated_date
 * @property integer $create_user_id
 * @property integer $updated_user_id
 *
 * @property \app\models\User $createUser
 * @property \app\models\User $updatedUser
 */
class WebSetting extends \app\components\BaseActiveRecord
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
            'updatedUser'
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'setting_key', 'value'], 'required'],
            [['value'], 'string'],
            [['type_id', 'status', 'create_user_id', 'updated_user_id'], 'integer'],
            [['created_date', 'updated_date'], 'safe'],
            [['name'], 'string', 'max' => 255],
            [['setting_key'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'web_setting';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'setting_id' => Yii::t('app', 'Setting ID'),
            'name' => Yii::t('app', 'Name'),
            'setting_key' => Yii::t('app', 'Setting Key'),
            'value' => Yii::t('app', 'Value'),
            'type_id' => Yii::t('app', 'Type ID'),
            'status' => Yii::t('app', 'Status'),
            'created_date' => Yii::t('app', 'Created Date'),
            'updated_date' => Yii::t('app', 'Updated Date'),
            'create_user_id' => Yii::t('app', 'Create User ID'),
            'updated_user_id' => Yii::t('app', 'Updated User ID'),
        ];
    }
    
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreateUser()
    {
        return $this->hasOne(\app\models\User::className(), ['id' => 'create_user_id']);
    }
        
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedUser()
    {
        return $this->hasOne(\app\models\User::className(), ['id' => 'updated_user_id']);
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
                'value' => new \yii\db\Expression('NOW()'),
            ],
            'blameable' => [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'create_user_id',
                'updatedByAttribute' => 'updated_user_id',
            ],
        ];
    }


    /**
     * @inheritdoc
     * @return \app\modules\admin\models\WebSettingQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\admin\models\WebSettingQuery(get_called_class());
    }

    public function getSettingBykey($key) {
		$model = self::find ()->where ( ['setting_key'=> $key] )->one ();
		 if (! empty ( $model )) {
			
			return $model->value;
		}
    }
}
