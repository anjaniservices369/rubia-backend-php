<?php

namespace app\modules\admin\controllers;

use Yii;
use app\models\User;
use app\modules\admin\models\VehicleTypes;
use app\modules\admin\models\search\VehicleTypesSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * VehicleTypesController implements the CRUD actions for VehicleTypes model.
 */
class VehicleTypesController extends Controller
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
                        'actions' => ['index', 'view', 'update', 'pdf', 'update-status'],
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

    // [doc:start] actionIndex - /admin/vehicle-types/index - Lists all VehicleTypes models.
    /**
     * Lists all VehicleTypes models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new VehicleTypesSearch();
        if(\Yii::$app->user->identity->user_role==User::ROLE_ADMIN || \Yii::$app->user->identity->user_role==User::ROLE_SUBADMIN){
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        } else if(\Yii::$app->user->identity->user_role==User::ROLE_MANAGER){
            $dataProvider = $searchModel->managersearch(Yii::$app->request->queryParams);
        }
        else if(\Yii::$app->user->identity->user_role==User::ROLE_FRANCHISE){
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
            // $dataProvider = $searchModel->managersearch(Yii::$app->request->queryParams);
        }
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    // [doc:end] actionIndex

    // [doc:start] actionView - /admin/vehicle-types/view - Displays a single VehicleTypes model.
    /**
     * Displays a single VehicleTypes model.
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

    // [doc:start] actionCreate - /admin/vehicle-types/create - Creates a new VehicleTypes model.
    /**
     * Creates a new VehicleTypes model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new VehicleTypes();

        if ($model->loadAll(Yii::$app->request->post()) && $model->saveAll()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }
    // [doc:end] actionCreate

    // [doc:start] actionUpdate - /admin/vehicle-types/update - Updates an existing VehicleTypes model.
    /**
     * Updates an existing VehicleTypes model.
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

    // [doc:start] actionDelete - /admin/vehicle-types/delete - Deletes an existing VehicleTypes model.
    /**
     * Deletes an existing VehicleTypes model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
      
        $model = $this->findModel($id);
        if(!empty($model)){
            $model->status = VehicleTypes::STATUS_DELETE;
            $model->save(false); 
        }

        return $this->redirect(['index']);
    }
    // [doc:end] actionDelete
    
    // [doc:start] actionUpdateStatus - /admin/vehicle-types/update-status - Update Status
    public function actionUpdateStatus(){
		$data =[];
		$post = \Yii::$app->request->post();
		\Yii::$app->response->format = 'json';
		if (! empty ( $post ['id'] ) ) {
			$model = VehicleTypes::find()->where([
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
     * Finds the VehicleTypes model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return VehicleTypes the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = VehicleTypes::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
        }
    }
}
