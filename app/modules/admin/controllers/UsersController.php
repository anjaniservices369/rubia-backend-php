<?php

namespace app\modules\admin\controllers;

use yii\web\ForbiddenHttpException;
use app\models\User;
use app\modules\admin\forms\UserForm;
use app\modules\admin\models\base\CashbackTransaction;
use app\modules\admin\models\UserSearch;
use app\traits\controllers\FindModelOrFail;
use Yii;
use yii\filters\VerbFilter;
use yii\base\Response;
use yii\widgets\ActiveForm;
use yii\data\ActiveDataProvider;
use app\modules\admin\forms\UserPassword;
use app\models\City;
use yii\helpers\Json;
use app\models\UserEmergencyContacts;
use app\modules\admin\models\base\DriverBankDetails;
use app\modules\admin\forms\BankDetailsForm;

use app\models\UploadForm;
use yii\web\UploadedFile;
use app\modules\admin\models\DriverPanCards;
use yii\helpers\Url;
use app\modules\admin\models\DriverAadharCard;
use app\modules\admin\forms\AadharDetailsForm;
use app\modules\admin\forms\DrivingLicenseForm;
use app\modules\admin\models\DrivingLicense;

use app\modules\admin\models\DriverWallet;
use app\modules\admin\models\DriverReferralEarnings;
use app\modules\admin\models\DriverVehicleTypes;


/**
 * UsersController implements the CRUD actions for User model.
 */
class UsersController extends Controller
{
	use FindModelOrFail;
	
	// [doc:start] init - Init
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		$this->modelClass = UserForm::class;
	}
	// [doc:end] init
	
	/**
	 * @inheritdoc
	 */

	/**
	 * Actions that read or change one specific user (the id comes from the query string or the form).
	 * Every one of them is authorized against User::canManage() before it runs, so e.g. a city franchise
	 * cannot touch accounts of another city or reset an administrator's password.
	 */
	private static $targetsUser = [
		'view', 'driver-view', 'update', 'delete', 'update-status', 'status-change', 'user-password-update',
		'user-bank-update', 'user-bank-view', 'user-verify-profile', 'vehicle-edit', 'admin-per-set',
		'user-pan-update', 'user-aadhar-update', 'user-license-update', 'referer-list',
	];

	public function beforeAction($action)
	{
		if (!parent::beforeAction($action)) {
			return false;
		}

		if (in_array($action->id, self::$targetsUser, true)) {
			$request = Yii::$app->request;
			$id = $request->get('id', $request->post('id', $request->post('user_id')));
			if ($id !== null && $id !== '') {
				$target = User::findOne($id);
				if ($target !== null && !User::canManage(Yii::$app->user->identity, $target)) {
					throw new ForbiddenHttpException('You are not allowed to manage this account.');
				}
			}
		}

		return true;
	}

	 // [doc:start] behaviors - Behaviors
	 public function behaviors()
	 {
		 return [
			 'verbs' => [
				 'class' => VerbFilter::className(),
				 'actions' => [
					 'delete' => ['post'],
				 ],
			 ],
			 'access' => [
				 'class' => \yii\filters\AccessControl::className(),
				 'rules' => [
					 [
						 'allow' => true,
						 'actions' => ['index', 'view', 'create', 'update', 'delete', 'update-status','status-change','drivers','driver-view','driver-profile','user-profile','user-verify-profile','vehicle-edit','admin-per-set','taxi','taxi-amount','franchise','create-franchise'],
						 'matchCallback' => function () {
							 return User::isAdmin() || User::isSubAdmin();
						 }
						
					 ], 
					 [
						 'allow' => true,
						 'actions' => ['index', 'view', 'update', 'pdf', 'update-status','status-change','drivers','driver-view','driver-profile','user-profile','user-verify-profile','vehicle-edit','admin-per-set','taxi','taxi-amount','franchise','create-franchise'],
						 'matchCallback' => function () {
							 return User::isManager();
						 }
					 ],
					 [
						 'allow' => true,
						 'actions' => ['drivers','taxi-amount','driver-profile'],
						 'matchCallback' => function () {
							 return User::isFranchise();
						 }
					 ],

					 [
						'allow' => true,
						'actions' => [ 'user-password-update'], // Include 'delete' action here
						'roles' => ['@'], // Allow any authenticated user
					],
					[
						'allow' => true,
						'actions' => ['user-password-update'],
						'matchCallback' => function () {
							return User::isAdmin() || User::isSubAdmin() || User::isManager();
						},
					],
					[
						'allow' => true,
						'actions' => ['user-password-update'],
						'matchCallback' => function () {
							return User::isFranchise();
						},
					],
                   
                   
                   [
						'allow' => true,
						'actions' => ['user-bank-update'],
						'matchCallback' => function () {
							return User::isAdmin() || User::isSubAdmin() || User::isManager();						},
					],

					[
						'allow' => true,
						'actions' => ['user-bank-view'],
						'matchCallback' => function () {
							return User::isAdmin() || User::isSubAdmin() || User::isManager();						},
					],
					[
						'allow' => true,
						'actions' => ['user-pan-update'],
						'matchCallback' => function () {
							return User::isAdmin() || User::isSubAdmin() || User::isManager();				
						},
					],

					[
						'allow' => true,
						'actions' => ['user-license-update'],
						'matchCallback' => function () {
							return User::isAdmin() || User::isSubAdmin() || User::isManager();				
						},
					],

					[
						'allow' => true,
						'actions' => ['user-aadhar-update'],
						'matchCallback' => function () {
							return User::isAdmin() || User::isSubAdmin() || User::isManager();				
						},
					],



					 [
						 'allow' => false
					 ]
				 ]
			 ]
		 ];
	 }
	 // [doc:end] behaviors
  
  
  
  
   // [doc:start] actionUserBankUpdate - /admin/users/user-bank-update - User Bank Update
   public function actionUserBankUpdate($id)
	 {
		 $model = $this->findUserModel($id);
		 $bankDetailsForm = new BankDetailsForm();
		 $bankDetailsModel = $model->getDriverBankDetails()->one();
	 
		 // Load the existing bank details if available
		 if ($bankDetailsModel) {
			 $bankDetailsForm->setAttributes($bankDetailsModel->attributes);
		 }
	 
		 $request = Yii::$app->request;
	 
		 if ($request->isPost) {
			 $bankDetailsForm->load($request->post());
			 
	 
			 if ($bankDetailsForm->validate()) {
			 
				 $transaction = Yii::$app->db->beginTransaction();
	 
				 try {
					 if (!$bankDetailsModel) {
						 $bankDetailsModel = new DriverBankDetails();
						 $bankDetailsModel->create_user_id = $model->id;
					 }
	 
					 // Set branch_name first
					 $bankDetailsModel->branch_name = $bankDetailsForm->branch_name;
	 
					 // Update the DriverBankDetails model with form attributes
					 $bankDetailsModel->setAttributes($bankDetailsForm->attributes);
					
				
					
	                   
					 if ($bankDetailsModel->save()) {
					     
						 $transaction->commit();
						 // Redirect to the appropriate view page after a successful save
						 return $this->redirect(['user-bank-view', 'id' => $model->id]);
	 
					 } else {
					     
						 $transaction->rollBack();
						 Yii::error($bankDetailsModel->errors, __METHOD__);
						
					 }
				 } catch (\Exception $e) {
					 $transaction->rollBack();
					 Yii::error($e->getMessage(), __METHOD__);
				 }
			 } else {
				 Yii::error($bankDetailsForm->errors, __METHOD__);
			 }
		 }
	 
		 // Render the update form view
		 return $this->render('user_bank_update', ['model' => $model, 'bankDetailsForm' => $bankDetailsForm]);
	 }
   // [doc:end] actionUserBankUpdate
	 

	
// [doc:start] actionUserBankView - /admin/users/user-bank-view - User Bank View
public function actionUserBankView($id)
{
    $model = $this->findUserModel($id);
    $bankDetailsModel = $model->getDriverBankDetails()->one();
    if ($bankDetailsModel === null) {
        // Nothing to show yet: go to the form where the details can be entered.
        return $this->redirect(['user-bank-update', 'id' => $model->id]);
    }

    return $this->render('view_user_bank', [
        'model' => $model,
        'bankDetailsModel' => $bankDetailsModel,
    ]);
}
// [doc:end] actionUserBankView





  
  
	
	// [doc:start] actionUserPasswordUpdate - /admin/users/user-password-update - User Password Update
	public function actionUserPasswordUpdate($id)
	{
		$model = $this->findUserModel($id);
		$userFormClass = (Yii::$app->user->identity->user_role == User::ROLE_ADMIN) ? UserPassword::class : UserForm::class;
		$userForm = new $userFormClass(['scenario' => 'update']);
		$userForm->setAttributes($model->attributes);
		$request = Yii::$app->request;
		if ($request->isPost) {
			$userForm->load($request->post());
			if ($userForm->validate()) {
				$model->setAttributes($userForm->attributes);
				if ($userForm->password) {
					$model->setPassword($userForm->password);
				}
				if ($model->save()) {
					return $this->render('view_user_password', ['model' => $model]);
				}
				$userForm->addErrors($model->errors);
			}
		}

		return $this->render('user_password_update', ['model' => $userForm]);
	}
	// [doc:end] actionUserPasswordUpdate


	// public function actionView($id) {
	// 	$model = User::find()->where(['id' => $id])->one();
	// 	return $this->render('view_old',['model' => $model]);

	// }

    protected function findUserModel($id)
{
    if ($id !== null && ($model = User::findOne($id)) !== null) {
        return $model;
    } else {
        throw new NotFoundHttpException('The requested page does not exist.');
    }
}

	// public function actionView($id) {
	// 	$model = User::find()->where(['id' => $id])->one();
	// 	return $this->render('view_old',['model' => $model]);

	// }

	 //start now change status

	//  public function actionUser_Password_Update($id)
	//  {
	// 	 $model = UserPassword::findOne($id);
	// 	echo"khaja";
	// 	exit;
	// 	 if (!$model) {
	// 		 throw new NotFoundHttpException('User not found.');
	// 	 }
	 
	// 	 if ($model->load(Yii::$app->request->post()) && $model->validate()) {
	// 		 // Validate and save the new password
	// 		 $user = User::findOne($id);
	// 		 $user->setPassword($model->newPassword);
	// 		 $user->save();
	 
	// 		 Yii::$app->session->setFlash('success', 'Password updated successfully.');
	// 		 return $this->redirect(['view', 'id' => $id]); // Redirect to the user profile page
	// 	 }
	 
	// 	 return $this->render('user_password_update', [
	// 		 'model' => $model,
	// 	 ]);
	//  }
	 
	// public function actionUser_Password_Update($id)
	// {
	// 	$model = UserForm::findOne($id);
	
	// 	if (!$model) {
	// 		throw new NotFoundHttpException('User not found.');
	// 	}
	
	// 	if ($model->load(Yii::$app->request->post()) && $model->validate()) {
	// 		$user = User::findOne($id);
	// 		$user->setPassword($model->password);
	// 		$user->save();
	
	// 		Yii::$app->session->setFlash('success', 'Password updated successfully.');
	// 		return $this->redirect(['view', 'id' => $id]);
	// 	}
	
	// 	return $this->render('user_password_update', [
	// 		'model' => $model,
	// 	]);
	// }
	


	/**
	 * Blocking takes effect at once: the API refuses the account's sessions (AuthSettings), a blocked driver goes
	 * offline and stops receiving offers, and a blocked customer's bookings that no driver has taken are cancelled.
	 * A ride already in progress is left for the administrator to finish or cancel from the booking page.
	 */
	private function releaseBlockedUser(User $user)
	{
		$now = date('Y-m-d H:i:s');
		if ($user->user_role === User::ROLE_DRIVER || $user->user_role === User::ROLE_TAXI) {
			$user->online_status = User::online_status_no;
			$user->save(false, ['online_status']);
			\app\modules\admin\models\base\DriverRequests::updateAll(
				['status' => \app\modules\admin\models\base\DriverRequests::STATUS_BOOKING_AUTO_CANCELLED, 'updated_on' => $now],
				['driver_id' => $user->id, 'status' => \app\modules\admin\models\base\DriverRequests::STATUS_WAITING_FOR_DRIVER]
			);
		} elseif ($user->user_role === User::ROLE_USER) {
			\app\modules\admin\models\base\Booking::updateAll(
				['status' => \app\modules\admin\models\base\Booking::STATUS_CANCELLED, 'cancel_reason' => 'Cancelled by admin: account blocked', 'updated_on' => $now],
				['user_id' => $user->id, 'status' => \app\modules\admin\models\base\Booking::STATUS_WAITING_FOR_DRIVER]
			);
		}
	}

	// [doc:start] actionStatusChange - /admin/users/status-change - Status Change
	public function actionStatusChange()
	{
		$result = $this->changeStatus(Yii::$app->request->post());

		return $result['message'] === 'Updated';
	}
	// [doc:end] actionStatusChange

 
 ///end now change status



 // [doc:start] actionTaxiAmount - /admin/users/taxi-amount - Taxi Amount
 public function actionTaxiAmount()
 {
	$vehicle_id = Yii::$app->request->get('vehicle_id');
	 $searchModel = new UserSearch();
		if(\Yii::$app->user->identity->user_role == User::ROLE_ADMIN)
		{
			$dataProvider = $searchModel->search(Yii::$app->request->queryParams, User::ROLE_TAXI);
		}elseif(\Yii::$app->user->identity->user_role == User::ROLE_FRANCHISE)
		{
			$dataProvider = $searchModel->searchmanager(Yii::$app->request->queryParams, User::ROLE_TAXI,'','',\Yii::$app->user->identity->city_id,$vehicle_id);
		}
	 \Yii::$app->view->title = "Dashboard";

	 return $this->render('taxi_amount', [
		 'searchModel'  => $searchModel,
		 'dataProvider' => $dataProvider,
	 ]);
 }
 // [doc:end] actionTaxiAmount

 



	// [doc:start] actionIndex - /admin/users/index - Lists all User models.
	/**
	 * Lists all User models.
	 *
	 * @return mixed	
	 */
	public function actionIndex()
	{
		$searchModel = new UserSearch();
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams,User::ROLE_USER,User::TYPE_PERSONAL);
		// Customers-profile: arranged by id unless the admin picks a different column to sort by.
		$dataProvider->sort->defaultOrder = ['id' => SORT_ASC];
		return $this->render('index', [
			'searchModel'  => $searchModel,
			'dataProvider' => $dataProvider,
		]);
	}
	// [doc:end] actionIndex
	
	// [doc:start] actionFranchise - /admin/users/franchise - Franchise
	public function actionFranchise()
	{
		$searchModel = new UserSearch();
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams,User::ROLE_FRANCHISE);
		return $this->render('franchise', [
			'searchModel'  => $searchModel,
			'dataProvider' => $dataProvider,
		]);
	}
	// [doc:end] actionFranchise
// [doc:start] actionDrivers - /admin/users/drivers - Drivers
public function actionDrivers($status = User::STATUS_ACTIVE, $profile_verified = null)
{
    $searchModel = new UserSearch();
    $searchModel->status = $status;
    $searchModel->profile_verified = $profile_verified;

    if (\Yii::$app->user->identity->user_role == User::ROLE_ADMIN) {
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, User::ROLE_DRIVER);
    } elseif (\Yii::$app->user->identity->user_role == User::ROLE_FRANCHISE) {
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, User::ROLE_DRIVER, '', '', \Yii::$app->user->identity->city_id, $status);
    }

    // Add sort configuration to order by creation date in descending order
    $dataProvider->sort = [
        'defaultOrder' => ['created_at' => SORT_DESC], // Assuming 'created_at' is the attribute representing the creation date
    ];
  
  $dataProvider->sort = [
        'defaultOrder' => ['joining_date' => SORT_DESC], // Assuming 'joining_date' is the attribute representing the joining date
    ];

    return $this->render('driver', [
        'searchModel' => $searchModel,
        'dataProvider' => $dataProvider,
        'status' => $status,
        'profile_verified' => $profile_verified,
    ]);
}
// [doc:end] actionDrivers


	// [doc:start] actionTaxi - /admin/users/taxi - Taxi
	public function actionTaxi(){
		$searchModel = new UserSearch();
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams,User::ROLE_TAXI);
		return $this->render('taxi', [
			'searchModel'  => $searchModel,
			'dataProvider' => $dataProvider,
		]);

	}
	// [doc:end] actionTaxi
  



	// [doc:start] actionBusiness - /admin/users/business - Business
	public function actionBusiness()
	{
		$searchModel = new UserSearch();
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams,User::ROLE_USER,User::TYPE_BUSINESS);
		return $this->render('business_user', [
			'searchModel'  => $searchModel,
			'dataProvider' => $dataProvider,
		]);
	}
	// [doc:end] actionBusiness

	// [doc:start] actionVendor - /admin/users/vendor - Vendor
	public function actionVendor()
	{
		$searchModel = new UserSearch();
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams,User::ROLE_VENDOR);
		return $this->render('vendor', [
			'searchModel'  => $searchModel,
			'dataProvider' => $dataProvider,
		]);
	}
	// [doc:end] actionVendor

	// [doc:start] actionSubAdmin - /admin/users/sub-admin - Sub Admin
	public function actionSubAdmin(){
		$searchModel = new UserSearch();
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams,User::ROLE_SUBADMIN);
		return $this->render('index', [
			'searchModel'  => $searchModel,
			'dataProvider' => $dataProvider,
		]);

	} 
	// [doc:end] actionSubAdmin

	// [doc:start] actionManagers - /admin/users/managers - Managers
	public function actionManagers(){
		$searchModel = new UserSearch();
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams,User::ROLE_VENDOR);
		return $this->render('index', [
			'searchModel'  => $searchModel,
			'dataProvider' => $dataProvider,
		]);

	}
	// [doc:end] actionManagers

	// [doc:start] actionSubscribeUser - /admin/users/subscribe-user - Subscribe User
	public function actionSubscribeUser(){
		$searchModel = new UserSearch();
		$dataProvider = $searchModel->SubscribedUserSearch(Yii::$app->request->queryParams,User::ROLE_USER);
		return $this->render('index', [
			'searchModel'  => $searchModel,
			'dataProvider' => $dataProvider,
		]);

	}
	// [doc:end] actionSubscribeUser
	// [doc:start] actionRefererList - /admin/users/referer-list - Referer List
	public function actionRefererList($id) {
		$searchModel = new UserSearch ();
		\Yii::$app->view->title = "Referal List";
		$dataProvider = $searchModel->usersearch ( Yii::$app->request->queryParams, $id );
		return $this->render ( 'index', [ 
				'searchModel' => $searchModel,
				'dataProvider' => $dataProvider 
		] );
	}
	// [doc:end] actionRefererList
	
	/**
	 * Creates a new User model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 *
	 * @return mixed
	 */
	/**
	 * Fails unless the signed-in user may give an account this role (see User::assignableRoles()).
	 */
	private function assertRoleAllowed($role)
	{
		if ($role === null || $role === '') {
			return;
		}
		if (!in_array($role, User::assignableRoles(Yii::$app->user->identity), true)) {
			throw new ForbiddenHttpException('You are not allowed to assign this role.');
		}
	}

	// [doc:start] actionCreate - /admin/users/create - Create
	public function actionCreate()
	{
		$model = new User();
		$model->on(User::EVENT_BEFORE_INSERT, [$model, 'generateAuthKey']);
		if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post())) {
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return ActiveForm::validate($model);
		}
		if ($model->load(Yii::$app->request->post())) {
			$this->assertRoleAllowed($model->user_role);
			if ($model->save()) {
				return $this->redirect(['view', 'id' => $model->id]);
			}
		}

		return $this->render('create', [
			'model' => $model,
		]);
	}
	// [doc:end] actionCreate

	
	///////// Franchise create
	// [doc:start] actionCreateFranchise - /admin/users/create-franchise - Create Franchise
	public function actionCreateFranchise()
	{
		$model = new UserForm();
		$model->on(User::EVENT_BEFORE_INSERT, [$model, 'generateAuthKey']);
		if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post())) {
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return ActiveForm::validate($model);
		}
		if ($model->load(Yii::$app->request->post())) {
			// This screen only creates city franchise accounts, whatever the form posted.
			$model->user_role = User::ROLE_FRANCHISE;
			$this->assertRoleAllowed($model->user_role);
			if ($model->save()) {
				return $this->redirect(['users/franchise']);
			}
		}

		return $this->render('franchisecreate', [
			'model' => $model,
		]);
	}
	// [doc:end] actionCreateFranchise


	
	// [doc:start] actionUpdate - /admin/users/update - Updates an existing User model.
	/**
	 * Updates an existing User model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 *
	 * @param integer $id
	 *
	 * @return mixed
	 */
	public function actionUpdate($id)
	{
		$model = $this->findModel($id);
		$request = Yii::$app->request;
		if ($request->isPost && $model->load($request->post())) {
			// Changing the role needs the right to hand out the new one.
			if ($model->isAttributeChanged('user_role')) {
				$this->assertRoleAllowed($model->user_role);
			}
			if ($model->save(false)) {
				return $this->redirect(['view', 'id' => $model->id]);
			}
		}

		return $this->render('update', [
			'model' => $model,
		]);
	}
	// [doc:end] actionUpdate

	// [doc:start] actionView - /admin/users/view - View
	public function actionView($id) {
		$model = User::find()->where(['id' => $id])->one();
		return $this->render('view_old',['model' => $model]);

	}
	// [doc:end] actionView


	// [doc:start] actionUserProfile - /admin/users/user-profile - A customer's whole profile as entered in the customer app: personal details (first and last name are mandatory), emergency.
	/**
	 * A customer's whole profile as entered in the customer app: personal details (first and last name are mandatory), emergency
	 * contacts and saved places, with the ride summary. Posting a form saves that section (field "section"), see CustomerProfile::save.
	 */
	public function actionUserProfile($id)
	{
		$data = \app\modules\admin\models\CustomerProfile::load($id);
		if ($data === null) {
			throw new \yii\web\NotFoundHttpException('There is no such customer.');
		}
		if (Yii::$app->request->isPost) {
			$section = (string) Yii::$app->request->post('section');
			[$ok, $message] = \app\modules\admin\models\CustomerProfile::save($id, $section, Yii::$app->request->post(), (int) Yii::$app->user->id);
			Yii::$app->session->setFlash($ok ? 'success' : 'error', $message);

			return $this->redirect(['user-profile', 'id' => (int) $id, '#' => $section]);
		}

		return $this->render('user_profile', $data);
	}
	// [doc:end] actionUserProfile

	// [doc:start] actionDriverProfile - /admin/users/driver-profile - A driver's whole profile as entered in the partner app: personal details, driving licence, Aadhaar, PAN, bank details, emergency.
	/**
	 * A driver's whole profile as entered in the partner app: personal details, driving licence, Aadhaar, PAN, bank details, emergency
	 * contacts, vehicle types and the police verification flag, with the onboarding checklist. Posting a form saves that section
	 * (field "section"), see DriverProfile::save.
	 */
	public function actionDriverProfile($id)
	{
		$data = \app\modules\admin\models\DriverProfile::load($id);
		if ($data === null) {
			throw new \yii\web\NotFoundHttpException('There is no such driver.');
		}
		// a city franchise may look at the drivers of its own city, but not change them
		$readonly = false;
		if (User::isFranchise()) {
			$mine = \app\modules\admin\models\UserSearch::inCity((int) Yii::$app->user->identity->city_id);
			if (!User::find()->where(['user.id' => (int) $id])->andWhere($mine)->exists()) {
				throw new ForbiddenHttpException('This driver does not belong to your city.');
			}
			$readonly = true;
		}
		$data['readonly'] = $readonly;
		if (Yii::$app->request->isPost) {
			if ($readonly) {
				throw new ForbiddenHttpException('A franchise cannot change a profile.');
			}
			$section = (string) Yii::$app->request->post('section');
			[$ok, $message] = \app\modules\admin\models\DriverProfile::save($id, $section, Yii::$app->request->post(), (int) Yii::$app->user->id);
			Yii::$app->session->setFlash($ok ? 'success' : 'error', $message);

			return $this->redirect(['driver-profile', 'id' => (int) $id, '#' => $section]);
		}

		return $this->render('driver_profile', $data);
	}
	// [doc:end] actionDriverProfile

	// [doc:start] actionDriverView - /admin/users/driver-view - Driver View
	public function actionDriverView($id) {
		$model = User::find()->where(['id' => $id])->one();
		return $this->render('driver_view',['model' => $model]);

	}
	// [doc:end] actionDriverView



	
	/**
	 * Deletes an existing User model.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 *
	 * @param integer $id
	 *
	 * @return mixed
	 */
	// [doc:start] actionDelete - /admin/users/delete - Removes an account by marking it deleted. The row is kept, so the rides, wallet entries, ratings and.
	/**
	 * Removes an account by marking it deleted. The row is kept, so the rides, wallet entries, ratings and
	 * notifications that reference it stay valid. A deleted account can no longer sign in.
	 */
	public function actionDelete($id)
	{
		$target = $this->findUserModel($id);
		if ((int) $target->id === (int) Yii::$app->user->id) {
			throw new ForbiddenHttpException('You cannot delete your own account.');
		}

		$target->status = User::STATUS_DELETED;
		$target->save(false);

		return $this->redirect(['index']);
	}
	// [doc:end] actionDelete

	// [doc:start] actionUpdateStatus - /admin/users/update-status - Update Status
	public function actionUpdateStatus()
	{
		$post = Yii::$app->request->post();
		// Booking page buttons post a plain form and come back to that page with a message.
		if (!empty($post['return_booking'])) {
			$result = $this->changeStatus($post);
			Yii::$app->session->setFlash($result['message'] === 'Updated' ? 'success' : 'error', $result['message'] === 'Updated' ? 'Account updated.' : $result['message']);

			return $this->redirect(['booking/view', 'id' => (int) $post['return_booking']]);
		}
		Yii::$app->response->format = 'json';

		return $this->changeStatus($post);
	}
	// [doc:end] actionUpdateStatus

	/**
	 * Applies a status sent by the grid toggle. Only the statuses an administrator may choose are accepted.
	 */
	private function changeStatus(array $post)
	{
		$allowed = [User::STATUS_ACTIVE, User::STATUS_BLOCKED, User::STATUS_INACTIVE];
		$id = $post['id'] ?? null;
		$val = $post['val'] ?? null;

		if (empty($id) || $val === null || !in_array((int) $val, $allowed, true)) {
			return ['message' => 'Invalid request'];
		}
		$model = User::findOne($id);
		if ($model === null) {
			return ['message' => 'User not found'];
		}
		if ((int) $model->id === (int) Yii::$app->user->id) {
			return ['message' => 'You cannot change your own status'];
		}

		$model->status = (int) $val;
		if (!$model->save(false)) {
			return ['message' => 'Not Updated'];
		}
		if ((int) $val !== User::STATUS_ACTIVE) {
			$this->releaseBlockedUser($model);
		}

		return ['message' => 'Updated', 'id' => $model->status];
	}

// public function actionUserVerifyProfile(){
// 	$data =[];
// 	$post = \Yii::$app->request->post();

// 	\Yii::$app->response->format = 'json';
// 	if (! empty ( $post ['user_id'] ) ) {
// 		$model = User::find()->where([
// 			'id' => $post['user_id'],
// 		])->one();
// 		if(!empty($model)){

// 			$model->profile_verified = $post['status'];    
// 			$model->rejection_reason = $post['rejectionReason'];    
// 		}
// 		if($model->save(false)){
// 			$data['message'] = "Updated";
// 			$data['id'] = $model->profile_verified ;

// 			if($model->profile_verified==User::profile_verified_yes){
// 				$body = 'Profile verification status verified successfully';

// 			}else if($model->profile_verified==User::profile_verified_rejected){
// 				$body = 'Profile verification status rejected';

// 			}
// 			$title = 'Profile verification';
		
// 			Yii::$app->push->notifyUser('',$model->id,$title,$body);



// 		}else{
// 			$data['message'] = "Not Updated";
			
// 		}

// }
// return $data;



// }
// [doc:start] actionUserVerifyProfile - /admin/users/user-verify-profile - User Verify Profile
public function actionUserVerifyProfile(){
	// //echo "sduycudgfygefugygd";
	$data =[];
	$post = \Yii::$app->request->post();

	\Yii::$app->response->format = 'json';
	if (! empty ( $post ['user_id'] ) ) {
		$model = User::find()->where([
			'id' => $post['user_id'],
		])->one();
		$driverReferralEarning = DriverReferralEarnings::find()->where(['driver_id'=>$model->referal_id])->one();
		if(!empty($model)){

			$model->profile_verified = $post['status'];    
			$model->rejection_reason = $post['rejectionReason'];    
		}
		if($model->save(false)){
			$data['message'] = "Updated";
			$data['id'] = $model->profile_verified ;

			if($model->profile_verified==User::profile_verified_yes){
				
				$driverReferralEarning->status = DriverReferralEarnings::STATUS_ACTIVE;
				$driverReferralEarning->save();
				
				$one_time_use = \Yii::$app->session->get('one_time_use', false);
				
				if(!$one_time_use){
					$driverWallet = new DriverWallet();
					$driverWallet->driver_id = $driverReferralEarning->driver_id;
					$driverWallet->amount = (float)$driverReferralEarning->earnings;
					$driverWallet->payment_type = DriverWallet::payment_type_credit;
					$driverWallet->method_reason = $driverReferralEarning->earnings . " credited for successful refferal";
					$driverWallet->status = DriverWallet::STATUS_SUCCESS;
					$driverWallet->created_on = date('Y-m-d H:i:s');
					$driverWallet->create_user_id = $model->id; 
					$driverWallet->save(false);
					\Yii::$app->session->set('one_time_use', true);
				}

				$body = 'Profile verification status verified successfully';

			}else if($model->profile_verified==User::profile_verified_rejected){
				$model->save(false);
				$body = 'Profile verification status rejected';
				
			}
			$title = 'Profile verification';
		
			Yii::$app->push->notifyUser('',$model->id,$title,$body);



		}else{
			$data['message'] = "Not Updated";
			
		}

}
return $data;



}
// [doc:end] actionUserVerifyProfile

// [doc:start] actionVehicleEdit - /admin/users/vehicle-edit - Vehicle Edit
public function actionVehicleEdit()
{
    $post = \Yii::$app->request->post();
    \Yii::$app->response->format = 'json';

    $driver_id = $post['user_id'];
	
    // Retrieve existing driver vehicle types
    $driver_vehicle_types = DriverVehicleTypes::find()->where(['driver_id' => $driver_id])->all();

    if (!empty($post['vehicle_types'])) {
        $selected_vehicle_types = $post['vehicle_types'];
		foreach ($driver_vehicle_types as $driver_vehicle) {
			if (!in_array($driver_vehicle->vehicle_type_id, $selected_vehicle_types)) {
				// Vehicle not selected in post, delete it
				$driver_vehicle->delete();
			}
		}
        // Iterate through selected vehicle types
        foreach ($selected_vehicle_types as $selected_vehicle_id) {
            $vehicle_exists = false;
            foreach ($driver_vehicle_types as $driver_vehicle) {
                if ($driver_vehicle->vehicle_type_id == $selected_vehicle_id) {
                    $vehicle_exists = true;
                    break;
                }
            }
            if (!$vehicle_exists) {
                // Vehicle not in database, add it
                $driver_vehicle = new DriverVehicleTypes();
                $driver_vehicle->driver_id = $driver_id;
                $driver_vehicle->vehicle_type_id = $selected_vehicle_id;
                $driver_vehicle->status = DriverVehicleTypes::STATUS_ACTIVE;
                $driver_vehicle->save();
            }
			
        }

        
    } else {
        // No vehicle selected, delete all existing driver vehicle types
        foreach ($driver_vehicle_types as $driver_vehicle) {
            $driver_vehicle->delete();
        }
    }
}
// [doc:end] actionVehicleEdit
// [doc:start] actionAdminPerSet - /admin/users/admin-per-set - Admin Per Set
public function actionAdminPerSet(){
	$data =[];
	$post = \Yii::$app->request->post();

	\Yii::$app->response->format = 'json';
	if (! empty ( $post ['user_id'] ) ) {
		$model = User::find()->where([
			'id' => $post['user_id'],
		])->one();
		if(!empty($model)){

			$model->admin_per = $post['admin_per'];      
		}
		if($model->save(false)){
			$data['message'] = "Updated";
			$data['id'] = $model->id ;
		}else{
			$data['message'] = "Not Updated";
			
		}

}
return $data;

}
// [doc:end] actionAdminPerSet

// [doc:start] actionUserPanUpdate - /admin/users/user-pan-update - User Pan Update
public function actionUserPanUpdate($id) {
	$model = $this->findModel($id);
	$driverPanCardsModel = DriverPanCards::find()->where(['driver_id' => $model->id])->one();
	if ($driverPanCardsModel === null) {
        // Model not found, create a new instance
        $driverPanCardsModel = new DriverPanCards(); // Make sure to use the correct class name
        $driverPanCardsModel->driver_id = $model->id; 
    }
	$request = Yii::$app->request;
	// print_r($request);
	if ($request->isPost) {
		$driverPanCardsModel->load(Yii::$app->request->post());
		$driverPanCardsModel->pan_card = UploadedFile::getInstance($driverPanCardsModel, 'pan_card');
		if ($driverPanCardsModel->validate()) {  
        	$random = date(time()); 
        	$panNumber = Yii::$app->request->get('pan_number');
        	$imageName = 'uploads/' . $random . '_' . $id . '_pan_card.' .  $driverPanCardsModel->pan_card->extension;
            if ($driverPanCardsModel->pan_card->saveAs($imageName)) {
            	$driverPanCardsModel->driver_id = $model->id; 
            	$driverPanCardsModel->pan_card = $imageName;
            	$driverPanCardsModel->pan_number = Yii::$app->request->post('DriverPanCards')['pan_number'];
            	$driverPanCardsModel->save(false);

            }

           $this->redirect(array('users/driver-view','id'=>$model->id));
        }  
	}

	// Render the update form view
		 return $this->render('user_pan_update', ['model' => $model, 'driverPanCardsModel' => $driverPanCardsModel]);

}
// [doc:end] actionUserPanUpdate

// [doc:start] actionUserAadharUpdate - /admin/users/user-aadhar-update - User Aadhar Update
public function actionUserAadharUpdate($id)
{
    $model = $this->findModel($id);
    $driverAadharCardModel = DriverAadharCard::find()->where(['driver_id' => $model->id])->one();

    if ($driverAadharCardModel === null) {
        $driverAadharCardModel = new DriverAadharCard();
        $driverAadharCardModel->driver_id = $model->id;
    }

    $request = Yii::$app->request;

    if ($request->isPost) {
        $driverAadharCardModel->load($request->post());
        $driverAadharCardModel->aadhar_card_front = UploadedFile::getInstance($driverAadharCardModel, 'aadhar_card_front');
        $driverAadharCardModel->aadhar_card_back = UploadedFile::getInstance($driverAadharCardModel, 'aadhar_card_back');

        if ($driverAadharCardModel->validate()) {
            $random = time();
            $aadharNumber = $request->post('DriverAadharCard')['aadhar_number'];

            $frontImageName = 'uploads/' . $random . '_' . $id . '_aadhar_front.' . $driverAadharCardModel->aadhar_card_front->extension;
            $backImageName = 'uploads/' . $random . '_' . $id . '_aadhar_back.' . $driverAadharCardModel->aadhar_card_back->extension;

            if ($driverAadharCardModel->aadhar_card_front->saveAs($frontImageName) && $driverAadharCardModel->aadhar_card_back->saveAs($backImageName)) {
                $driverAadharCardModel->aadhar_card_front = $frontImageName;
                $driverAadharCardModel->aadhar_card_back = $backImageName;
                $driverAadharCardModel->aadhar_number = $aadharNumber;
                $driverAadharCardModel->save(false);
            }

            return $this->redirect(['users/driver-view', 'id' => $model->id]);
        }
    }

    return $this->render('user_aadhar_update', ['model' => $model, 'driverAadharCardModel' => $driverAadharCardModel]);
}
// [doc:end] actionUserAadharUpdate



// [doc:start] actionUserLicenseUpdate - /admin/users/user-license-update - User License Update
public function actionUserLicenseUpdate($id)
{
	$model = $this->findModel($id);
	$drivingLicenseModel = DrivingLicense::find()->where(['driver_id' => $model->id])->one();

	if ($drivingLicenseModel === null) {
		// Model not found, create a new instance
		$drivingLicenseModel = new DrivingLicense(); 
		$drivingLicenseModel->driver_id = $model->id; 
	}

	$request = Yii::$app->request;

	if ($request->isPost) {
		$drivingLicenseModel->load(Yii::$app->request->post());
		$drivingLicenseModel->driving_license_front = UploadedFile::getInstance($drivingLicenseModel, 'driving_license_front');
		$drivingLicenseModel->driving_license_back = UploadedFile::getInstance($drivingLicenseModel, 'driving_license_back');

		if ($drivingLicenseModel->validate()) {
			$random = date(time());
			$imageNameFront = 'uploads/' . $random . '_' . $id . '_driving_license_front.' .  $drivingLicenseModel->driving_license_front->extension;
			$imageNameBack = 'uploads/' . $random . '_' . $id . '_driving_license_back.' .  $drivingLicenseModel->driving_license_back->extension;

			if ($drivingLicenseModel->driving_license_front->saveAs($imageNameFront) && $drivingLicenseModel->driving_license_back->saveAs($imageNameBack)) {
				$drivingLicenseModel->driving_license_front = $imageNameFront;
				$drivingLicenseModel->driving_license_back = $imageNameBack;
				$drivingLicenseModel->save(false);
			}

			$this->redirect(['users/driver-view', 'id' => $model->id]);
		}
	}

	return $this->render('user_license_update', ['model' => $model, 'drivingLicenseModel' => $drivingLicenseModel]);
}
// [doc:end] actionUserLicenseUpdate


}
