<?php

namespace app\modules\admin\controllers;

use Yii;
use app\models\User;
use app\modules\admin\models\Plans;
use app\modules\admin\models\search\PlansSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * PlansController implements the CRUD actions for Plans model.
 */
class PlansController extends Controller
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
                        'actions' => ['index', 'view', 'create', 'update', 'delete', 'update-status', 'add-driver-subscription', 'add-driver-subscription-logs'],
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

    // [doc:start] actionIndex - /admin/plans/index - Lists all Plans models.
    /**
     * Lists all Plans models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new PlansSearch();
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

    // [doc:start] actionView - /admin/plans/view - Displays a single Plans model.
    /**
     * Displays a single Plans model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $providerDriverSubscription = new \yii\data\ArrayDataProvider([
            'allModels' => $model->driverSubscriptions,
        ]);
        $providerDriverSubscriptionLogs = new \yii\data\ArrayDataProvider([
            'allModels' => $model->driverSubscriptionLogs,
        ]);
        return $this->render('view', [
            'model' => $this->findModel($id),
            'providerDriverSubscription' => $providerDriverSubscription,
            'providerDriverSubscriptionLogs' => $providerDriverSubscriptionLogs,
        ]);
    }
    // [doc:end] actionView

    // [doc:start] actionCreate - /admin/plans/create - Creates a new Plans model.
    /**
     * Creates a new Plans model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Plans();

        if ($model->loadAll(Yii::$app->request->post()) && $model->saveAll()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }
    // [doc:end] actionCreate

    // [doc:start] actionUpdate - /admin/plans/update - Updates an existing Plans model.
    /**
     * Updates an existing Plans model.
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

    // [doc:start] actionDelete - /admin/plans/delete - Deletes an existing Plans model.
    /**
     * Deletes an existing Plans model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
      
        $model = $this->findModel($id);
        if(!empty($model)){
            $model->status = Plans::STATUS_DELETE;
            $model->save(false); 
        }

        return $this->redirect(['index']);
    }
    // [doc:end] actionDelete
    
    // [doc:start] actionUpdateStatus - /admin/plans/update-status - Update Status
    public function actionUpdateStatus(){
		$data =[];
		$post = \Yii::$app->request->post();
		\Yii::$app->response->format = 'json';
		if (! empty ( $post ['id'] ) ) {
			$model = Plans::find()->where([
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
     * Finds the Plans model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Plans the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Plans::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
        }
    }
    
    // [doc:start] actionAddDriverSubscription - /admin/plans/add-driver-subscription - Action to load a tabular form grid.
    /**
    * Action to load a tabular form grid
    * for DriverSubscription
    * @author Yohanes Candrajaya <moo.tensai@gmail.com>
    * @author Jiwantoro Ndaru <jiwanndaru@gmail.com>
    *
    * @return mixed
    */
    public function actionAddDriverSubscription()
    {
        if (Yii::$app->request->isAjax) {
            $row = Yii::$app->request->post('DriverSubscription');
            if (!empty($row)) {
                $row = array_values($row);
            }
            if((Yii::$app->request->post('isNewRecord') && Yii::$app->request->post('_action') == 'load' && empty($row)) || Yii::$app->request->post('_action') == 'add')
                $row[] = [];
            return $this->renderAjax('_formDriverSubscription', ['row' => $row]);
        } else {
            throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
        }
    }
    // [doc:end] actionAddDriverSubscription
    
    // [doc:start] actionAddDriverSubscriptionLogs - /admin/plans/add-driver-subscription-logs - Action to load a tabular form grid.
    /**
    * Action to load a tabular form grid
    * for DriverSubscriptionLogs
    * @author Yohanes Candrajaya <moo.tensai@gmail.com>
    * @author Jiwantoro Ndaru <jiwanndaru@gmail.com>
    *
    * @return mixed
    */
    public function actionAddDriverSubscriptionLogs()
    {
        if (Yii::$app->request->isAjax) {
            $row = Yii::$app->request->post('DriverSubscriptionLogs');
            if (!empty($row)) {
                $row = array_values($row);
            }
            if((Yii::$app->request->post('isNewRecord') && Yii::$app->request->post('_action') == 'load' && empty($row)) || Yii::$app->request->post('_action') == 'add')
                $row[] = [];
            return $this->renderAjax('_formDriverSubscriptionLogs', ['row' => $row]);
        } else {
            throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
        }
    }
    // [doc:end] actionAddDriverSubscriptionLogs
}
