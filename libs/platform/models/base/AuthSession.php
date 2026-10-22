<?php

namespace app\modules\admin\models\base;

use Yii;

use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;

/**
 * This is the base model class for table "auth_session".
 *
 * @property integer $id
 * @property string $auth_code
 * @property string $device_token
 * @property integer $type_id
 * @property integer $create_user_id
 * @property string $created_on
 * @property string $updated_on
 *
 * @property \app\models\User $createUser
 */
class AuthSession extends \app\components\BaseActiveRecord
{
    use \mootensai\relation\RelationTrait;


    /**
    * This function helps \mootensai\relation\RelationTrait runs faster
    * @return array relation names of this model
    */
    public function relationNames()
    {
        return [
            'createUser'
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['auth_code', 'device_token', 'create_user_id', 'created_on'], 'required'],
            [['type_id', 'create_user_id'], 'integer'],
            [['created_on', 'updated_on'], 'safe'],
            [['auth_code', 'device_token'], 'string', 'max' => 256]
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'auth_session';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'auth_code' => Yii::t('app', 'Auth Code'),
            'device_token' => Yii::t('app', 'Device Token'),
            'type_id' => Yii::t('app', 'Type ID'),
            'create_user_id' => Yii::t('app', 'Create User ID'),
            'created_on' => Yii::t('app', 'Created On'),
            'updated_on' => Yii::t('app', 'Updated On'),
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
            /*'blameable' => [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => false,
                'updatedByAttribute' => 'x',
            ],*/
        ];
    }


    /**
     * @inheritdoc
     * @return \app\modules\admin\models\AuthSessionQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\admin\models\AuthSessionQuery(get_called_class());
    }
}
