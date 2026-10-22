<?php

namespace app\modules\admin\models\base;

use Yii;


/**
 * This is the base model class for table "auth".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $source
 * @property string $source_id
 *
 * @property \app\models\User $user
 */
class Auth extends \app\components\BaseActiveRecord
{
    use \mootensai\relation\RelationTrait;


    /**
    * This function helps \mootensai\relation\RelationTrait runs faster
    * @return array relation names of this model
    */
    public function relationNames()
    {
        return [
            'user'
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'source', 'source_id'], 'required'],
            [['user_id'], 'integer'],
            [['source', 'source_id'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'auth';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'source' => Yii::t('app', 'Source'),
            'source_id' => Yii::t('app', 'Source ID'),
        ];
    }
    
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(\app\models\User::className(), ['id' => 'user_id']);
    }
    

    /**
     * @inheritdoc
     * @return \app\modules\admin\models\AuthQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\admin\models\AuthQuery(get_called_class());
    }
}
