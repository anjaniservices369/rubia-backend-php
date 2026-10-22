<?php

namespace app\modules\api\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\filters\AccessRule;

/**
 * Base class of every API controller (modules driverapp, customerapp, taxi*, managerapp, ...).
 *
 * Gives each action: JSON output, sendJsonResponse() (adds `status`/`url`, hides SQL and stack text from `error`
 * and logs it instead), authUserId() (user id behind the `auth_code` header or query parameter) and the
 * API_OK / API_NOK constants. Keep the response shapes: the shipped apps depend on them.
 */
abstract class BKController extends Controller {
	const API_OK = 'OK';
	const API_NOK = 'NOK';
	// [doc:start] behaviors - Behaviors
	public function behaviors() {
		return [ 
				'access' => [ 
						'class' => AccessControl::className (),
						'ruleConfig' => [ 
								'class' => AccessRule::className () 
						],
						'rules' => [ 
								[ 
										'actions' => [ 
												'index' 
										],
										'allow' => true,
										'roles' => [ 
												'@' 
										] 
								],
								[ 
										'actions' => [ 
												'index' 
										],
										'allow' => true,
										'roles' => [ 
												'?',
												'@' 
										] 
								] 
						] 
				],
				
				'verbs' => [ 
						'class' => \yii\filters\VerbFilter::className (),
						'actions' => [ 
								'delete' => [ 
										'post',
										'delete' 
								] 
						] 
				] 
		];
	}
	// [doc:end] behaviors
	public $enableCsrfValidation = false;
	private $resp = [ 
			'status' => self::API_NOK 
	];
	private $user_id;
	// [doc:start] beforeAction - Before Action
	public function beforeAction($action) {
		$this->resp ['url'] = \yii::$app->request->pathInfo;
		$this->dropInvalidAuthCode ();
		return parent::beforeAction ( $action );
	}
	// [doc:end] beforeAction
	/**
	 * auth_code is a signed JWT. Anything that is not a valid, unexpired JWT is removed from the request
	 * so downstream session lookups treat the call as unauthenticated (same as an unknown code before).
	 */
	private function dropInvalidAuthCode() {
		$request = \Yii::$app->request;
		$code = $request->headers->get ( 'auth_code' );
		if ($code === null) {
			$code = $request->get ( 'auth_code' );
		}
		if ($code === null) {
			$code = $request->post ( 'auth_code' );
		}
		if ($code === null || $code === '' || \app\modules\auth\services\JwtService::isAcceptable ( $code )) {
			return;
		}
		$request->headers->remove ( 'auth_code' );
		unset ( $_GET ['auth_code'], $_POST ['auth_code'] );
		$request->setQueryParams ( array_diff_key ( $request->getQueryParams (), [ 'auth_code' => 1 ] ) );
		$body = $request->getBodyParams ();
		if (is_array ( $body )) {
			$request->setBodyParams ( array_diff_key ( $body, [ 'auth_code' => 1 ] ) );
		}
		\Yii::warning ( 'Rejected non-JWT or expired auth_code on ' . $request->pathInfo, 'auth' );
	}
	/**
	 * Id of the user owning the auth_code of this request (header `auth_code` or query parameter), or null.
	 */
	protected function authUserId() {
		$code = \Yii::$app->request->headers->get ( 'auth_code' );
		if ($code === null) {
			$code = \Yii::$app->request->getQueryParam ( 'auth_code' );
		}

		return $code ? ( new \app\modules\auth\services\AuthSettings () )->getAuthSession ( $code ) : null;
	}
	// [doc:start] sendJsonResponse - Send Json Response
	public function sendJsonResponse($data = null) {
		if ($data != null)
			$this->resp = ArrayHelper::merge ( $this->resp, $data );

		if (isset ( $this->resp ['error'] )) {
			$this->resp ['error'] = $this->scrubError ( $this->resp ['error'] );
		}

		return $this->resp;
	}
	// [doc:end] sendJsonResponse

	/**
	 * Database and framework internals (SQLSTATE codes, table and column names, class names) never go to a
	 * client. They are logged and the app gets a neutral sentence instead; ordinary messages pass unchanged.
	 *
	 * @param string|array $error
	 * @return string|array
	 */
	private function scrubError($error) {
		if (is_array ( $error )) {
			foreach ( $error as $key => $value ) {
				$error [$key] = $this->scrubError ( $value );
			}
			return $error;
		}
		if (is_string ( $error ) && preg_match ( '/SQLSTATE|Integrity constraint|Duplicate entry|yii\\\\|PDOException|Stack trace|\.php\b|Undefined (array key|index|offset|variable)|Call to (a member function|undefined)/i', $error )) {
			\Yii::error ( 'API error hidden from client: ' . $error, 'api' );
			return 'Something went wrong. Please try again in a moment.';
		}
		return $error;
	}
}
