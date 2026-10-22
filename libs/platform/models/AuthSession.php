<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\AuthSession as BaseAuthSession;
use yii\web\ForbiddenHttpException;

/**
 * This is the model class for table "auth_session".
 */
class AuthSession extends BaseAuthSession
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_replace_recursive(parent::rules(),
	    [
            [['auth_code', 'create_user_id', 'created_on'], 'required'],
            [['type_id', 'create_user_id'], 'integer'],
            [['created_on', 'updated_on'], 'safe'],
            [['auth_code', 'device_token'], 'string', 'max' => 256]
        ]);
    }
    public function beforeDelete() {
		return parent::beforeDelete ();
	}
	public function asJson($with_relations = false) {
		$json = [ ];
		$json ['id'] = $this->id;
		$json ['auth_code'] = $this->auth_code;
		$json ['device_token'] = $this->device_token;
		$json ['type_id'] = $this->type_id;
		$json ['create_user_id'] = $this->create_user_id;
		$json ['created_on'] = $this->created_on;
		if ($with_relations) {
			// CreateUser
			$list = $this->getCreateUser ()->all ();
			
			if (is_array ( $list )) {
				$relationData = [ ];
				foreach ( $list as $item ) {
					$relationData [] = $item;
				}
				$json ['CreateUser'] = $relationData;
			} else {
				$json ['CreateUser'] = $list;
			}
		}
		return $json;
	}
	private static $session_expiration_days = 0;
	public function beforeSave($insert) {
		return parent::beforeSave ( $insert );
	}
	public static function token($count) {
		$alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
		$pass = array (); // remember to declare $pass as an array
		$alphaLength = strlen ( $alphabet ) - 1; // put the length -1 in cache
		for($i = 0; $i < $count; $i ++) {
			$n = rand ( 0, $alphaLength );
			$pass [] = $alphabet [$n];
		}
		$p = implode ( $pass );
		return implode ( $pass );
	}
	/**
	 * Sessions of accounts that are not active (blocked by an administrator, inactive or deleted) are invisible, so
	 * every lookup of an auth_code - login check, API calls, push tokens - treats them as logged out at once.
	 */
	public static function find() {
		return new \app\models\ActiveUserSessionQuery ( get_called_class () );
	}

	public static function newSession($model) {
		self::deleteSession ( Yii::$app->user->identity->id );
		
		/*
		 * $auth_session = AuthSession::findOne(
		 * [
		 * 'device_token' => $model->device_token
		 * ]);
		 * if ($auth_session == null)
		 */
		$auth_session = new AuthSession();

		$auth_session->create_user_id = Yii::$app->user->id;
		$identity = Yii::$app->user->identity;
		$auth_session->auth_code = \app\modules\auth\services\JwtService::issue ( Yii::$app->user->id, $identity ? $identity->user_role : '' );
		$auth_session->device_token = (string) $model->device_token;
        $auth_session->type_id = $model->device_type;
        $auth_session->created_on = date('Y-m-d H:i:s');
		if ($auth_session->save ()) {
			return $auth_session;
		} else {
            Yii::error('Could not create a session: ' . json_encode($auth_session->getErrors()), 'auth');
            throw new \RuntimeException('Could not create a session');
        }
	}
	public static function deleteSession($id) {
		$old = AuthSession::findAll ( [ 
				'create_user_id' => $id
		] );
		foreach ( $old as $session ) {
			$session->delete ();
		}
		
		return true;
	}
	public static function getHead() {
		if (! function_exists ( 'getallheaders' )) {
			function getallheaders() {
				$headers = '';
				foreach ( $_SERVER as $name => $value ) {
					if (substr ( $name, 0, 5 ) == 'HTTP_') {
						$headers [str_replace ( ' ', '-', ucwords ( strtolower ( str_replace ( '_', ' ', substr ( $name, 5 ) ) ) ) )] = $value;
					}
				}
				return $headers;
			}
		}
		return getallheaders ();
	}
	public static function authenticateSession($auth_code = null) {
		if (! Yii::$app->user->isGuest)
			return true;
		if ($auth_code == null) {
			if ($auth_code == null) {
				
				$auth_code = isset ( \Yii::$app->request->headers ['auth_code'] ) ? \Yii::$app->request->headers ['auth_code'] : Yii::$app->request->getQueryParam ( 'auth_code' );
			}
			if ($auth_code == null)
				return false;
		}
		
		$auth_session = AuthSession::findOne ( array (
				'auth_code' => $auth_code 
		) );
		
		if ($auth_session != null) {
			
			if ($auth_session->createUser != null) {
				$user = $auth_session->createUser;
				Yii::$app->user->login($user, 3600 * 24 * 90 );
				$auth_session->save (); // update time is changed here
				return true;
			} else {
				throw new ForbiddenHttpException ( "auth code required." );
			}
		}
		return false;
	}

	public function getDeviceToken($id){
		$model = AuthSession::find()->where(['create_user_id'=>$id])->one();
		if(!empty($model)){
			return $model->device_token;
		}
		
		
	}
	
}
