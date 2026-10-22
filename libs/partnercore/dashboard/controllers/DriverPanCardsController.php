<?php

namespace app\modules\admin\controllers;

use Yii;
use app\models\User;
use app\modules\admin\models\DriverPanCards;
use app\modules\admin\models\search\DriverPanCardsSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * DriverPanCardsController implements the CRUD actions for DriverPanCards model.
 */
class DriverPanCardsController extends Controller
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
                        'allow' => false
                    ]
                ]
            ]
        ];
    }
    // [doc:end] behaviors

    // [doc:start] actionIndex - /admin/driver-pan-cards/index - Lists all DriverPanCards models.
    /**
     * Lists all DriverPanCards models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new DriverPanCardsSearch();
        if(\Yii::$app->user->identity->user_role==User::ROLE_ADMIN || \Yii::$app->user->identity->user_role==User::ROLE_SUBADMIN){
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        } else if(\Yii::$app->user->identity->user_role==User::ROLE_MANAGER){
            $dataProvider = $searchModel->managersearch(Yii::$app->request->queryParams);
        }
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    // [doc:end] actionIndex

    // [doc:start] actionView - /admin/driver-pan-cards/view - Displays a single DriverPanCards model.
    /**
     * Displays a single DriverPanCards model.
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

    // [doc:start] actionCreate - /admin/driver-pan-cards/create - Creates a new DriverPanCards model.
    /**
     * Creates a new DriverPanCards model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new DriverPanCards();

        if ($model->loadAll(Yii::$app->request->post()) && $model->saveAll()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }
    // [doc:end] actionCreate

    // [doc:start] actionUpdate - /admin/driver-pan-cards/update - Updates an existing DriverPanCards model.
    /**
     * Updates an existing DriverPanCards model.
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

    // [doc:start] actionDelete - /admin/driver-pan-cards/delete - Deletes an existing DriverPanCards model.
    /**
     * Deletes an existing DriverPanCards model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
      
        $model = $this->findModel($id);
        if(!empty($model)){
            $model->status = DriverPanCards::STATUS_DELETE;
            $model->save(false); 
        }

        return $this->redirect(['index']);
    }
    // [doc:end] actionDelete
    
    // [doc:start] actionUpdateStatus - /admin/driver-pan-cards/update-status - Update Status
    public function actionUpdateStatus(){
		$data =[];
		$post = \Yii::$app->request->post();
		\Yii::$app->response->format = 'json';
		if (! empty ( $post ['id'] ) ) {
			$model = DriverPanCards::find()->where([
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
     * Finds the DriverPanCards model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return DriverPanCards the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = DriverPanCards::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
        }
    }
}
