<?php
namespace app\modules\managerapp\controllers;
use yii;
use app\modules\api\controllers\BKController;
use yii\filters\AccessControl;
use app\modules\geo\services\DrivingDistance;
use yii\filters\AccessRule;
use yii\helpers\ArrayHelper;
use app\modules\auth\services\AuthSettings;
use yii\data\ActiveDataProvider;
use app\modules\admin\forms\UserForm;
use app\modules\admin\models\Banner;
use app\modules\admin\models\Temples;
use app\modules\admin\models\Category;
use app\modules\admin\models\AuthSession;
use app\modules\admin\models\Auth;
use app\modules\admin\models\PujaServices;
use app\forms\LoginForm;
use app\models\User;
use app\modules\admin\models\CashbackTransaction;
use app\modules\admin\models\Notification;
use app\modules\admin\models\TempleTimings;
use app\modules\admin\models\TempleCategories;
use app\modules\admin\models\BankDetails;
use app\modules\admin\models\Orders;

/**
 * Manager app API: /api/manager/*  - LEGACY. Its actions (temple details, idols, services, timings) belong to an
 * earlier product and have nothing to do with rides, bookings or drivers. Nothing in the Customer or Partner
 * apps calls it. Safe to retire once you have confirmed no installed app uses it.
 */
class ManagerController extends BKController
{

    // [doc:start] behaviors - Behaviors
    public function behaviors() {

		return ArrayHelper::merge ( parent::behaviors (), [ 
			/*'corsFilter' => [
                'class' => \yii\filters\Cors::className(),
                'cors' => [
                    // restrict access to
                    'Origin' => ['http://localhost:*', 'http://localhost:58600'],
                    // Allow only POST and PUT methods
                    'Access-Control-Request-Method' => ['POST', 'PUT'],
                    // Allow only headers 'X-Wsse'
                    'Access-Control-Request-Headers' => ['X-Wsse'],
                    // Allow credentials (cookies, authorization headers, etc.) to be exposed to the browser
                    'Access-Control-Allow-Credentials' => true,
                    // Allow OPTIONS caching
                    'Access-Control-Max-Age' => 3600,
                    // Allow the X-Pagination-Current-Page header to be exposed to the browser.
                    'Access-Control-Expose-Headers' => ['X-Pagination-Current-Page'],
                ],
            ],*/

				'access' => [ 
						'class' => AccessControl::className (),
						'ruleConfig' => [ 

								'class' => AccessRule::className () 
						],
						'rules' => [ 
								[ 

										'actions' => [ 
                                                'index',
                                                //'verify-otp',
                                                'signup',
                                                'login',
                                                'my-profile',
                                                'update-profile',
                                                'add-temple-details',
                                                'add-temple-timings',
                                                'add-idols',
                                                'idol-list',
                                                'add-service',
                                                'my-services',
                                                'edit-idol',
                                                'edit-service',	
                                                'add-bank-details',
                                                'edit-bank-details',
                                                'my-orders',
                                                'check-temple'

										],

										'allow' => true,
										'roles' => [ 

												'@' 
										] 
								],

								[ 
										'actions' => [ 
                                                'index',
                                                'signup',
                                                'verify-otp',
                                                'login',
                                                'my-profile',
                                                'update-profile',
                                                'add-temple-details',
                                                'add-temple-timings',
                                                'add-idols',
                                                'idol-list',
                                                'add-service',
                                                'my-services',
                                                'edit-idol',
                                                'edit-service',
                                                'add-bank-details',
                                                'edit-bank-details',
                                                'my-orders',
                                                'check-temple'
												
										],

										'allow' => true,

										'roles' => [ 
												'?',
												'*',
												//'@' 

										] 

								] 

						] 

				] 

		] );

	}
    // [doc:end] behaviors

	

    // [doc:start] actionIndex - /api/manager/index - Index
    public function actionIndex()
    {
        $data = [];
        $data['details'] =  'dsdsadsa';
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionIndex

    // [doc:start] actionVerifyOtp - /api/manager/verify-otp - Verify Otp
    public function actionVerifyOtp()
    {
        $data = [];
        $post = Yii::$app->request->post();
      
        if (!empty($post)) {

            $contact_no = $post['contact_no'];
            $session_code = $post['session_code'];
            $otp_code = $post['otp_code'];

            $send_otp = Yii::$app->sms->verifyOtp($session_code, $otp_code);

            $send_otp = json_decode($send_otp, true);

            if ($send_otp['Status'] == 'Success') {

                $providerId = "phone";

                $number = $post['contact_no'];
                $auth_id = $post['contact_no'];

                $auth = Auth::find()->where([
                    'source' => $providerId,
                    'source_id' => $auth_id,
                ])->one();

                if ($auth) {
    
                    $user = $auth->user;
                    $user->device_token = ($post['device_token'] ?? '');
                    $user->device_type = ($post['device_type'] ?? 0);
                    Yii::$app->user->login($user);

                    $data['status'] = self::API_OK;
                    $data['details'] = $user;
                    $data['auth_code'] = AuthSession::newSession($user)->auth_code;
                } else {
                    //if($isNewUser == "true"){
                    $check = User::findOne(['contact_no' => $number]);
                    if (empty($check)) {
                        $model = new User();
                        $model->username = $number;
                        $model->contact_no = $number;
                        $model->device_token = ($post['device_token'] ?? '');
                        $model->device_type = ($post['device_type'] ?? 0);
                        $model->referal_code = '';
                        $model->user_role =  User::ROLE_VENDOR;

                        if ($model->validate()) {
                            // $model->roles = array($model->user_role);
                            if ($model->save()) {
                                $auth = new Auth();
                                $auth->user_id = $model->id;
                                $auth->source = $providerId;
                                $auth->source_id = $auth_id;
                                if ($auth->save(false)) {
                                    // //Find User 
                                    $user = $auth->user;
                                    $user->device_token = ($post['device_token'] ?? '');
                                    $user->device_type = ($post['device_type'] ?? 0);
                                    Yii::$app->user->login($user);

                                    $data['status'] = self::API_OK;
                                    $data['details'] = $user;
                                    $data['auth_code'] = AuthSession::newSession($user)->auth_code;

                                    $notification = new Notification();
                                    $notification->title = 'New Manager Registered';
                                    $notification->icon = 'fas fa-user';
                                    $notification->user_id = $model->id;
                                    //$notification->created_date = date('Y-m-d H:i:s');
                                    $notification->check_on_ajax = 0;
                                    $notification->module = '';
                                    $notification->model_type = get_class(new User());
                                    $notification->save(false);
                                } else {
                                    $data['status'] = self::API_NOK;
                                    $data['error'] = $auth->getErrors();
                                }
                            } else {
                                $data['status'] = self::API_NOK;
                                $data['error'] = $model->getErrors();
                            }
                        } else {
                            $data['status'] = self::API_NOK;
                            $data['error'] = $model->getErrors();
                        }
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = Yii::t("app", 'This number is already registered with us.Please Contact Support');
                    }
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t("app", "OTP failed");
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = Yii::t("app", "No  Data Posted");
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionVerifyOtp

    // [doc:start] actionSignup - /api/manager/signup - Signup
    public function actionSignup(){
        $data = [];
        $post = Yii::$app->request->post();
        // var_dump($post['User']);exit;
        $model = new UserForm();
        $model->on(User::EVENT_BEFORE_INSERT, [$model, 'generateAuthKey']);
        $email_identify = User::find()->where([
            'email' => $post['User']['username'],
        ])->one();
        // var_dump($email_identify); exit;
        if (empty($email_identify)) {
            $model->username = $post['User']['username'];
           // $model->email = $post['User']['username'];
            $model->password = $post['User']['password'];
            $model->passwordRepeat = $post['User']['password'];
           // $model->contact_no = $post['User']['contact_no'];
           $model->referal_code = (new CashbackTransaction())->getToken(6);
            $model->user_role = User::ROLE_VENDOR;

            if ($model->validate()) {
                $model->roles = array($model->user_role);
                if ($model->save()) {

                    $notification = new Notification();
                    $notification->title = 'New Manager Registered';
                    $notification->icon = 'fa fa-user';
                    $notification->user_id = $model->id;
                    $notification->module = '';
                    $notification->model_type = get_class(new User());
                    $notification->save(false);
                    $data['status'] = self::API_OK;
                    $data['details'] = $model;

                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = $model->getErrors();
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = $model->getErrors();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = "Username already exists.";
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionSignup

    // [doc:start] actionLogin - /api/manager/login - Login
    public function actionLogin()
    {
        $data = [];
        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post())) {

            $user_email = User::find()->where(['username' => $model->username])
            ->andWhere(['user_role' => User::ROLE_MANAGER])->andWhere(['status' => User::STATUS_ACTIVE])->one();
            if ($user_email) {
                if ($model->login()) {
                    $data['status'] = self::API_OK;
                    $data['auth_code'] = AuthSession::newSession($model)->auth_code;
                    $data['detail'] = $model;
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = 'Incorrect Password';
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = "Invalid merchant";
            }

        } else {
            $data['error'] = "No data posted.";
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionLogin

    // [doc:start] actionMyProfile - /api/manager/my-profile - My Profile
    public function actionMyProfile()
    {
        $data = [];
        $post = Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            $model = User::findOne($user_id);
            $d['user_details'] = $model->asJson();
            $data['status'] = SELF::API_OK;
            $data['details'] = $d;
        } else {
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "User Not Found");
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionMyProfile

    // [doc:start] actionUpdateProfile - /api/manager/update-profile - Update Profile
    public function actionUpdateProfile()
    {
        $data = [];
        $post = Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            if (!empty($post)) {
                $model = User::find()->where(['id' => $user_id])->andWhere(['user_role' => User::ROLE_VENDOR])->one();

                if (!empty($model)) {
                    $model->first_name =  $post['User']['full_name'];
                    if (!empty($post['User']['profile_image'])) {
                        $profile_image = $model->profileImage($post['User']['profile_image'], $model->first_name);
                        $model->profile_image = $profile_image;
                    }
                    if (!empty($post['User']['email'])) {
                        $model->email = $post['User']['email'];
                    }
                    $model->contact_no =  $post['User']['contact_no'];
                    $model->address = $post['User']['address'];
                    $model->date_of_birth = $post['User']['date_of_birth'];
                    $model->username = $model->email;
                    if (!empty($post['User']['id_proof'])) {
                        $id_proof = $model->profileImage($post['User']['id_proof'], $model->first_name);
                        $model->id_proof = $id_proof;
                    }

                    if ($model->update(false)) {
                        $data['status'] = self::API_OK;
                        $data['details'] = $model->asJson();
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = Yii::t("app", 'Something Went Wrong');
                    }
                } else {
                    $data['status'] = SELF::API_NOK;
                    $data['error'] = Yii::t("app", "User Not Found");
                }
            } else {
                $data['status'] = SELF::API_NOK;
                $data['error'] = Yii::t("app", "Data Not Posted");
            }
        } else {
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "User Not Found");
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionUpdateProfile

    //Check Temple
    // [doc:start] actionCheckTemple - /api/manager/check-temple - Check Temple
    public function actionCheckTemple(){
        $data = [];
        $post = Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            $temple = Temples::find()->where(['owner_id' => $user_id])->andWhere(['status' => Temples::STATE_ACTIVE])->one();
            if(!empty($temple)){
                $data['status'] = self::API_OK;
                        $data['error'] = Yii::t("app", 'Temple added'); 
            }else{
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t("app", 'No temple added yet');
            }
        }else{
            $data['status'] = self::API_NOK;
            $data['error'] = Yii::t("app", 'No user found');
        }
        return $this->sendJsonResponse($data);

    }
    // [doc:end] actionCheckTemple
    // [doc:start] actionAddTempleDetails - /api/manager/add-temple-details - Add Temple Details
    public function actionAddTempleDetails(){
        $data = [];
        $post = Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            $model = new Temples();
            $model->temple_name = $post['temple_name'];
            $model->street = $post['address'];
            $model->city = $post['district'];
            $model->state = $post['state'];
            $model->country_code = $post['country_code'];
            $model->post_code = $post['pincode'];
            $model->coordinates = $post['lat_lang'];
           // $List = expload(', ',  $model->coordinates);
           $str_arr = preg_split ("/\,/", $model->coordinates);  
           $model->lat = isset($str_arr[0])?$str_arr[0]:'';
           $model->lng = isset($str_arr[1])?$str_arr[1]:'';
           /* var_dump($model->coordinates); exit;
            $model->lat = $post['lat'];
            $model->lng = $post['lng'];
            $model->coordinates = $model->lat . ',' . $model->lng;*/
           // $model->kyc_doc = $post['kyc_doc'];
            if (!empty($post['temple_image'])) {
                $temple_image = Temples::templeImage($post['temple_image'], $model->temple_name);
                $model->image = $temple_image;
            }
            $model->description = $post['description'];
            $model->owner_id = $user_id;
            $model->status = 1;
            $model->is_open = 1;
            if($model->save()){
                $notification = new Notification();
                $notification->title = 'New Temple Registered';
                $notification->icon = 'fa fa-user';
                $notification->module = '';
                $notification->user_id = $user_id;
                $notification->model_type = get_class(new User());
                $notification->save(false);

                $data['status'] = SELF::API_OK;
                $data['details'] = $model->asJson();
            }else{
                $data['status'] = SELF::API_NOK;
                $data['error'] = $model->getErrors();
            }
        }else {
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "User Not Found");
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionAddTempleDetails
    
// [doc:start] actionAddTempleTimings - /api/manager/add-temple-timings - Add Temple Timings
public function actionAddTempleTimings(){
   \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
	$data = [];
	$post = Yii::$app->request->post();
	$headers = isset ( \Yii::$app->request->headers['auth_code'] ) ? \Yii::$app->request->headers ['auth_code'] : Yii::$app->request->getQueryParam ( 'auth_code' );
	$auth = new AuthSettings();
	$user_id = $auth->getAuthSession($headers);
	if (! empty ( $user_id )) {

		$json = file_get_contents('php://input');
       
		// Converts it into a PHP object
        //$data_json = json_decode($json, TRUE);
       
        $text['jsone_data'] = json_decode( preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $json), true );
		$json  = json_encode($text);
		$error = json_last_error();
		
		foreach($text['jsone_data'] as $jdata){
           $temple = Temples::find()->where(['owner_id' => $user_id])->andWhere(['status' => Temples::STATE_ACTIVE])->one();
			if(!empty($temple)){
                $model = new TempleTimings();
                $model->temple_id = $temple['id'];
                $model->day = $jdata['day'];
                $model->opening_time = $jdata['opening_time'];
                $model->closing_time = $jdata['closing_time'];
                $model->status = 1;
                if(!$model->save(false)){
                    $data['status'] = SELF::API_NOK;
                    $data['error'] = $model->getErrors();
                }else{
                    $data['status'] = SELF::API_OK;
                    $data['details'] = Yii::t("app", "Data Saved");
                }
            }else{
                $data['status'] = SELF::API_NOK;
                $data['error'] = Yii::t("app", "Temple Not Found");
            }

        }
    }else {
        $data['status'] = SELF::API_NOK;
        $data['error'] = Yii::t("app", "User Not Found");
    }

        return $this->sendJsonResponse($data);
    }
// [doc:end] actionAddTempleTimings


    // [doc:start] actionAddIdols - /api/manager/add-idols - Add Idols
    public function actionAddIdols(){
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $data = [];
        $post = Yii::$app->request->post();
        $headers = isset ( \Yii::$app->request->headers['auth_code'] ) ? \Yii::$app->request->headers ['auth_code'] : Yii::$app->request->getQueryParam ( 'auth_code' );
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (! empty ( $user_id )) {
    
            $json = file_get_contents('php://input');
           
            // Converts it into a PHP object
            //$data_json = json_decode($json, TRUE);
           
            $text['jsone_data'] = json_decode( preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $json), true );
            $json  = json_encode($text);
            $error = json_last_error();
            
            foreach($text['jsone_data'] as $jdata){
                //var_dump($jdata); exit;
               $temple = Temples::find()->where(['owner_id' => $user_id])->andWhere(['status' => Temples::STATE_ACTIVE])->one();
                if(!empty($temple)){
                    $model = new Category();
                    $model->title = $jdata['idol_name'];
                    $model->slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $model['title']));
                    if (!empty($jdata['idol_image'])) {
                        $idol_image = Category::catImage($jdata['idol_image'], $model->title);
                        //var_dump( $idol_image); exit;
                        $model->image = $idol_image;
                    }
                    $model->status = 1;
                    if($model->save(false)){
                        $temple_category = new TempleCategories();
                        $temple_category->temple_id = $temple['id'];
                        $temple_category->category_id = $model->id;
                        $temple_category->status = 1;
                        $temple_category->save(false);
                        $data['status'] = SELF::API_OK;
                        $data['details'] = Yii::t("app", "Data Saved");  
                    }else{
                        $data['status'] = SELF::API_NOK;
                        $data['error'] = $model->getErrors();
                    }
                }else{
                    $data['status'] = SELF::API_NOK;
                    $data['error'] = Yii::t("app", "Temple Not Found");
                }
    
            }
        }else {
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "User Not Found");
        }
    
            return $this->sendJsonResponse($data);

    }
    // [doc:end] actionAddIdols

 
    // [doc:start] actionIdolList - /api/manager/idol-list - Idol List
    public function actionIdolList(){
        $data = [];
        $post = Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            $temple = Temples::find()->where(['owner_id' => $user_id])->andWhere(['status' => Temples::STATE_ACTIVE])->one();
            if(!empty($temple)){
            $category = Category::find()->joinWith(['templeCategories as tc'])->where(['tc.temple_id' => $temple->id])->andWhere(['category.status' => 1])->all();
            if(!empty($category)){
                foreach($category as $cat ){
                    $list[] = $cat->asJson();
                }
                $data['status'] = SELF::API_OK;
                $data['details'] = $list;
            }else{
                $data['status'] = SELF::API_NOK;
                $data['error'] = Yii::t("app", "Category Not Found");
            }
            
        }else{
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "Temple Not Found");
        }
        }else {   
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "User Not Found");
        }

        return $this->sendJsonResponse($data);

    }
    // [doc:end] actionIdolList
    // [doc:start] actionAddService - /api/manager/add-service - Add Service
    public function actionAddService(){
        $data = [];
        $post = Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            $temple = Temples::find()->where(['owner_id' => $user_id])->andWhere(['status' => Temples::STATE_ACTIVE])->one();
            if(!empty($temple)){
            $model = new PujaServices();
            $model->title = $post['title'];
            $model->temple_id = $temple['id'];
            $model->category_id = $post['idol_category'];
            $model->type_id = $post['pooja_catrgory'];
            $model->service_date = $post['service_date'];
            $model->day_id = $post['day_id'];
            $model->max_booking = $post['daily_booking'];
            $model->original_price = $post['price'];
            $model->description = $post['benefits'];
            $model->status = 1;
            if($model->save(false)){
                $data['status'] = SELF::API_OK;
                $data['details'] = $model->asJson();
            }else{
                $data['status'] = SELF::API_NOK;
                $data['error'] = $model->getErrors();
            }
        }else{
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "Temple Not Found");
        }
        }else {   
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "User Not Found");
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionAddService

    // [doc:start] actionMyServices - /api/manager/my-services - My Services
    public function actionMyServices(){
        $data = [];
        //$post = Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            $temple = Temples::find()->where(['owner_id' => $user_id])->andWhere(['status' => Temples::STATE_ACTIVE])->one();
            if(!empty($temple)){
                $services = PujaServices::find()->where(['temple_id' => $temple['id']])->all();
                if(!empty($services)){
                    foreach($services as $service){
                        $list[] = $service->asJson();
                    }
                    $data['status'] = SELF::API_OK;
                    $data['details'] = $list;
                }else{
                 $data['status'] = SELF::API_NOK;
                 $data['error'] = Yii::t("app", "Services Not Found");
                }
            }else{
                $data['status'] = SELF::API_NOK;
                $data['error'] = Yii::t("app", "Temple Not Found");
            }
        }else {
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "User Not Found");
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionMyServices

    // [doc:start] actionEditIdol - /api/manager/edit-idol - Edit Idol
    public function actionEditIdol(){
        $data = [];
        $post = Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            $category = Category::find()->where(['id' => $post['idol_id']])->one();
            if(!empty($category)){
                $oldImage = $category['image'];
                $category->title = $post['idol_name'];
                if (!empty($post['idol_image'])) {
                    $idol_image = Category::catImage($post['idol_image'], $category->title);
                    $category->image = $idol_image;
                }else{
                    $category->image =  $oldImage ;
                }
                if($category->save(false)){
                    $data['status'] = SELF::API_OK;
                    $data['details'] = $category->asJson();
                }else{
                    $data['status'] = SELF::API_NOK;
                    $data['error'] = $category->getErrors();
                }
                
            }else {
                $data['status'] = SELF::API_NOK;
                $data['error'] = Yii::t("app", "Idol Not Found");
            }
    
            return $this->sendJsonResponse($data);
        }else {
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "User Not Found");
        }

        return $this->sendJsonResponse($data);

    }
    // [doc:end] actionEditIdol

    // [doc:start] actionEditService - /api/manager/edit-service - Edit Service
    public function actionEditService(){
        $data = [];
        $post = Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            $temple = Temples::find()->where(['owner_id' => $user_id])->andWhere(['status' => Temples::STATE_ACTIVE])->one();
            if(!empty($temple)){
            $model = PujaServices::find()->where(['id'=>$post['service_id']])->andWhere(['temple_id' => $temple['id']])->one();
            if(!empty($model)){
                $model->title = $post['title'];
                $model->category_id = $post['idol_category'];
                $model->type_id = $post['pooja_catrgory'];
                $model->service_date = $post['service_date'];
                $model->day_id = $post['day_id'];
                $model->max_booking = $post['daily_booking'];
                $model->original_price = $post['price'];
                $model->description = $post['benefits'];
                $model->status = 1;
                if($model->save(false)){
                    $data['status'] = SELF::API_OK;
                    $data['details'] = $model->asJson();
                }else{
                    $data['status'] = SELF::API_NOK;
                    $data['error'] = $model->getErrors();
                }
            }else{
                $data['status'] = SELF::API_NOK;
                $data['error'] = Yii::t("app", "Service Not Found");
            }
           
        }else{
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "Temple Not Found");
        }
        }else {   
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "User Not Found");
        }

        return $this->sendJsonResponse($data);

    }
    // [doc:end] actionEditService

    // [doc:start] actionAddBankDetails - /api/manager/add-bank-details - Add Bank Details
    public function actionAddBankDetails(){
        $data = [];
        $post = Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            $model = new BankDetails();
            $model->user_id = $user_id;
            $model->full_name = $post['full_name'];
            $model->account_no = $post['account_no'];
            $model->ifsc_code = $post['ifsc_code'];
            $model->status = 1;
            if($model->save()){
                $data['status'] = SELF::API_OK;
                $data['details'] = $model->asJson();
            }else{
                $data['status'] = SELF::API_NOK;
                $data['error'] = $model->getErrors();
            }
        }else {   
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "User Not Found");
        }

        return $this->sendJsonResponse($data);

    }
    // [doc:end] actionAddBankDetails

    // [doc:start] actionEditBankDetails - /api/manager/edit-bank-details - Edit Bank Details
    public function actionEditBankDetails(){
        $data = [];
        $post = Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            $model =  BankDetails::find()->where(['id' => $post['account_id']])->one();
            $model->full_name = $post['full_name'];
            $model->account_no = $post['account_no'];
            $model->ifsc_code = $post['ifsc_code'];
            if($model->update()){
                $data['status'] = SELF::API_OK;
                $data['details'] = $model->asJson();
            }else{
                $data['status'] = SELF::API_NOK;
                $data['error'] = $model->getErrors();
            }
        }else {   
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "User Not Found");
        }

        return $this->sendJsonResponse($data);

    }
    // [doc:end] actionEditBankDetails

     // Temple Orders
     // [doc:start] actionMyOrders - /api/manager/my-orders - My Orders
     public function actionMyOrders()
     {
         $data = [];
         $headers = isset(\Yii::$app->request->headers['auth_code']) ? \Yii::$app->request->headers['auth_code'] : Yii::$app->request->getQueryParam('auth_code');
         $auth = new AuthSettings();
         $post = Yii::$app->request->post();
         $today = Date('Y-m-d');
         $user_id = $auth->getAuthSession($headers);
         if (!empty($user_id)) {
             $temple = Temples::find()->select('id')->Where(['owner_id' => $user_id]);
             $page = isset($post['page']) ? $post['page'] : 0;
             if (empty($post['status'])) {
 
                 $query = Orders::find()->andWhere(['orders.status' => Orders::STATUS_ORDERED])
                     ->andWhere(['in', 'temple_id', $temple]);
             } else {
                     $query = Orders::find()->andWhere(['orders.status' => (int) $post['status']])
                     ->andWhere(['in','temple_id',$temple]);
             }
             //    echo $query->createCommand()->getRawSql();exit;
             $temple_orders = new ActiveDataProvider([
                 'query' => $query,
                 'sort' => [
                     'defaultOrder' => [
                          'id' => SORT_DESC,
                        // 'delivery_date' => SORT_ASC,
                     ],
                 ],
                 'pagination' => array(
                     'page' => $page,
                     'pageSize' => 20,
                 ),
             ]);
             if (!empty($temple_orders->getModels())) {
                 foreach ($temple_orders->getModels() as $temple_orders) {
 
                     $list[]['order_details'] = $temple_orders->asJson();
                 }
                 $data['status'] = self::API_OK;
                 $data['details'] = $list;
             } else {
                 $data['status'] = self::API_NOK;
                 $data['error'] = "No Orders found";
             }
         }else {   
            $data['status'] = SELF::API_NOK;
            $data['error'] = Yii::t("app", "User Not Found");
        }

         return $this->sendJsonResponse($data);
     }
     // [doc:end] actionMyOrders
 

}