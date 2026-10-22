<?php

namespace app\modules\media\controllers;

use app\components\BaseController;
use app\models\User;
use app\modules\media\models\Media;
use app\modules\media\models\MediaSearch;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * Default controller for the `media` module
 */
class DefaultController extends BaseController {
	public $layout = '@app/views/layouts/main';
	public $enableCsrfValidation = false;
	// [doc:start] behaviors - Behaviors
	public function behaviors() {
		return [ 
				'access' => [ 
						'class' => AccessControl::className (),
						'rules' => [ 
								[ 
										'actions' => [ 
												'bulk-delete',
												'index',
												'save-detail',
												'detail' 
										],
										'allow' => true,
										'matchCallback' => function () {
											return User::isAdmin () || User::isManager ();
										} 
								] 
						] 
				],
				'verbs' => [ 
						'class' => VerbFilter::className (),
						'actions' => [ 
								'save-detail' => [ 
										'post' 
								] 
						] 
				] 
		];
	}
	// [doc:end] behaviors
	// [doc:start] actionIndex - /media/default/index - Renders the index view for the module.
	/**
	 * Renders the index view for the module
	 *
	 * @return string
	 */
	public function actionIndex() {
		$this->layout = '@app/views/layouts/main';
		$searchModel = new MediaSearch ();
		$dataProvider = $searchModel->search ( \Yii::$app->request->queryParams );
		
		return $this->render ( 'index', [ 
				'dataProvider' => $dataProvider 
		] );
	}
	// [doc:end] actionIndex
	// [doc:start] actionDetail - /media/default/detail - Detail
	public function actionDetail($id) {
		\Yii::$app->response->format = 'json';
		$response = [ 
				'status' => 1000 
		];
		$model = $this->findModel ( $id );
		$response ['imageUrl'] = \Yii::$app->urlManager->createAbsoluteUrl ( [ 
				"uploads/thumbnail/$model->thumb_file" 
		] );
		$response ['uploadedBy'] = isset ( $model->createUser ) ? $model->createUser->full_name : 'Not Set';
		$response ['model'] = $model;
		return $response;
	}
	// [doc:end] actionDetail
	// [doc:start] actionSaveDetail - /media/default/save-detail - Save Detail
	public function actionSaveDetail($id) {
		\Yii::$app->response->format = 'json';
		$response = [ 
				'status' => 1000 
		];
		$post = \Yii::$app->request->post ();
		$model = $this->findModel ( $id );
		
		if (isset ( $post ['title'] )) {
			$model->title = $post ['title'];
		}
		if (isset ( $post ['alt'] )) {
			$model->alt = $post ['alt'];
		}
		if (! $model->save ( false, [ 
				'title',
				'alt' 
		] )) {
			$response ['error'] = $model->errorString ();
		}
		$response ['model'] = $model;
		return $response;
	}
	// [doc:end] actionSaveDetail
	protected function findModel($id) {
		if (($model = Media::findOne ( $id )) !== null) {
			return $model;
		}
		
		throw new NotFoundHttpException ( \Yii::t ( 'app', 'The requested page does not exist.' ) );
	}
}
