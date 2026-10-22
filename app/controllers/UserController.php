<?php

namespace app\controllers;

use app\components\AccessRule;
use app\components\AuthHandler;
use app\components\BaseController;
use app\models\LoginForm;
use app\models\User;
use app\models\UserSearch;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use app\models\Wishlist;
use app\models\Cart;
use app\models\Notification;
use app\models\Setting;
use app\models\base\ChangePassword;
use app\models\CashbackTransaction;
use app\models\Transaction;

/**
 * UserController implements the CRUD actions for User model.
 */
class UserController extends BaseController {
	
	/**
	 * @inheritdoc
	 */
	public function behaviors() {
		return [ 
				'access' => [ 
						'class' => AccessControl::className (),
						'rules' => [ 
								[ 
										'actions' => [ 
												'logout',
												'dashboard',
												'profile',
												'merchant',
												'merchant-grocery',
												'edit-profile',
												'delivery-boy',
												'manager',
												'index' ,
												'send-otp',
												'verify-otp',
												'forget-password',
												'reset-password',
												'changepassword-admin',
												'create',
												'view',
												'delete',
												'update',
												'vendor',
												'business',
												'subscribe-user'
										],
										'allow' => true,
										'matchCallback' => function () {
											return User::isAdmin();
										}
								],
								[ 
										'actions' => [ 
												'login',
												'merchant',
												'signup',
												'logout',
												'dashboard',
												'user-dashboard',
												'forget',
												'reset',
												'test',
												'verify',
												'check',
												'fb-callback',
												'send-otp',
												'verify-otp',
												'forget-password',
												'reset-password',
												'changepassword',
												'all-user',
												'admin-login',
												'vendor',
												'business',
												'subscribe-user'




										],
										'allow' => true,
										'roles' => [ 
											'?',
											'@',
											'*' 
									] 
										]
										
						] 
				],
				'verbs' => [ 
						'class' => VerbFilter::className (),
						'actions' => [ 
								'logout' => [ 
										'post' 
								] 
						] 
				] 
		];
	}
	public function actions() {
		return [ 
				'auth' => [ 
						'class' => 'yii\authclient\AuthAction',
						'successCallback' => [ 
								$this,
								'onAuthSuccess' 
						] 
				] 
		];
	}
	public function onAuthSuccess($client) {
		$this->layout = 'frontend';
		(new AuthHandler ( $client ))->handle ();
		return $this->redirect ( [ 
				'user/profile',
				'id' => \Yii::$app->user->id 
		] );
	}
	public function actionWishlist() {
		$this->layout = 'frontend';
		
		$wishlists = Wishlist::find ()->where ( [ 
				'create_user_id' => \Yii::$app->user->id 
		] )->all ();
		
		return $this->render ( 'wishlist', [ 
				'wishlists' => $wishlists 
		] );
	}
	public function actionProfile($id) {
		$model = $this->findModel ( $id );
		
		return $this->render ( 'profile', [ 
				'model' => $model 
		] );
	}
	public function actionEditProfile($id) {
		$model = $this->findModel ( $id );
		if ($model->load ( \Yii::$app->request->post () )) {
			$oldimage = $model->profile_image;
			$image = UploadedFile::getInstance ( $model, 'profile_image' );
			if (! empty ( $image )) {
				if (! empty ( $image->baseName )) {
					$image->saveAs ( UPLOAD_PATH . '/' . $image->baseName . '.' . $image->extension );
					$model->profile_image = $image->baseName . '.' . $image->extension;
				} else {
					$model->profile_image = $oldimage;
				}
			}
			
			if (! $model->save ()) {
				print_r ( $model->getErrors () );
				exit ();
			}
		}
		
		return $this->redirect ( [ 
				'profile',
				'id' => $model->id 
		] );
	}
	public function actionLogout() {
		Yii::$app->user->logout ();
		
		return $this->goHome ();
	}
	
	/**
	 * Lists all User models.
	 *
	 * @return mixed
	 */
	public function actionIndex() {
		$this->layout = 'main';
		$searchModel = new UserSearch ();
		$dataProvider = $searchModel->search ( Yii::$app->request->queryParams );
		
		return $this->render ( 'index', [ 
				'searchModel' => $searchModel,
				'dataProvider' => $dataProvider 
		] );
	}
	public function actionAllUser() {
		$this->layout = 'main';
		$searchModel = new UserSearch ();
		$dataProvider = $searchModel->search ( Yii::$app->request->queryParams, User::ROLE_USER);
		
		return $this->render ( 'index', [ 
				'searchModel' => $searchModel,
				'dataProvider' => $dataProvider 
		] );
	}
	public function actionMerchant() {
		$this->layout = 'main';
		$searchModel = new UserSearch ();
		$dataProvider = $searchModel->search ( Yii::$app->request->queryParams, User::ROLE_MERCHANT);
		
		return $this->render ( 'index', [ 
				'searchModel' => $searchModel,
				'dataProvider' => $dataProvider 
		] );
	}
	public function actionDeliveryBoy() {
		$this->layout = 'main';
		$searchModel = new UserSearch ();
		$dataProvider = $searchModel->search ( Yii::$app->request->queryParams, User::ROLE_DELIVERYBOY);
		
		return $this->render ( 'index', [ 
				'searchModel' => $searchModel,
				'dataProvider' => $dataProvider 
		] );
	}
	public function actionManager() {
		$this->layout = 'main';
		$searchModel = new UserSearch ();
		$dataProvider = $searchModel->search ( Yii::$app->request->queryParams, User::ROLE_MANAGER);
		
		return $this->render ( 'index', [ 
				'searchModel' => $searchModel,
				'dataProvider' => $dataProvider 
		] );
	}
	public function actionMerchantGrocery() {
		$this->layout = 'main';
		$searchModel = new UserSearch ();
		$dataProvider = $searchModel->search ( Yii::$app->request->queryParams, User::ROLE_GROCERY_MERCHANT);
		
		return $this->render ( 'index', [ 
				'searchModel' => $searchModel,
				'dataProvider' => $dataProvider 
		] );
	}
	public function actionDashboard() {
		$this->layout = 'main';
		return $this->render ( 'dashboard' );
	}
	/**
	 * Displays a single User model.
	 *
	 * @param integer $id        	
	 * @return mixed
	 */
	public function actionView($id) {
		$signup_bonus= CashbackTransaction::getSignupBonus($id);
		$referal_bonus= CashbackTransaction::getReferralApprovedCashback($id);
		$cashback= CashbackTransaction::getCashback($id);

		return $this->render ( 'view', [ 
				'model' => $this->findModel ($id),
				'signup_bonus' => $signup_bonus, 
				'referal_bonus' => $referal_bonus,
				'cashback' => $cashback
		] );
	}
	public function actionLogin() {
		$this->layout = 'frontend';
		$userModel = new User ();
		$userModel->scenario = 'add-user';
		if (! Yii::$app->user->isGuest) {
			return $this->goHome ();
		}
		
		$model = new LoginForm ();
		if ($model->load ( Yii::$app->request->post () ) && $model->login ()) {
			
			
			
			return $this->redirect ( [ 
					'site/index' 
			] );
		}
		return $this->render ( '/user/login', [ 
				'model' => $model,
				'userModel' => $userModel 
		] );
	}
	public function actionAdminLogin() {
		//$this->layout = '//main-login';
		if (! Yii::$app->user->isGuest) {
			return $this->goHome ();
		}
		
		$model = new LoginForm ();
		if ($model->load ( Yii::$app->request->post () ) && $model->login ()) {
			return $this->redirect ( [ 
				//	'user/dashboard' 
				'/admin'
			] );
		}
		return $this->render ( '/site/admin-login', [ 
				'model' => $model 
		] );
	}
	public function actionAddAdmin() {
		$model = new User ();
		$this->layout = 'main-login';
		$model->scenario = 'add-admin';
		$user = User::find ()->count ();
		if (! empty ( $user )) {
			return $this->redirect ( [ 
					'user/login' 
			] );
		}
		if ($model->load ( \yii::$app->request->post () )) {
			if ($model->validate ()) {
				$model->setPassword ();
				if ($model->save ( false )) {
					return $this->redirect ( [ 
							'user/login' 
					] );
				}
			}
		}
		return $this->render ( 'add-admin', [ 
				'model' => $model 
		] );
	}
	public function actionSignup() {
		$model = new User ();
		$model->scenario = 'add-user';
		if ($model->load ( Yii::$app->request->post () )) {
			$model->username = Yii::$app->request->post ('user[email]');
			$model->role_id = User::ROLE_USER;
			$model->state_id = User::STATE_ACTIVE;
			Yii::$app->request->post ('verified')==1?$model->is_mobile_verified = 1:$model->is_mobile_verified = 0;
			$image = UploadedFile::getInstance ( $model, 'profile_image' );
			if (! empty ( $image )) {
				$image->saveAs ( UPLOAD_PATH . '/' . $image->baseName . '.' . $image->extension );
				$model->profile_image = $image->baseName . '.' . $image->extension;
			}
			if ($model->validate ()) {
				$model->setPassword ();
				if (! $model->save ( false )) {
					\Yii::$app->session->setFlash ( 'error', \Yii::t ( 'app', $model->getErrors () ) );
				}
	// 			$send = Yii::$app->mailer->compose()
    //            ->setFrom(Yii::$app->params['adminEmail'])
    //           ->setTo($model->email)
    //  ->setSubject('Signup success')
	//  ->send();
	//  if($send){
    //     echo "Send";
    // }else{
	// 	echo 'dssdd';exit;
	// }
	 
				Yii::$app->user->login ( $model );
			
	
				$noti = new Notification();
				$noti->saveNotification('New User',$model,'','fas fa-user-plus');
			} else {
				
				print_r ( $model->getErrors () );
				exit ();
			}
			
			return $this->redirect ( [ 
					'/site/index' 
			] );
		}
		
		return $this->render ( 'signup', [ 
				'model' => $model 
		
		] );
	}
	
	/**
	 * Creates a new User model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 *
	 * @return mixed
	 */
	public function actionCreate() {
		$model = new User ();
		$model->scenario = 'add-user';
		if ($model->load ( Yii::$app->request->post () )) {
			$model->username = $model->email;
			$model->state_id = User::STATE_ACTIVE;
			$model->created_on = date('Y-m-d');
			$image = UploadedFile::getInstance ( $model, 'profile_image' );
			if (! empty ( $image )) {
				$image->saveAs ( UPLOAD_PATH . '/' . $image->baseName . '.' . $image->extension );
				$model->profile_image = $image->baseName . '.' . $image->extension;
			}
			if ($model->validate ()) {
				$model->setPassword ();
				if (! $model->save ( false )) {
					\Yii::$app->session->setFlash ( 'error', \Yii::t ( 'app', $model->getErrors () ) );
				}
			}else{
				print_r($model->getErrors());exit;
			}
			
			return $this->redirect ( [ 
					'view',
					'id' => $model->id 
			] );
		} else {
			return $this->render ( 'create', [ 
					'model' => $model 
			] );
		}
	}
	
	/**
	 * Updates an existing User model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 *
	 * @param integer $id        	
	 * @return mixed
	 */
	public function actionUpdate($id) {
		$model = $this->findModel ( $id );
	
		$model->scenario = 'update';
		$oldImage = $model->profile_image;
		if ($model->load ( Yii::$app->request->post () )) {
			
			$model->profile_image = $oldImage;
			
			$image = UploadedFile::getInstance ( $model, 'profile_image' );
			if (! empty ( $image )) {
				$image->saveAs ( UPLOAD_PATH . '/' . $image->baseName . '.' . $image->extension );
				$model->profile_image = $image->baseName . '.' . $image->extension;
			}
				//var_dump($model);exit;
			if (! $model->save ()) {
				\Yii::$app->session->setFlash ( 'error', \Yii::t ( 'app', $model->getErrors () ) );
			}
			return $this->redirect ( [ 
					'view',
					'id' => $model->id 
			] );
		} else {
			return $this->render ( 'update', [ 
					'model' => $model 
			] );
		}
	}
	
	/**
	 * Deletes an existing User model.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 *
	 * @param integer $id        	
	 * @return mixed
	 */
	public function actionChangepassword($id) {
		$model = $this->findModel ( $id );
		$model->scenario = 'changepassword';
		if ($model->load ( Yii::$app->request->post () )) {
			$model->password = $model->newPassword;
			$model->setPassword ();
			if ($model->save ()) {
				return $this->redirect ( [ 
						'user/dashboard' 
				] );
			} else {
				Yii::$app->getSession ()->setFlash ( 'error', 'old password is incorrect' );
			}
		}
		return $this->render ( 'changepassword', [ 
				'model' => $model 
		] );
	}

	// public function actionChangepasswordAdmin($id) {
	// 	$model = $this->findModel ( $id );
	// 	$model->scenario = 'changepassword';
	// 	if ($model->load ( Yii::$app->request->post () )) {
	// 		$model->password = $model->newPassword;
	// 		$model->setPassword ();
	// 		if ($model->save ()) {
	// 			return $this->redirect ( [ 
	// 					'user/dashboard' 
	// 			] );
	// 		} else {
	// 			Yii::$app->getSession ()->setFlash ( 'error', 'old password is incorrect' );
	// 		}
	// 	}
	// 	return $this->render ( 'changepassword', [ 
	// 			'model' => $model 
	// 	] );
	// }

	public function actionDelete($id) {
		$model = $this->findModel ( $id );
		
		if ($model->role_id == User::ROLE_ADMIN || $model->role_id == \Yii::$app->user->identity->role_id) {
			\Yii::$app->session->setFlash ( "error", \Yii::t ( 'app', 'Can not delete login user or admin' ) );
		} else
			$model->delete ();
		return $this->redirect ( [ 
				'index' 
		] );
	}
	
	/**
	 * Finds the User model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 *
	 * @param integer $id        	
	 * @return User the loaded model
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	protected function findModel($id) {
		if (($model = User::findOne ( $id )) !== null) {
			return $model;
		} else {
			throw new NotFoundHttpException ( 'The requested page does not exist.' );
		}
	}
	public function actionUserDashboard(){
		$this->layout = 'frontend';
		$model = User::find()->where(['id'=>\Yii::$app->user->id])->one();
		if($get = \Yii::$app->request->get()){
			return $this->render ( '/user/user_dashboard', [ 
				'model' => $model,
				'page' => $get['page'],
				'pageSize' => $get['per-page']
		] );
		}

		return $this->render ( '/user/user_dashboard', [ 
			'model' => $model,
	] );
	}
	public function actionForget(){
		$data =  Yii::$app->request->post();
		$token = substr(base64_encode(sha1(mt_rand())), 0, 64);
		$email  = $data['email']; 
		$user = User::find(\Yii::$app->user->id)->where(['email' => $email])->one();
		$user->access_token = $token;
		// $user->token_status = 1;
		 if ($user->save()) {
				 $subject = " Password Reset";
				 $cfg = \app\modules\admin\models\Integrations::userMail(Yii::$app->params['adminEmail'], $subject);
				 Yii::$app->mailer->compose('reset', ['user' =>  $user, 'text' => $cfg['text']])
					->setFrom($cfg['from'])
					->setSubject($cfg['subject'])
					->setTo($email)
					->send();
				
		 }
		}
		function actionReset(){

		
			$data =  Yii::$app->request->get();
		    $model = User::find()->Where([
				'access_token' => $data['access_token']
			])->one();
			if ($model->load ( Yii::$app->request->post () )) {
				$model->password = $model->newPassword;
				$model->setPassword ();
				if (!$model->save ()) {
					print_r($model->getErrors());exit;
				}else{
					return $this->redirect ( [ 
						'site/index' 
				] );
				}
			}
			if(!empty($model)){
				return $this->render('reset',[
					'model' => $model 
				]);
			}
			
		}
		public function actionResetPassword(){

			
			\Yii::$app->response->format = 'json';
			$password = $data['cnfpassword'];
			$access_token = $data['access_token'];
			$model = new User;
		//	$model->scenario = 'changepassword';
			$model = User::find()->Where([
					'access_token' => $access_token
			])->one();
			$model->password = Yii::$app->security->generatePasswordHash($password);
			$model->access_token_status = 0;
			if($model->save(false)){
			$data['status'] = 1;
			$data['msg']='Changed';
			}else{
				$data['status']=0;
	 		$data['msg']=$model->getErrors();
	 }
	 return $data;
	}

	public	function actionTest(){
			return $this->render('sid');
		}
	public	function actionVerify(){
			$data =  Yii::$app->request->post();
			$model = User::find()->where(['contact_no'=>$data['phoneNumber']])->exists();
			
			return $model;
		}
	public	function actionCheck(){
			
error_reporting(0);
define( "FB_ACCOUNT_KIT_APP_ID", "2200587066874566" );
define( "FB_ACCOUNT_KIT_APP_SECRET", "e01d193bc0f6ba9fd4d583968b34ad6b" );
$code = $_POST['code'];
$csrf = $_POST['csrf'];
$auth = file_get_contents( 'https://graph.accountkit.com/v1.1/access_token?grant_type=authorization_code&code='.  $code .'&access_token=AA|'. FB_ACCOUNT_KIT_APP_ID .'|'. FB_ACCOUNT_KIT_APP_SECRET );
$access = json_decode( $auth, true );
if( empty( $access ) || !isset( $access['access_token'] ) ){
    return array( "status" => 2, "message" => "Unable to verify the phone number." );
}
//App scret proof key Ref : https://developers.facebook.com/docs/graph-api/securing-requests
$appsecret_proof= hash_hmac( 'sha256', $access['access_token'], FB_ACCOUNT_KIT_APP_SECRET ); 
//echo 'https://graph.accountkit.com/v1.1/me/?access_token='. $access['access_token'];
$ch = curl_init();
// Set query data here with the URL
curl_setopt($ch, CURLOPT_URL, 'https://graph.accountkit.com/v1.1/me/?access_token='. $access['access_token'].'&appsecret_proof='. $appsecret_proof ); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
curl_setopt($ch, CURLOPT_TIMEOUT, '4');
$resp = trim(curl_exec($ch));
curl_close($ch);
$info = json_decode( $resp, true );
if( empty( $info ) || !isset( $info['phone'] ) || isset( $info['error'] ) ){
    return array( "status" => 2, "message" => "Unable to verify the phone number." );
}
$phoneNumber = $info['phone']['national_number'];
echo json_encode( $info );
/*
$user = $this->db->query( "SELECT * FROM user WHERE phone_number = '". $phoneNumber ."'" )->result_array();
if( !empty( $user ) ){
    //Create session
    return array( "status" => "01", "message" => "Login success", "token" => $jwt );
}else{
    return array( "status" => "02", "message" => "Phonenumber not registered with us." );
}*/
		}
		function actionFbCallback(){
			$setting = new Setting();
			$appId = $setting->getSettingBykey('facebook_app_id');
			$appSecret = $setting->getSettingBykey('facebook_app_secret');
			$data =  Yii::$app->request->post();
			$access_key = $data['data']['authResponse']['accessToken'];
			$id = $data['data']['authResponse']['userID'];
			$fb = new \Facebook\Facebook([
				'app_id' => $appId,
				'app_secret' => $appSecret,
				'default_graph_version' => 'v3.2',
				]);
			  
			  try {
				// Returns a `Facebook\FacebookResponse` object
				$response = $fb->get('/me?fields=id,name,email,picture', $access_key);
			  } catch(Facebook\Exceptions\FacebookResponseException $e) {
				echo 'Graph returned an error: ' . $e->getMessage();
				exit;
			  } catch(Facebook\Exceptions\FacebookSDKException $e) {
				echo 'Facebook SDK returned an error: ' . $e->getMessage();
				exit;
			  }
			  
			  $user = $response->getGraphUser();

			 	$name = $user['name'];
				$email = $user['email'];
				$image = $user['picture']['url'];
				$checkemail = User::find()->Where(['email' => $email])->one();
				//var_dump($checkemail);exit;
				if(!empty($checkemail)){
					Yii::$app->user->login ( $checkemail );
					 return $this->redirect(\Yii::$app->request->referrer);
				}else{
					$model = new User();
					$model->scenario = 'facebook-login';
					//$model->contact_no = $number;
					$model->username = $email;
					$model->full_name = $name;
					$model->oauth_client_user_id = $id;
					$model->oauth_client = $access_key;
					$model->email = $email;
					$model->profile_image = $image;
					$model->is_mobile_verified = 0;
				//$model->contact_no = $phonenumber;
					//print_r($model->email); exit;
					//$model->is_mobile_verified = (int)$data['verified'];
					//$model->updated_on = date('Y-m-d H:i:s');
					$model->role_id = User::ROLE_USER;
					$model->state_id = User::STATE_ACTIVE;
					if($model->save(false)){
						// print_r($model->save());
						Yii::$app->user->login ( $model );
						
						return $this->redirect(\Yii::$app->request->referrer);
					}
				}

			}
		
	public function actionSendOtp(){
								$key = Setting::getSettingBykey('otp_key');
								$phone = Yii::$app->request->post()['phoneNumber'];
								
								// $key = '121815AslsGWFD8a57be16c3';
					//	$otp = (new User ())->getRandomString(4);
							$otp = mt_rand(1000,9999);
					// var_dump($otp);exit;
						$message = "Your generated OTP is ".$otp;
						$sender_id = "CASHBK";
						$mobile = $phone;
					
								$curl = curl_init();

							curl_setopt_array($curl, array(
							CURLOPT_URL => "http://control.msg91.com/api/sendotp.php?otp_length=&authkey=$key&message=$message&sender=$sender_id&mobile=$mobile&otp=$otp&email=&otp_expiry=&template=",
							CURLOPT_RETURNTRANSFER => true,
							CURLOPT_ENCODING => "",
							CURLOPT_MAXREDIRS => 10,
							CURLOPT_TIMEOUT => 30,
							CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
							CURLOPT_CUSTOMREQUEST => "POST",
							CURLOPT_POSTFIELDS => "",
							CURLOPT_SSL_VERIFYHOST => 0,
							CURLOPT_SSL_VERIFYPEER => 0,
						));

						$response = curl_exec($curl);
						$err = curl_error($curl);
						
						curl_close($curl);
					
						if ($err) {
							return $err;
							} else {
							return $response;
						}
				}
				public function actionVerifyOtp() {
					$key = Setting::getSettingBykey('otp_key');
					$phone = Yii::$app->request->post()['phoneNumber'];
					
					// $key = '121815AslsGWFD8a57be16c3';
					$otp = Yii::$app->request->post()['otp'];
				
					$mobile = $phone;
				
						
					$curl = curl_init();
			$url = "https://control.msg91.com/api/verifyRequestOTP.php?authkey=$key&mobile=$mobile&otp=$otp";
					curl_setopt_array($curl, array(
						CURLOPT_URL => "https://control.msg91.com/api/verifyRequestOTP.php?authkey=$key&mobile=$mobile&otp=$otp",
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_ENCODING => "",
						CURLOPT_MAXREDIRS => 10,
						CURLOPT_TIMEOUT => 30,
						CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
						CURLOPT_CUSTOMREQUEST => "POST",
						CURLOPT_POSTFIELDS => "",
						CURLOPT_SSL_VERIFYHOST => 0,
						CURLOPT_SSL_VERIFYPEER => 0,
						CURLOPT_HTTPHEADER => array(
						"content-type: application/x-www-form-urlencoded"
						),
					));
					
					$response = curl_exec($curl);
					$err = curl_error($curl);
					
					curl_close($curl);
					
					if ($err) {
						return $err;
					} else {
						$res = json_decode($response);
						if($res->type!='error'){
							$model = User::find()->where(['id'=>\Yii::$app->user->id])->one();
							if(!empty($model)){
								$model->is_mobile_verified = 1;
								$model->contact_no = 	$mobile;
								if(!$model->save()){
									print_r($model->getErrors());
									exit;
								}
							}
						}
						

					return $response;
					}
				
				}
				public function actionForgetPassword(){
					$data =  Yii::$app->request->post();
					\Yii::$app->response->format = 'json';
					$token = substr(base64_encode(sha1(mt_rand())), 0, 64);
					$email  = $data['email'];
					
					if($email!=''){
						
						$user = User::find()->where(['email' => $email])->one();
						if(!empty($user)){
							$user->access_token = $token;
							// exit("hii");
							$user->access_token_status = 1;
							 if ($user->save(false)) {
									 $subject = " Password Reset";
									 $mail =  Yii::$app->mailer->compose('reset', ['user' =>  $user])
									->setFrom(Yii::$app->params['adminEmail'])
									->setSubject($subject)
									->setTo($email)
									->send();
									$data ['status'] = 1;	
									$data['msg'] = 'Saved';
							 }else{
								 $data['error'] = print_r($user->getErrors());
							 }
						}
						else{
							$data ['status'] = 0;
							$data['msg'] = 'Email not exist';
						}
					}else{
						$data['status']='empty';
					} 
					
					return $data;
					}
				
				
		}

