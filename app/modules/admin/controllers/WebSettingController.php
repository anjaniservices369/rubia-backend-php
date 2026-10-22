<?php

namespace app\modules\admin\controllers;

use Yii;
use app\modules\admin\models\WebSetting;
use app\modules\admin\models\search\WebSettingSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;

/**
 * WebSettingController implements the CRUD actions for WebSetting model.
 */
class WebSettingController extends Controller
{
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
                        'actions' => ['index', 'view', 'create', 'update', 'delete', 'pdf',
                        'cms',
                        'save-cms',
                        'dispatch',
                        'logo',
                        'favicon' ],
                        'roles' => ['@']
                    ],
                    [
                        'allow' => false
                    ]
                ]
            ]
        ];
    }
    // [doc:end] behaviors

    // [doc:start] actionIndex - /admin/web-setting/index - Lists all WebSetting models.
    /**
     * Lists all WebSetting models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new WebSettingSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    // [doc:end] actionIndex

    // [doc:start] actionView - /admin/web-setting/view - Displays a single WebSetting model.
    /**
     * Displays a single WebSetting model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }
    // [doc:end] actionView

    // [doc:start] actionCreate - /admin/web-setting/create - Creates a new WebSetting model.
    /**
     * Creates a new WebSetting model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new WebSetting();

        if ($model->loadAll(Yii::$app->request->post()) && $model->saveAll()) {
            return $this->redirect(['view', 'id' => $model->setting_id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }
    // [doc:end] actionCreate

    // [doc:start] actionUpdate - /admin/web-setting/update - Updates an existing WebSetting model.
    /**
     * Updates an existing WebSetting model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->loadAll(Yii::$app->request->post()) && $model->saveAll()) {
            return $this->redirect(['view', 'id' => $model->setting_id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }
    // [doc:end] actionUpdate

    // [doc:start] actionDelete - /admin/web-setting/delete - Deletes an existing WebSetting model.
    /**
     * Deletes an existing WebSetting model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->deleteWithRelated();

        return $this->redirect(['index']);
    }
    // [doc:end] actionDelete
    
    // [doc:start] actionPdf - /admin/web-setting/pdf - Export WebSetting information into PDF format.
    /**
     * 
     * Export WebSetting information into PDF format.
     * @param integer $id
     * @return mixed
     */
    public function actionPdf($id) {
        $model = $this->findModel($id);

        $content = $this->renderAjax('_pdf', [
            'model' => $model,
        ]);

        $pdf = new \kartik\mpdf\Pdf([
            'mode' => \kartik\mpdf\Pdf::MODE_CORE,
            'format' => \kartik\mpdf\Pdf::FORMAT_A4,
            'orientation' => \kartik\mpdf\Pdf::ORIENT_PORTRAIT,
            'destination' => \kartik\mpdf\Pdf::DEST_BROWSER,
            'content' => $content,
            'cssFile' => '@vendor/kartik-v/yii2-mpdf/assets/kv-mpdf-bootstrap.min.css',
            'cssInline' => '.kv-heading-1{font-size:18px}',
            'options' => ['title' => \Yii::$app->name],
            'methods' => [
                'SetHeader' => [\Yii::$app->name],
                'SetFooter' => ['{PAGENO}'],
            ]
        ]);

        return $pdf->render();
    }
    // [doc:end] actionPdf

    
    /**
     * Finds the WebSetting model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return WebSetting the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = WebSetting::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
        }
    }
    // [doc:start] actionCms - /admin/web-setting/cms - Cms
    public function actionCms() {
		$model = new WebSetting ();
		$web_settings = WebSetting::find()->Where([
			'type_id' => WebSetting::WEB_SETTING
		])->andWhere([
			'!=','setting_key','website_logo'
		])->andWhere([
			'!=','setting_key','website_favicon'
		])->all();

		$webimage_setting = WebSetting::find()->Where([
			'type_id' => WebSetting::WEB_SETTING,
			'setting_key' => 'website_logo'
		])->one();

		$smtp_settings = WebSetting::find()->Where([
			'type_id' => WebSetting::SMPT_SETTING
		])->all ();
		$url_settings = WebSetting::find()->Where([
			'type_id' => WebSetting::URL_SETTING
		])->all ();
		$email_settings = WebSetting::find()->Where([
			'type_id' => WebSetting::EMAIL_SETTING 
		])->all ();
		$enable_settings = WebSetting::find()->Where([
			'type_id' => WebSetting::ENABLE_SERVICES
		])->all ();
		$amount_settings = WebSetting::find()->Where([
			'type_id' => WebSetting::AMOUNT_SETTING
		])->all ();
		$secret_id = WebSetting::find()->Where([
			'type_id' => WebSetting::SECRET_ID
        ])->all ();
        
		$content_settings = WebSetting::find()->Where([
			'type_id' => WebSetting::CONTENT_SETTING
		])->all ();
		$fax_settings = WebSetting::find()->Where([
			'type_id' => WebSetting::FAX_SETTING
		])->all ();
		$paypal_setting = WebSetting::find()->where([
			'type_id' => WebSetting::PAYPAL_SETTING])->all();
		$notification = WebSetting::find()->where([
		'type_id' => WebSetting::NOTIFICATION])->all();
		$payment_gateway = WebSetting::find()->where([
            'type_id' => WebSetting::PAYMENT_GATEWAY])->all();
		$driver_settings = WebSetting::find()->where([
            'type_id' => WebSetting::DRIVER_SETTINGS])->all();
		$rider_settings = WebSetting::find()->where([
            'type_id' => WebSetting::RIDER_SETTINGS])->all();
		$user_settings = WebSetting::find()->where([
            'type_id' => WebSetting::USER_SETTINGS])->all();
		$bike_settings = WebSetting::find()->where([
            'type_id' => WebSetting::BIKE_SETTINGS])->all();
		$taxi_settings = WebSetting::find()->where([
            'type_id' => WebSetting::TAXI_SETTINGS])->all();
      
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
                'payment_gateway' => $payment_gateway,
                'driver_settings' => $driver_settings,
                'rider_settings' => $rider_settings,
                'user_settings' => $user_settings,
                'bike_settings' => $bike_settings,
                'taxi_settings' => $taxi_settings,
               

		] );
	}
    // [doc:end] actionCms
	// [doc:start] actionSaveCms - /admin/web-setting/save-cms - Saves one dashboard setting. POST only: a setting (and a secret such as an API key) must never travel in a URL.
	/**
	 * Saves one dashboard setting. POST only: a setting (and a secret such as an API key) must never travel in a URL.
	 */
	public function actionSaveCms() {
		\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
		$request = \Yii::$app->request;
		if (!$request->isPost) {
			throw new \yii\web\MethodNotAllowedHttpException('POST required.');
		}

		$id = $request->post ( 'id' );
		$model = $id ? WebSetting::findOne ( [ 'setting_id' => $id ] ) : null;
		if ($model === null) {
			return [ 'saved' => false ];
		}
		$model->value = ( string ) $request->post ( 'value', '' );
		$model->save ( false );

		return [ 'saved' => true ];
	}
	// [doc:end] actionSaveCms
	// [doc:start] actionSaveCmsImage - /admin/web-setting/save-cms-image - Save Cms Image
	public function actionSaveCmsImage() {
		if (empty($_FILES['images'])) {
			print json_encode(['error'=>'No files found for upload.']); 
			return; 
		}
    }
	// [doc:end] actionSaveCmsImage
    // [doc:start] actionFavicon - /admin/web-setting/favicon - Favicon
    public function actionFavicon(){
		$getkey = new WebSetting();
        $key = $getkey->getSettingBykey('website_favicon');
       // $id = $key->id;
        //var_dump($id); exit;
        $model = WebSetting::find()->where(['setting_key' => 'website_favicon'])->one();
		//$model = WebSetting::findOne($id);
		if ($model->load(Yii::$app->request->post())){
			$image = UploadedFile::getInstance($model,'value');
			if (!empty($image)){
				$fileName = $image->baseName . '-' . time () . '.' . $image->extension;
				$image->saveAs('uploads/'.$fileName );
				$model->setting_id = $model->setting_id;
                $model->value=$fileName;
                $model->status = '1';
			}
			$model->save();
		}
		return $this->render ('favicon', [ 
			'model' => $model 
		]);
	}
    // [doc:end] actionFavicon
    // [doc:start] actionLogo - /admin/web-setting/logo - Logo
    public function actionLogo(){
		$getkey = new WebSetting();
        $key = $getkey->getSettingBykey('logo');
       // $id = $key->id;
        //var_dump($id); exit;
        $model = WebSetting::find()->where(['setting_key' => 'logo'])->one();
		//$model = WebSetting::findOne($id);
		if ($model->load(Yii::$app->request->post())){
			$image = UploadedFile::getInstance($model,'value');
			if (!empty($image)){
				$fileName = $image->baseName . '-' . time () . '.' . $image->extension;
				$image->saveAs('uploads/'.$fileName );
				$model->setting_id = $model->setting_id;
                $model->value=$fileName;
                $model->status = '1';
			}
			$model->save();
		}
		return $this->render ( 'logo', [ 
			'model' => $model 
		]);
	}
    // [doc:end] actionLogo
}
