<?php

namespace app\modules\admin\controllers;

use Yii;
use app\models\User;
use app\modules\admin\models\RideCommision;
use app\modules\admin\models\search\RideCommisionSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * RideCommisionController implements the CRUD actions for RideCommision model.
 */
class RideCommisionController extends Controller
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
                        'actions' => ['index', 'view', 'create', 'update', 'delete', 'update-status'],
                        'matchCallback' => function () {
                            return User::isAdmin() || User::isSubAdmin();
                        }
                       
                    ],
                    [
                        'allow' => true,
                        'actions' => ['index', 'view', 'update', 'pdf', 'update-status'],
                        'matchCallback' => function () {
                            return User::isManager();
                        }
                    ],
                    [
                        'allow' => true,
                        'actions' => ['index', 'view', 'create', 'update', 'delete', 'update-status'],
                        'matchCallback' => function () {
                            return User::isFranchise();
                        }
                    ],
                    [
                        'allow' => false
                    ]
                ]
            ]
        ];
    }
    // [doc:end] behaviors

    // [doc:start] actionIndex - /admin/ride-commision/index - Lists all RideCommision models.
    /**
     * Lists all RideCommision models.
     * @return mixed
     */
    public function actionIndex()
    {
        $vehicle_id = Yii::$app->request->get('vehicle_id');
        $searchModel = new RideCommisionSearch();
        if(\Yii::$app->user->identity->user_role==User::ROLE_ADMIN || \Yii::$app->user->identity->user_role==User::ROLE_SUBADMIN){
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams,$vehicle_id,'');
        } else if(\Yii::$app->user->identity->user_role==User::ROLE_MANAGER){
            $dataProvider = $searchModel->managersearch(Yii::$app->request->queryParams);
        }
        else if(\Yii::$app->user->identity->user_role==User::ROLE_FRANCHISE){
            $dataProvider = $searchModel->searchManager(Yii::$app->request->queryParams,$vehicle_id,\Yii::$app->user->identity->city_id);
        }
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    // [doc:end] actionIndex

    // [doc:start] actionView - /admin/ride-commision/view - Displays a single RideCommision model.
    /**
     * Displays a single RideCommision model.
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

    // [doc:start] actionCreate - /admin/ride-commision/create - Creates a new RideCommision model.
    /**
     * Creates a new RideCommision model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new RideCommision();

        if ($model->loadAll(Yii::$app->request->post()) && $model->saveAll()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }
    // [doc:end] actionCreate

    // [doc:start] actionUpdate - /admin/ride-commision/update - Updates an existing RideCommision model.
    /**
     * Updates an existing RideCommision model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->loadAll(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }
    // [doc:end] actionUpdate

    // [doc:start] actionDelete - /admin/ride-commision/delete - Deletes an existing RideCommision model.
    /**
     * Deletes an existing RideCommision model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
      
        $model = $this->findModel($id);
        if(!empty($model)){
            $model->status = RideCommision::STATUS_DELETE;
            $model->save(false); 
        }

        return $this->redirect(['index']);
    }
    // [doc:end] actionDelete
    
    // [doc:start] actionUpdateStatus - /admin/ride-commision/update-status - Update Status
    public function actionUpdateStatus(){
		$data =[];
		$post = \Yii::$app->request->post();
		\Yii::$app->response->format = 'json';
		if (! empty ( $post ['id'] ) ) {
			$model = RideCommision::find()->where([
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
    // [doc:end] actionUpdateStatus

    
    /**
     * Finds the RideCommision model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return RideCommision the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = RideCommision::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
        }
    }
}
