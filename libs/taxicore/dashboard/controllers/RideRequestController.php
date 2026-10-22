<?php

namespace app\modules\admin\controllers;

use Yii;
use app\models\User;
use app\modules\admin\models\RideEarnings;
use app\modules\admin\models\RideRequest;
use app\modules\admin\models\search\RideRequestSearch;
use app\modules\admin\models\TaxiRating;
use kartik\mpdf\Pdf;
use Mpdf\Mpdf;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * RideRequestController implements the CRUD actions for RideRequest model.
 */
class RideRequestController extends Controller
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
                        'actions' => ['index', 'view', 'create', 'update', 'delete', 'update-status', 'orders-panel', 'driver-locations', 'print-page','completed-rides','new-rides','on-going-rides','bike','car'],
                        'matchCallback' => function () {
                            return User::isAdmin();
                        }

                    ],
                    [
                        'allow' => true,
                        'actions' => ['index', 'view', 'update', 'pdf', 'update-status', 'orders-panel', 'print-page'],
                        'matchCallback' => function () {
                            // return User::isManager();
                        }
                    ],
                    [
                        'allow' => true,
                        'actions' => ['index', 'view', 'create', 'update', 'delete', 'update-status', 'orders-panel', 'driver-locations', 'print-page','completed-rides','new-rides','on-going-rides','bike','car'],
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

    // [doc:start] actionIndex - /admin/ride-request/index - Lists all RideRequest models.
    /**
     * Lists all RideRequest models.
     * @return mixed
     */
    public function actionIndex()
    {
        $vehicle_id = Yii::$app->request->get('vehicle_id');
        $searchModel = new RideRequestSearch();
        if (\Yii::$app->user->identity->user_role == User::ROLE_ADMIN || \Yii::$app->user->identity->user_role == User::ROLE_SUBADMIN) {
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams,'',$vehicle_id);
        } else if (\Yii::$app->user->identity->user_role == User::ROLE_MANAGER) {
            $dataProvider = $searchModel->managersearch(Yii::$app->request->queryParams);
        } else if (\Yii::$app->user->identity->user_role == User::ROLE_FRANCHISE) {
            $dataProvider = $searchModel->searchManager(Yii::$app->request->queryParams,'',$vehicle_id,\Yii::$app->user->identity->city_id);
        }
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    // [doc:end] actionIndex

    // [doc:start] actionNewRides - /admin/ride-request/new-rides - New Rides
    public function actionNewRides()
    {
        $vehicle_id = Yii::$app->request->get('vehicle_id');
        $searchModel = new RideRequestSearch();
        if (\Yii::$app->user->identity->user_role == User::ROLE_ADMIN || \Yii::$app->user->identity->user_role == User::ROLE_SUBADMIN) {
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams,RideRequest::STATUS_NEW_REQUEST);
        } else if (\Yii::$app->user->identity->user_role == User::ROLE_MANAGER) {
            $dataProvider = $searchModel->managersearch(Yii::$app->request->queryParams);
        }
        else if (\Yii::$app->user->identity->user_role == User::ROLE_FRANCHISE) {
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams,RideRequest::STATUS_NEW_REQUEST,$vehicle_id,\Yii::$app->user->identity->city_id);
        }
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    // [doc:end] actionNewRides

    // [doc:start] actionOnGoingRides - /admin/ride-request/on-going-rides - On Going Rides
    public function actionOnGoingRides()
    {
        $vehicle_id = Yii::$app->request->get('vehicle_id');
        $searchModel = new RideRequestSearch();
        if (\Yii::$app->user->identity->user_role == User::ROLE_ADMIN || \Yii::$app->user->identity->user_role == User::ROLE_SUBADMIN) {
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams,RideRequest::STATUS_START_RIDE);
        } else if (\Yii::$app->user->identity->user_role == User::ROLE_MANAGER) {
            $dataProvider = $searchModel->managersearch(Yii::$app->request->queryParams);
        }
        else if (\Yii::$app->user->identity->user_role == User::ROLE_FRANCHISE) {
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams,RideRequest::STATUS_START_RIDE,$vehicle_id,\Yii::$app->user->identity->city_id);
        }
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    // [doc:end] actionOnGoingRides

    // [doc:start] actionCompletedRides - /admin/ride-request/completed-rides - Completed Rides
    public function actionCompletedRides()
    {
        $vehicle_id = Yii::$app->request->get('vehicle_id');
        $searchModel = new RideRequestSearch();
        if (\Yii::$app->user->identity->user_role == User::ROLE_ADMIN || \Yii::$app->user->identity->user_role == User::ROLE_SUBADMIN) {
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams,RideRequest::STATUS_RIDE_COMPLETED_PAID);
        } else if (\Yii::$app->user->identity->user_role == User::ROLE_MANAGER) {
            $dataProvider = $searchModel->managersearch(Yii::$app->request->queryParams);
        }
        else if (\Yii::$app->user->identity->user_role == User::ROLE_FRANCHISE) {
            $dataProvider = $searchModel->searchFranchise(Yii::$app->request->queryParams,RideRequest::STATUS_RIDE_COMPLETED_PAID,$vehicle_id,\Yii::$app->user->identity->city_id);
        }
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    // [doc:end] actionCompletedRides



    // [doc:start] actionView - /admin/ride-request/view - Displays a single RideRequest model.
    /**
     * Displays a single RideRequest model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $rideEarnings = RideEarnings::find()->where(['ride_id' => $model->id])->one();
        $rideReviews = TaxiRating::find()->where(['ride_id' => $model->id])->one();
        if (!empty($rideEarnings)) {
            return $this->render('view', [
                'model' => $this->findModel($id),
                'rideEarnings' => $rideEarnings,
                'rideReviews' => $rideReviews,
            ]);
        } else {
            $rideEarnings = '';
            return $this->render('view', [
                'model' => $this->findModel($id),
                'rideEarnings' => $rideEarnings,
                'rideReviews' => $rideReviews,

            ]);
        }
    }
    // [doc:end] actionView

    // [doc:start] actionPrintPage - /admin/ride-request/print-page - Print Page
    public function actionPrintPage($ride_id)
    {
        $this->layout = 'blank';
        $rideRequest = RideRequest::find()->where(['id' => $ride_id])->one();
        // $content = $this->render('_printpage', ['ride_request' => $rideRequest]);
        $content = $this->renderPartial('_printpage', ['ride_request' => $rideRequest]);
        // return $content;
        Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;

        // setup kartik\mpdf\Pdf component
        $pdf = new Pdf([
            // set to use core fonts only
            'mode' => Pdf::MODE_CORE,
            // A4 paper format
            'format' => Pdf::FORMAT_A4,
            // portrait orientation
            'orientation' => Pdf::ORIENT_PORTRAIT,
            // stream to browser inline
            'destination' => Pdf::DEST_BROWSER,
            // your html content input
            'content' => $content,
            // format content from your own css file if needed or use the
            // enhanced bootstrap css built by Krajee for mPDF formatting 
            'cssFile' => '@vendor/kartik-v/yii2-mpdf/src/assets/kv-mpdf-bootstrap.min.css',
            // any css to be embedded if required
            'cssInline' => '',
            // set mPDF properties on the fly
            'options' => ['title' => false],
            // call mPDF methods on the fly
            'methods' => [
                'SetHeader' => false,
                'SetFooter' => false,
            ]
        ]);

        // return the pdf output as per the destination setting
        return $pdf->render();
        // return $content;
    }
    // [doc:end] actionPrintPage

    // [doc:start] actionCreate - /admin/ride-request/create - Creates a new RideRequest model.
    /**
     * Creates a new RideRequest model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new RideRequest();

        if ($model->loadAll(Yii::$app->request->post()) && $model->saveAll()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }
    // [doc:end] actionCreate

    // [doc:start] actionUpdate - /admin/ride-request/update - Updates an existing RideRequest model.
    /**
     * Updates an existing RideRequest model.
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

    /**
     * Deletes an existing RideRequest model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    // public function actionDelete($id)
    // {

    //     $model = $this->findModel($id);
    //     if (!empty($model)) {
    //         $model->status = RideRequest::STATUS_DELETE;
    //         $model->save(false);
    //     }

    //     return $this->redirect(['index']);
    // }

    // [doc:start] actionUpdateStatus - /admin/ride-request/update-status - Update Status
    public function actionUpdateStatus()
    {
        $data = [];
        $post = \Yii::$app->request->post();
        \Yii::$app->response->format = 'json';
        if (!empty($post['id'])) {
            $model = RideRequest::find()->where([
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
     * Finds the RideRequest model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return RideRequest the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = RideRequest::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
        }
    }


    // [doc:start] actionDriverLocations - /admin/ride-request/driver-locations - Online drivers with their last known position (kept by the API's update-lat-lng).
    /**
     * Online drivers with their last known position (kept by the API's update-lat-lng).
     * Polled by the orders panel map.
     */
    public function actionDriverLocations()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $rows = User::find()
            ->select(['id', 'lat', 'lng'])
            ->where(['user_role' => User::ROLE_DRIVER, 'online_status' => 1])
            ->andWhere(['not', ['lat' => null]])
            ->andWhere(['not', ['lng' => null]])
            ->andWhere(['<>', 'lat', ''])
            ->asArray()
            ->all();

        return array_map(function ($r) {
            return ['id' => (int) $r['id'], 'lat' => (float) $r['lat'], 'lng' => (float) $r['lng']];
        }, $rows);
    }
    // [doc:end] actionDriverLocations

    // [doc:start] actionOrdersPanel - /admin/ride-request/orders-panel - Orders Panel
    public function actionOrdersPanel()
    {
        return $this->render('orders_panel');
    }
    // [doc:end] actionOrdersPanel
}
