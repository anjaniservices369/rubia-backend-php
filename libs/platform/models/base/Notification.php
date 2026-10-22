<?php


namespace app\modules\admin\models\base;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
/**
 * This is the base model class for table "notification".
 *
 * @property integer $id
 * @property string $title
 * @property integer $order_id
 * @property integer $user_id
 * @property string $module
 * @property string $icon
 * @property integer $mark_read
 * @property string $model_type
 * @property integer $check_on_ajax
 * @property integer $status
 * @property string $created_on
 * @property string $updated_on
 * @property integer $create_user_id
 * @property integer $update_user_id
 *
 * @property \app\models\User $createUser
 * @property \app\models\User $updateUser
 */
class Notification extends \app\components\BaseActiveRecord
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

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title'], 'required'],
            [['order_id', 'user_id', 'check_on_ajax', ], 'integer'],
            [['created_on', 'updated_on'], 'safe'],
            [['title', 'module', 'icon', 'model_type'], 'string', 'max' => 255],
            [['mark_read', 'status'], 'string', 'max' => 4]
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'notification';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'title' => Yii::t('app', 'Title'),
            'order_id' => Yii::t('app', 'Order ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'module' => Yii::t('app', 'Module'),
            'icon' => Yii::t('app', 'Icon'),
            'mark_read' => Yii::t('app', 'Mark Read'),
            'model_type' => Yii::t('app', 'Model Type'),
            'check_on_ajax' => Yii::t('app', 'Check On Ajax'),
            'status' => Yii::t('app', 'Status'),
            'created_on' => Yii::t('app', 'Created On'),
            'updated_on' => Yii::t('app', 'Updated On'),
          
        ];
    }
    
    /**
     * @return \yii\db\ActiveQuery
     */
  
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
            // 'blameable' => [
            //     'class' => BlameableBehavior::className(),
            //     'createdByAttribute' => 'create_user_id',
            //     'updatedByAttribute' => 'update_user_id',
            // ],
        ];
    }

public function asJson(){
    $data = [] ; 
            $data['id'] =  $this->id;
        
                $data['title'] =  $this->title;
        
                $data['order_id'] =  $this->order_id;
        
                $data['user_id'] =  $this->user_id;
        
                $data['module'] =  $this->module;
        
                $data['icon'] =  $this->icon;
        
                $data['mark_read'] =  $this->mark_read;
        
                $data['model_type'] =  $this->model_type;
        
                $data['check_on_ajax'] =  $this->check_on_ajax;
        
                $data['status'] =  $this->status;
        
                $data['created_on'] =  $this->created_on;
        
                $data['updated_on'] =  $this->updated_on;
        
                $data['create_user_id'] =  $this->create_user_id;
        
                $data['update_user_id'] =  $this->update_user_id;
        
            return $data;
}
    /**
     * @inheritdoc
     * @return \app\modules\admin\models\NotificationQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\admin\models\NotificationQuery(get_called_class());
    }
}


