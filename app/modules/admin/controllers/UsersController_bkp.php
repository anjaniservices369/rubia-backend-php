<?php

namespace app\modules\admin\controllers;

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


/**
 * UsersController implements the CRUD actions for User model.
 */
class UsersController extends Controller
{
	use FindModelOrFail;
	
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		$this->modelClass = UserForm::class;
	}
	
	/**
	 * @inheritdoc
	 */

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
						 'actions' => ['index', 'view', 'create', 'update', 'delete', 'update-status','status-change','drivers','driver-view','user-verify-profile','admin-per-set','taxi','taxi-amount','franchise','create-franchise'],
						 'matchCallback' => function () {
							 return User::isAdmin() || User::isSubAdmin();
						 }
						
					 ], 
					 [
						 'allow' => true,
						 'actions' => ['index', 'view', 'update', 'pdf', 'update-status','status-change','drivers','driver-view','user-verify-profile','admin-per-set','taxi','taxi-amount','franchise','create-franchise'],
						 'matchCallback' => function () {
							 return User::isManager();
						 }
					 ],
					 [
						 'allow' => true,
						 'actions' => ['drivers','taxi-amount'],
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
	 

	
public function actionUserBankView($id)
{
    $model = $this->findUserModel($id);
    $bankDetailsModel = $model->getDriverBankDetails()->one();

    return $this->render('view_user_bank', [
        'model' => $model,
        'bankDetailsModel' => $bankDetailsModel,
    ]);
}





  
  
	
	 public function actionUserPasswordUpdate($id)
{
    $model = $this->findUserModel($id);

    // Use AdminUserForm for admins and UserForm for others
    $userFormClass = (\Yii::$app->user->identity->user_role == User::ROLE_ADMIN) ? UserPassword::class : UserForm::class;
    $userForm = new $userFormClass();
    $userForm = new $userFormClass(['scenario' => 'update']);
    $userForm->setAttributes($model->attributes);

    $request = Yii::$app->request;

    if ($request->isPost) {
        // Load form data directly into the user form
        $userForm->load($request->post());

        // Validate and save the correct model
        if ($userForm->validate()) {
            $model->setAttributes($userForm->attributes);

			if ($userForm->password) {
				$model->setPassword($userForm->password);
			}
			
            if ($model->save()) {
                return $this->render('view_user_password', ['model' => $model]);
            } else {
                print_r($model->errors);
            }
        } else {
            print_r($userForm->errors);
        }
    }

    return $this->render('user_password_update', ['model' => $userForm]);
}

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
	


	 public function actionStatusChange()
	 {
		$post = \Yii::$app->request->post ();	
	   
		if (! empty ( $post ['id'] )) {
			
			$transaction = User::find()->where(['id' =>  $post['id']])->one();
		if (! empty ( $transaction )) {
				
				$transaction->status = $post ['val'];           
				
				if($transaction->update (false)){
					return true;
	
			   }
			   else{
				   return false;
			   }
			}else{
				return false;
			}
		}		
	 }
 
 ///end now change status



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

 



	/**
	 * Lists all User models.
	 *
	 * @return mixed	
	 */
	public function actionIndex()
	{
		$searchModel = new UserSearch();
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams,User::ROLE_USER,User::TYPE_PERSONAL);
		return $this->render('index', [
			'searchModel'  => $searchModel,
			'dataProvider' => $dataProvider,
		]);
	}
	
	public function actionFranchise()
	{
		$searchModel = new UserSearch();
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams,User::ROLE_FRANCHISE);
		return $this->render('franchise', [
			'searchModel'  => $searchModel,
			'dataProvider' => $dataProvider,
		]);
	}
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


	public function actionTaxi(){
		$searchModel = new UserSearch();
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams,User::ROLE_TAXI);
		return $this->render('taxi', [
			'searchModel'  => $searchModel,
			'dataProvider' => $dataProvider,
		]);

	}
  



	public function actionBusiness()
	{
		$searchModel = new UserSearch();
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams,User::ROLE_USER,User::TYPE_BUSINESS);
		return $this->render('business_user', [
			'searchModel'  => $searchModel,
			'dataProvider' => $dataProvider,
		]);
	}

	public function actionVendor()
	{
		$searchModel = new UserSearch();
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams,User::ROLE_VENDOR);
		return $this->render('vendor', [
			'searchModel'  => $searchModel,
			'dataProvider' => $dataProvider,
		]);
	}

	public function actionSubAdmin(){
		$searchModel = new UserSearch();
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams,User::ROLE_SUBADMIN);
		return $this->render('index', [
			'searchModel'  => $searchModel,
			'dataProvider' => $dataProvider,
		]);

	} 

	public function actionManagers(){
		$searchModel = new UserSearch();
		$dataProvider = $searchModel->search(Yii::$app->request->queryParams,User::ROLE_VENDOR);
		return $this->render('index', [
			'searchModel'  => $searchModel,
			'dataProvider' => $dataProvider,
		]);

	}

	public function actionSubscribeUser(){
		$searchModel = new UserSearch();
		$dataProvider = $searchModel->SubscribedUserSearch(Yii::$app->request->queryParams,User::ROLE_USER);
		return $this->render('index', [
			'searchModel'  => $searchModel,
			'dataProvider' => $dataProvider,
		]);

	}
	public function actionRefererList($id) {
		$searchModel = new UserSearch ();
		\Yii::$app->view->title = "Referal List";
		$dataProvider = $searchModel->usersearch ( Yii::$app->request->queryParams, $id );
		return $this->render ( 'index', [ 
				'searchModel' => $searchModel,
				'dataProvider' => $dataProvider 
		] );
	}
	
	/**
	 * Creates a new User model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 *
	 * @return mixed
	 */
	public function actionCreate()
	{
		$model = new User();
		$model->on(User::EVENT_BEFORE_INSERT, [$model, 'generateAuthKey']);
		
        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post())) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return ActiveForm::validate($model);
            \Yii::$app->end();
        }
		if ($model->load(Yii::$app->request->post())) {
			

			if($model->save()){
				return $this->redirect([ 'view','id' => $model->id ]);
				//return $this->redirect(['update', 'id' => $model->id]);
			}else{
				print_r($model->getErrors()); exit;
			}
			
		}	
		return $this->render('create', [
			'model' => $model,
		]);
	}
	
	///////// Franchise create
	public function actionCreateFranchise()
	{
		$model = new UserForm();
		$model->on(User::EVENT_BEFORE_INSERT, [$model, 'generateAuthKey']);
		
        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post())) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return ActiveForm::validate($model);
            \Yii::$app->end();
        }
		if ($model->load(Yii::$app->request->post())) {
			
			if($model->save()){
				// return $this->redirect([ 'view','id' => $model->id ]);
				return $this->redirect([ 'users/franchise']);
				//return $this->redirect(['update', 'id' => $model->id]);
			}else{
				print_r($model->getErrors()); exit;
			}
			
		}	
		return $this->render('franchisecreate', [
			'model' => $model,
		]);
	}

	
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
    $model = $this->findModel($id, UserForm::class);

    $request = Yii::$app->request;
    
    if ($request->isPost && $model->load($request->post()) && $model->save(false)) {
        return $this->redirect(['view', 'id' => $model->id]);
    }

    return $this->render('update', [
        'model' => $model,
    ]);
}
	public function actionView($id) {
		$model = User::find()->where(['id' => $id])->one();
		return $this->render('view_old',['model' => $model]);

	}


	public function actionDriverView($id) {
		$model = User::find()->where(['id' => $id])->one();
		return $this->render('driver_view',['model' => $model]);

	}



	
	/**
	 * Deletes an existing User model.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 *
	 * @param integer $id
	 *
	 * @return mixed
	 */
	public function actionDelete($id)
	{
		$this->findModel($id)->delete();
		
		return $this->redirect(['index']);
	}
	public function actionUpdateStatus(){
		$data =[];
		$post = \Yii::$app->request->post();
		\Yii::$app->response->format = 'json';
		if (! empty ( $post ['id'] ) ) {
			$model = User::find()->where([
				'id' => $post['id'],
			])->one();
			if(!empty($model)){

                $model->status = $post['val'];      
			}
			if($model->save(false)){
				$data['message'] = "Updated";
                $data['id'] = $model->status ;
			}else{
				$data['message'] = "Not Updated";
                
			}

	} 
	return $data;
}
public function actionUserVerifyProfile(){
	$data =[];
	$post = \Yii::$app->request->post();

	\Yii::$app->response->format = 'json';
	if (! empty ( $post ['user_id'] ) ) {
		$model = User::find()->where([
			'id' => $post['user_id'],
		])->one();
		if(!empty($model)){

			$model->profile_verified = $post['status'];    
			$model->rejection_reason = $post['rejectionReason'];    
		}
		if($model->save(false)){
			$data['message'] = "Updated";
			$data['id'] = $model->profile_verified ;

			if($model->profile_verified==User::profile_verified_yes){
				$body = 'Profile verification status verified successfully';

			}else if($model->profile_verified==User::profile_verified_rejected){
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


}
