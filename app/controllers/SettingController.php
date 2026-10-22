<?php

namespace app\controllers;

use Yii;
use app\models\Setting;
use app\models\SettingSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use app\models\OrderSearch;

/**
 * SettingController implements the CRUD actions for Setting model.
 */
class SettingController extends Controller {
	/**
	 * @inheritdoc
	 */
	public function behaviors() {
		return array_merge ( parent::behaviors (), [ 
				'verbs' => [ 
						'class' => VerbFilter::className (),
						'actions' => [ 
								'delete' => [ 
										'POST' 
								] 
						] 
				] 
		] );
	}
	
	/**
	 * Lists all Setting models.
	 * 
	 * @return mixed
	 */
	public function actionIndex() {
		$searchModel = new SettingSearch();
		$dataProvider = $searchModel->search( Yii::$app->request->queryParams );
		
		return $this->render ( 'index', [ 
				'searchModel' => $searchModel,
				'dataProvider' => $dataProvider 
		] );
	}
	
	/**
	 * Displays a single Setting model.
	 * 
	 * @param integer $id        	
	 * @return mixed
	 */
	public function actionView($id) {
		return $this->render ( 'view', [ 
				'model' => $this->findModel ( $id ) 
		] );
	}
	
	/**
	 * Creates a new Setting model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 * 
	 * @return mixed
	 */
	public function actionCreate() {
		$model = new Setting ();
		
		if ($model->load ( Yii::$app->request->post () )) {
			
			$image = UploadedFile::getInstance ( $model, 'value' );
			if (! empty ( $image )) {
				$fileName = $image->baseName . '-' . time () . $imageFile->extension;
				
				$image->saveAs ( 'uploads/' . $fileName );
				$model->value = $fileName;
			}
			
			$model->save ();
			
			return $this->redirect ( [ 
					'view',
					'id' => $model->setting_id 
			] );
		} else {
			return $this->render ( 'create', [ 
					'model' => $model 
			] );
		}
	}
	
	/**
	 * Updates an existing Setting model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * 
	 * @param integer $id        	
	 * @return mixed
	 */
	public function actionUpdate($id) {
		$model = $this->findModel ( $id );
		
		if ($model->load ( Yii::$app->request->post () )) {
			
			$image = UploadedFile::getInstance ( $model, 'value' );
			if (! empty ( $image )) {
				$fileName = $image->baseName . '-' . time () . '.' . $image->extension;
				
				$image->saveAs ( 'uploads/' . $fileName );
				$model->value = $fileName;
			}
			$model->save ();
			return $this->redirect ( [ 
					'view',
					'id' => $model->setting_id 
			] );
		} else {
			return $this->render ( 'update', [ 
					'model' => $model 
			] );
		}
	}
	
	/**
	 * Deletes an existing Setting model.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 * 
	 * @param integer $id        	
	 * @return mixed
	 */
	public function actionDelete($id) {
		$this->findModel ( $id )->delete ();
		
		return $this->redirect ( [ 
				'index' 
		] );
	}
	
	/**
	 * Finds the Setting model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 * 
	 * @param integer $id        	
	 * @return Setting the loaded model
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	protected function findModel($id) {
		if (($model = Setting::findOne ( $id )) !== null) {
			return $model;
		} else {
			throw new NotFoundHttpException ( 'The requested page does not exist.' );
		}
	}
	public function actionCms() {
		$model = new Setting ();
		$web_settings = Setting::find()->Where([
			'type_id' => Setting::WEB_SETTING
		])->andWhere([
			'!=','setting_key','website_logo'
		])->andWhere([
			'!=','setting_key','website_favicon'
		])->all();

		$webimage_setting = Setting::find()->Where([
			'type_id' => Setting::WEB_SETTING,
			'setting_key' => 'website_logo'
		])->one();

		$smtp_settings = Setting::find()->Where([
			'type_id' => Setting::SMPT_SETTING
		])->all ();
		$url_settings = Setting::find()->Where([
			'type_id' => Setting::URL_SETTING
		])->all ();
		$email_settings = Setting::find()->Where([
			'type_id' => Setting::EMAIL_SETTING
		])->all ();
		$enable_settings = Setting::find()->Where([
			'type_id' => Setting::ENABLE_SERVICES
		])->all ();
		$amount_settings = Setting::find()->Where([
			'type_id' => Setting::AMOUNT_SETTING
		])->all ();
		$secret_id = Setting::find()->Where([
			'type_id' => Setting::SECRET_ID
		])->all ();
		$content_settings = Setting::find()->Where([
			'type_id' => Setting::CONTENT_SETTING
		])->all ();
		$fax_settings = Setting::find()->Where([
			'type_id' => Setting::FAX_SETTING
		])->all ();
		$paypal_setting = Setting::find()->where([
			'type_id' => Setting::PAYPAL_SETTING])->all();
		$notification = Setting::find()->where([
		'type_id' => Setting::NOTIFICATION])->all();
		$razorpay = Setting::find()->where([
			'type_id' => Setting::RAZORPAY])->all();
		return $this->render( 'cms', [ 
				'model' => $model,
				'web_settings' => $web_settings,
				'smtp_settings'=>$smtp_settings,
				'url_settings'=>$url_settings,
				'email_settings'=>$email_settings,
				'enable_settings'=>$enable_settings,
				'amount_settings'=>$amount_settings,
				'secret_id'=>$secret_id,
				'content_settings'=>$content_settings,
				'webimage_setting'=>$webimage_setting,
				'fax_setting'=>$fax_settings,
				'paypal_setting'=>$paypal_setting,
				'notification' => $notification,
				'razorpay' => $razorpay

		] );
	}
	public function actionSaveCms() {
		$data = \Yii::$app->request->get ();   
		if (!empty($data ['id'] )){
			
			$model = Setting::find ()->where ( [ 
					'setting_id' => $data ['id'] 
			] )->one ();
			$model->value = $data['value'];
			
			$model->save (false);
		}
	}
	public function actionSaveCmsImage() {
		if (empty($_FILES['images'])) {
			echo json_encode(['error'=>'No files found for upload.']); 
			return; 
		}
	}
	
}
