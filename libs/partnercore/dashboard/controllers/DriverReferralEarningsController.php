<?php

namespace app\modules\admin\controllers;

use Yii;
use app\models\User;
use app\modules\admin\models\DriverReferralEarnings;
use app\modules\admin\models\search\DriverReferralEarningsSearch;
use app\modules\admin\models\DriverWallet;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * DriverReferralEarningsController implements the CRUD actions for DriverReferralEarnings model.
 */
class DriverReferralEarningsController extends Controller
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

    // [doc:start] actionIndex - /admin/driver-referral-earnings/index - Lists all DriverReferralEarnings models.
    /**
     * Lists all DriverReferralEarnings models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new DriverReferralEarningsSearch();
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

    // [doc:start] actionView - /admin/driver-referral-earnings/view - Displays a single DriverReferralEarnings model.
    /**
     * Displays a single DriverReferralEarnings model.
     * 
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

    // [doc:start] actionCreate - /admin/driver-referral-earnings/create - Creates a new DriverReferralEarnings model.
    /**
     * Creates a new DriverReferralEarnings model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
       
        $model = new DriverReferralEarnings(['scenario' => 'create']);
        $model->driver_id = Yii::$app->user->id;

       

        
    
        if ($model->load(Yii::$app->request->post()) && $model->save()  ){

            $driverWallet = new DriverWallet();
            $driverWallet->driver_id =  $model->driver_id;
            $driverWallet->amount = (float) $model->earnings;
            $driverWallet->payment_type = DriverWallet::payment_type_credit;
            $driverWallet->method_reason =  $model->earnings . " credited for successful refferal";
            $driverWallet->status = DriverWallet::STATUS_SUCCESS;
            $driverWallet->created_on = date('Y-m-d H:i:s');
            $driverWallet->create_user_id =  $model->driver_id; 

          //  print_r( $driverWallet);
        
            
           $driverWallet->save(false);

            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', ['model' => $model]);
        }
    }
    // [doc:end] actionCreate
    

    // [doc:start] actionUpdate - /admin/driver-referral-earnings/update - Updates an existing DriverReferralEarnings model.
    /**
     * Updates an existing DriverReferralEarnings model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * 
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->loadAll(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);

           ; // Debugging line


        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }
    // [doc:end] actionUpdate

    // [doc:start] actionDelete - /admin/driver-referral-earnings/delete - Deletes an existing DriverReferralEarnings model.
    /**
     * Deletes an existing DriverReferralEarnings model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * 
     * @return mixed
     */
    public function actionDelete($id)
    {
      
        $model = $this->findModel($id);
        if(!empty($model)){
            $model->status = DriverReferralEarnings::STATUS_DELETE;
            $model->save(false); 
        }

        return $this->redirect(['index']);
    }
    // [doc:end] actionDelete
    
    // [doc:start] actionUpdateStatus - /admin/driver-referral-earnings/update-status - Update Status
    public function actionUpdateStatus(){
		$data =[];
		$post = \Yii::$app->request->post();
		\Yii::$app->response->format = 'json';
		if (! empty ( $post ['id'] ) ) {
			$model = DriverReferralEarnings::find()->where([
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
     * Finds the DriverReferralEarnings model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * 
     * @return DriverReferralEarnings the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = DriverReferralEarnings::findOne($id)) !== null) {

            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
        }
    }
}
