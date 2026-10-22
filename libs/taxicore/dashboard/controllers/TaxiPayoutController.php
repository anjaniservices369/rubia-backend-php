<?php

namespace app\modules\admin\controllers;

use Yii;
use app\models\User;
use app\modules\admin\models\base\RideEarnings;
use app\modules\admin\models\base\RideRequest;
use app\modules\admin\models\TaxiPayout;
use app\modules\admin\models\search\TaxiPayoutSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * TaxiPayoutController implements the CRUD actions for TaxiPayout model.
 */
class TaxiPayoutController extends Controller
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
                        'actions' => ['index', 'view', 'create', 'update', 'delete', 'update-status', 'pending-withdraw-request', 'approved-withdraw-request'],
                        'matchCallback' => function () {
                            return User::isAdmin() || User::isSubAdmin();
                        }

                    ],
                    [
                        'allow' => true,
                        'actions' => ['index', 'view', 'update', 'pdf', 'update-status', 'pending-withdraw-request'],
                        'matchCallback' => function () {
                            return User::isManager();
                        }
                    ],
                    [
                        'allow' => true,
                        'actions' => ['index', 'view', 'create', 'update', 'delete', 'update-status', 'pending-withdraw-request', 'approved-withdraw-request'],
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

    // [doc:start] actionIndex - /admin/taxi-payout/index - Lists all TaxiPayout models.
    /**
     * Lists all TaxiPayout models.
     * @return mixed
     */
    public function actionIndex()
    {
        $vehicle_id = Yii::$app->request->get('vehicle_id');
        $searchModel = new TaxiPayoutSearch();
        if (\Yii::$app->user->identity->user_role == User::ROLE_ADMIN || \Yii::$app->user->identity->user_role == User::ROLE_SUBADMIN) {
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams,$vehicle_id);
        } else if (\Yii::$app->user->identity->user_role == User::ROLE_MANAGER) {
            $dataProvider = $searchModel->managersearch(Yii::$app->request->queryParams);
        }else if (\Yii::$app->user->identity->user_role == User::ROLE_FRANCHISE) {
            $dataProvider = $searchModel->managersearch(Yii::$app->request->queryParams,$vehicle_id);
        }
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    // [doc:end] actionIndex

    // [doc:start] actionPendingWithdrawRequest - /admin/taxi-payout/pending-withdraw-request - Pending Withdraw Request
    public function actionPendingWithdrawRequest()
    {
        $vehicle_id = Yii::$app->request->get('vehicle_id');
        $searchModel = new TaxiPayoutSearch();
        if (\Yii::$app->user->identity->user_role == User::ROLE_ADMIN || \Yii::$app->user->identity->user_role == User::ROLE_SUBADMIN) {
            $dataProvider = $searchModel->withdrawrequestsearch(Yii::$app->request->queryParams, TaxiPayout::STATUS_PROCESSING,$vehicle_id);
        } else if (\Yii::$app->user->identity->user_role == User::ROLE_MANAGER) {
            $dataProvider = $searchModel->withdrawrequestsearch(Yii::$app->request->queryParams, TaxiPayout::STATUS_PROCESSING,$vehicle_id);
        } else if (\Yii::$app->user->identity->user_role == User::ROLE_FRANCHISE) {
            $dataProvider = $searchModel->withdrawrequestsearchmanager(Yii::$app->request->queryParams, TaxiPayout::STATUS_PROCESSING,$vehicle_id);
        }
        return $this->render('withdraw_view', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    // [doc:end] actionPendingWithdrawRequest


    // [doc:start] actionApprovedWithdrawRequest - /admin/taxi-payout/approved-withdraw-request - Approved Withdraw Request
    public function actionApprovedWithdrawRequest()
    {
        $vehicle_id = Yii::$app->request->get('vehicle_id');
        $searchModel = new TaxiPayoutSearch();
        if (\Yii::$app->user->identity->user_role == User::ROLE_ADMIN || \Yii::$app->user->identity->user_role == User::ROLE_SUBADMIN) {
            $dataProvider = $searchModel->withdrawrequestsearch(Yii::$app->request->queryParams, TaxiPayout::STATUS_APPROVED,$vehicle_id);
        } else if (\Yii::$app->user->identity->user_role == User::ROLE_MANAGER) {
            $dataProvider = $searchModel->withdrawrequestsearch(Yii::$app->request->queryParams, TaxiPayout::STATUS_APPROVED,$vehicle_id);
        } else if (\Yii::$app->user->identity->user_role == User::ROLE_FRANCHISE) {
            $dataProvider = $searchModel->withdrawrequestsearchmanager(Yii::$app->request->queryParams, TaxiPayout::STATUS_APPROVED,$vehicle_id);
        }
        return $this->render('withdraw_view', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    // [doc:end] actionApprovedWithdrawRequest

    // [doc:start] actionView - /admin/taxi-payout/view - Displays a single TaxiPayout model.
    /**
     * Displays a single TaxiPayout model.
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

    // [doc:start] actionCreate - /admin/taxi-payout/create - Creates a new TaxiPayout model.
    /**
     * Creates a new TaxiPayout model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $post = Yii::$app->request->post();

        $model = new TaxiPayout();
        if (!empty($post)) {
            if ((int)$post['TaxiPayout']['payment_type'] == TaxiPayout::PAYMENT_TYPE_CREDIT) {
                $model = new RideEarnings();
                $model->driver_id = $post['TaxiPayout']['driver_id'];
                $model->total_amount = $post['TaxiPayout']['amount'];
                $model->driver_earning = $post['TaxiPayout']['amount'];
                $model->refrence_id = $post['TaxiPayout']['refrence_id'];
                $model->method_reason = $post['TaxiPayout']['method_reason'];
                $model->payment_method = RideRequest::PAYMENT_METHOD_ONLINE;
                $model->status = RideEarnings::STATUS_APPROVED;
                $model->type = RideEarnings::MANUL;
                if ($model->save(false)) {
                    return $this->redirect(['ride-earnings/manual-payout', 'id' => $model->id]);
                }
            } else {
                $model = new TaxiPayout();

                if ($model->loadAll(Yii::$app->request->post()) && $model->saveAll()) {
                    return $this->redirect(['index', 'id' => $model->id]);
                } else {
                    return $this->render('create', [
                        'model' => $model,
                    ]);
                }
            }
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }
    // [doc:end] actionCreate

    // [doc:start] actionUpdate - /admin/taxi-payout/update - Updates an existing TaxiPayout model.
    /**
     * Updates an existing TaxiPayout model.
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

    // [doc:start] actionDelete - /admin/taxi-payout/delete - Deletes an existing TaxiPayout model.
    /**
     * Deletes an existing TaxiPayout model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {

        $model = $this->findModel($id);
        if (!empty($model)) {
            $model->status = TaxiPayout::STATUS_DELETE;
            $model->save(false);
        }

        return $this->redirect(['index']);
    }
    // [doc:end] actionDelete

    // [doc:start] actionUpdateStatus - /admin/taxi-payout/update-status - Update Status
    public function actionUpdateStatus()
    {
        $data = [];
        $post = \Yii::$app->request->post();
        \Yii::$app->response->format = 'json';
        if (!empty($post['id'])) {
            $model = TaxiPayout::find()->where([
                'id' => $post['id'],
            ])->one();
            if (!empty($model)) {

                $model->status = $post['val'];
            }
            if ($model->save(false)) {
                $data['message'] = "Updated";
                $data['id'] = $model->status;
            } else {
                $data['message'] = "Not Updated";
            }
        }
        return $data;
    }
    // [doc:end] actionUpdateStatus


    /**
     * Finds the TaxiPayout model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return TaxiPayout the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = TaxiPayout::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
        }
    }
}
