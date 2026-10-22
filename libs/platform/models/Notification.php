<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\Notification as BaseNotification;

/**
 * This is the model class for table "notification".
 */
class Notification extends BaseNotification
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_replace_recursive(parent::rules(),
	    [
            [['title'], 'required'],
            [['order_id', 'user_id', 'check_on_ajax', 'create_user_id', 'update_user_id'], 'integer'],
            [['created_on', 'updated_on'], 'safe'],
            [['title', 'module', 'icon', 'model_type'], 'string', 'max' => 255],
            [['mark_read', 'status'], 'string', 'max' => 4]
        ]);
    }

    public function getLatestUnreadNotification(){

        $notification = Notification::find()->where(['mark_read'=>0])->limit(10)->all();

        return $notification;
    }

    public function saveNotification($message,$model,$order_id='',$icon='',$user_id='') {
		if($user_id==''){
			$user_id = \Yii::$app->user->id;
		}else{
			$user_id = $user_id;
		}
	
        $this->title = $message;
       
		//var_dump($order_id); exit;
		//$this->rest_id = $rest_id;
	
		$this->icon = $icon;
		$this->order_id = $order_id;
		$this->model_type = get_class ( $model );
        $this->create_user_id = (int)$user_id; 
		$this->check_on_ajax = 0;
		$this->mark_read = '0';
       
	
		if(!$this->save (false)){
        print_r($this->getErrors());exit;
		}
	}
	

}
