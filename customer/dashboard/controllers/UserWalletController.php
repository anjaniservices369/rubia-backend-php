<?php

namespace app\modules\admin\controllers;

use Yii;
use app\models\User;
use app\modules\admin\models\UserWallet;
use app\modules\admin\models\search\UserWalletSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * UserWalletController implements the CRUD actions for UserWallet model.
 */
class UserWalletController extends Controller
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

    // [doc:start] actionIndex - /admin/user-wallet/index - Lists all UserWallet models.
    /**
     * Lists all UserWallet models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new UserWalletSearch();
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

    // [doc:start] actionView - /admin/user-wallet/view - Displays a single UserWallet model.
    /**
     * Displays a single UserWallet model.
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

    // [doc:start] actionCreate - /admin/user-wallet/create - Creates a new UserWallet model.
    /**
     * Creates a new UserWallet model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new UserWallet();

        if ($model->loadAll(Yii::$app->request->post()) && $model->saveAll()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }
    // [doc:end] actionCreate

    // [doc:start] actionUpdate - /admin/user-wallet/update - Updates an existing UserWallet model.
    /**
     * Updates an existing UserWallet model.
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

    // [doc:start] actionDelete - /admin/user-wallet/delete - Deletes an existing UserWallet model.
    /**
     * Deletes an existing UserWallet model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
      
        
    }
    // [doc:end] actionDelete
    
    // [doc:start] actionUpdateStatus - /admin/user-wallet/update-status - Update Status
    public function actionUpdateStatus(){
		$data =[];
		$post = \Yii::$app->request->post();
		\Yii::$app->response->format = 'json';
		if (! empty ( $post ['id'] ) ) {
			$model = UserWallet::find()->where([
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
     * Finds the UserWallet model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return UserWallet the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = UserWallet::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
        }
    }
}
