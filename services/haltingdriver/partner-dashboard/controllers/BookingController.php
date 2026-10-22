<?php

namespace app\modules\admin\controllers;

use Yii;
use app\models\User;
use app\modules\admin\models\Booking;
use app\modules\admin\models\search\BookingSearch;
use app\modules\dispatch\services\BookingAdmin;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * Dashboard: driver-service bookings.
 *
 *   lists ....... index (waiting for driver), driver-accepted, go-for-pickup, arrived-location, start-ride,
 *                 completed-ride, cancel-ride, auto-canceled-bookings
 *   one booking . view (details + the management panel), manage (POST: cancel / reopen / assign / progress),
 *                 update-status (POST, from the grid), update, delete
 *
 * Changes go through BookingAdmin (modules/dispatch/services) so offers, history and notifications stay
 * consistent; only admins and sub-admins may change a booking, managers and franchise users may look.
 */
class BookingController extends Controller
{
    // [doc:start] behaviors - Behaviors
    public function behaviors()
    {
        return [ 
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                    'manage' => ['post'],
                    'update-status' => ['post'],
                ],
            ],
            'access' => [
                'class' => \yii\filters\AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => [
                            'index',
                            'view',
                            'create',
                            'update',
                            'delete',
                            'update-status',
                            'manage',

                            'driver-accepted',
                            'go-for-pickup',
                            'arrived-location',
                            'start-ride',
                            'completed-ride',
                            'cancel-ride',
                          'auto-canceled-bookings',

                        ],
                        'matchCallback' => function () {
                            return User::isAdmin() || User::isSubAdmin();
                        },
                    ],
                    [
                        'allow' => true,
                        'actions' => [
                            'index',
                            'view',
                            'update',
                            'pdf',
                            'update-status',

                            'driver-accepted',
                            'go-for-pickup',
                            'arrived-location',
                            'start-ride',
                            'completed-ride',
                        ],
                        'matchCallback' => function () {
                            return User::isManager();
                        },
                    ],
                    [
                        'allow' => true,
                        'actions' => [
                            'index',
                            'view',
                            'update',
                            'pdf',
                            'update-status',

                            'driver-accepted',
                            'go-for-pickup',
                            'arrived-location',
                            'start-ride',
                            'completed-ride',
                        ],
                        'matchCallback' => function () {
                            return User::isFranchise();
                        },
                    ],
                    [
                        'allow' => false,
                    ],
                ],
            ],
        ];
    }
    // [doc:end] behaviors

    // [doc:start] actionIndex - /admin/booking/index - Lists all Booking models.
    /**
     * Lists all Booking models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new BookingSearch();
        if (\Yii::$app->user->identity->user_role == User::ROLE_ADMIN ||\Yii::$app->user->identity->user_role == User::ROLE_SUBADMIN) {
            
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams,Booking::STATUS_WAITING_FOR_DRIVER);
        } elseif (\Yii::$app->user->identity->user_role == User::ROLE_MANAGER) {
            $dataProvider = $searchModel->managersearch(Yii::$app->request->queryParams,Booking::STATUS_WAITING_FOR_DRIVER );
        }
        elseif (\Yii::$app->user->identity->user_role == User::ROLE_FRANCHISE){
            $dataProvider = $searchModel->managersearch(Yii::$app->request->queryParams,Booking::STATUS_WAITING_FOR_DRIVER);
        }
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    // [doc:end] actionIndex


    // [doc:start] actionDriverAccepted - /admin/booking/driver-accepted - Driver Accepted
    public function actionDriverAccepted()
    {
        $searchModel = new BookingSearch();
        if (\Yii::$app->user->identity->user_role == User::ROLE_ADMIN ||\Yii::$app->user->identity->user_role == User::ROLE_SUBADMIN) {
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams,Booking::STATUS_DRIVER_ACCEPTED);
        } elseif (\Yii::$app->user->identity->user_role == User::ROLE_MANAGER) 
        {
            $dataProvider = $searchModel->managersearch(Yii::$app->request->queryParams,Booking::STATUS_DRIVER_ACCEPTED);
        } elseif (\Yii::$app->user->identity->user_role == User::ROLE_FRANCHISE) 
        {
            $dataProvider = $searchModel->managersearch(Yii::$app->request->queryParams,Booking::STATUS_DRIVER_ACCEPTED);
        }
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    // [doc:end] actionDriverAccepted


    // [doc:start] actionGoForPickup - /admin/booking/go-for-pickup - Go For Pickup
    public function actionGoForPickup()
    {
        $searchModel = new BookingSearch();
        if (\Yii::$app->user->identity->user_role == User::ROLE_ADMIN ||\Yii::$app->user->identity->user_role == User::ROLE_SUBADMIN) {
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams,Booking::STATUS_GO_FOR_PICKUP);
        } elseif (\Yii::$app->user->identity->user_role == User::ROLE_MANAGER) 
        {
            $dataProvider = $searchModel->managersearch(Yii::$app->request->queryParams,Booking::STATUS_GO_FOR_PICKUP);
        }
        elseif (\Yii::$app->user->identity->user_role == User::ROLE_FRANCHISE) 
        {
            $dataProvider = $searchModel->managersearch(Yii::$app->request->queryParams,Booking::STATUS_GO_FOR_PICKUP);
        }
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    // [doc:end] actionGoForPickup



    // [doc:start] actionArrivedLocation - /admin/booking/arrived-location - Arrived Location
    public function actionArrivedLocation()
    {
        $searchModel = new BookingSearch();
        if (\Yii::$app->user->identity->user_role == User::ROLE_ADMIN ||\Yii::$app->user->identity->user_role == User::ROLE_SUBADMIN) {
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams,Booking::STATUS_ARRIVED_LOCATION);
        } elseif (\Yii::$app->user->identity->user_role == User::ROLE_MANAGER) 
        {
            $dataProvider = $searchModel->managersearch(Yii::$app->request->queryParams,Booking::STATUS_ARRIVED_LOCATION);
        }elseif (\Yii::$app->user->identity->user_role == User::ROLE_FRANCHISE) 
        {
            $dataProvider = $searchModel->managersearch(Yii::$app->request->queryParams,Booking::STATUS_ARRIVED_LOCATION);
        }
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    // [doc:end] actionArrivedLocation



    // [doc:start] actionStartRide - /admin/booking/start-ride - Start Ride
    public function actionStartRide()
    {
        $searchModel = new BookingSearch();
        if (\Yii::$app->user->identity->user_role == User::ROLE_ADMIN ||\Yii::$app->user->identity->user_role == User::ROLE_SUBADMIN) {
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams,Booking::STATUS_START_RIDE);
        } elseif (\Yii::$app->user->identity->user_role == User::ROLE_MANAGER) 
        {
            $dataProvider = $searchModel->managersearch(Yii::$app->request->queryParams,Booking::STATUS_START_RIDE);
        }
        elseif (\Yii::$app->user->identity->user_role == User::ROLE_FRANCHISE) 
        {
            $dataProvider = $searchModel->managersearch(Yii::$app->request->queryParams,Booking::STATUS_START_RIDE);
        }
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    // [doc:end] actionStartRide


    
    // [doc:start] actionCompletedRide - /admin/booking/completed-ride - Completed Ride
    public function actionCompletedRide()
    {
        $searchModel = new BookingSearch();
        if (\Yii::$app->user->identity->user_role == User::ROLE_ADMIN ||\Yii::$app->user->identity->user_role == User::ROLE_SUBADMIN) {
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams,Booking::STATUS_RIDE_COMPLETED);
        } elseif (\Yii::$app->user->identity->user_role == User::ROLE_MANAGER) 
        {
            $dataProvider = $searchModel->managersearch(Yii::$app->request->queryParams,Booking::STATUS_RIDE_COMPLETED);
        }
        elseif (\Yii::$app->user->identity->user_role == User::ROLE_FRANCHISE) 
        {
            $dataProvider = $searchModel->managersearch(Yii::$app->request->queryParams,Booking::STATUS_RIDE_COMPLETED);
        }
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    // [doc:end] actionCompletedRide


    // [doc:start] actionCancelRide - /admin/booking/cancel-ride - Cancel Ride
    public function actionCancelRide()
    {
        $searchModel = new BookingSearch();
        if (\Yii::$app->user->identity->user_role == User::ROLE_ADMIN ||\Yii::$app->user->identity->user_role == User::ROLE_SUBADMIN) {
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams,Booking::STATUS_USER_CANCELLED);
        } elseif (\Yii::$app->user->identity->user_role == User::ROLE_MANAGER) 
        {
            $dataProvider = $searchModel->managersearch(Yii::$app->request->queryParams,Booking::STATUS_USER_CANCELLED);
        }
        elseif (\Yii::$app->user->identity->user_role == User::ROLE_FRANCHISE) 
        {
            $dataProvider = $searchModel->managersearch(Yii::$app->request->queryParams,Booking::STATUS_DRIVER_CANCELLED);
        }
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    // [doc:end] actionCancelRide
  
  
    // [doc:start] actionAutoCanceledBookings - /admin/booking/auto-canceled-bookings - Auto Canceled Bookings
    public function actionAutoCanceledBookings()
{
    $searchModel = new BookingSearch();
    $dataProvider = $searchModel->search(Yii::$app->request->queryParams, Booking::STATUS_CANCELLED);

    return $this->render('index', [
        'searchModel' => $searchModel,
        'dataProvider' => $dataProvider,
    ]);
}
    // [doc:end] actionAutoCanceledBookings







    // [doc:start] actionView - /admin/booking/view - Displays a single Booking model.
    /**
     * Displays a single Booking model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        $providerDriverRequests = new \yii\data\ArrayDataProvider([
            'allModels' => $model->driverRequests,
        ]);

        $providerVehicleVerification = new \yii\data\ArrayDataProvider([
            'allModels' => $model->vehicleVerifications,
        ]);
        return $this->render('view', [
            'model' => $this->findModel($id),

            'providerDriverRequests' => $providerDriverRequests,

            'providerVehicleVerification' => $providerVehicleVerification,
        ]);
    }
    // [doc:end] actionView

    // [doc:start] actionCreate - /admin/booking/create - Creates a new Booking model.
    /**
     * Creates a new Booking model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Booking();

        if ($model->loadAll(Yii::$app->request->post()) && $model->saveAll()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }
    // [doc:end] actionCreate

    // [doc:start] actionUpdate - /admin/booking/update - Updates an existing Booking model.
    /**
     * Updates an existing Booking model.
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

    // [doc:start] actionDelete - /admin/booking/delete - Deletes an existing Booking model.
    /**
     * Deletes an existing Booking model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
    

        return $this->redirect(['index']);
    }
    // [doc:end] actionDelete

    // [doc:start] actionUpdateStatus - /admin/booking/update-status - Status change from the bookings grid. Goes through BookingAdmin so offers, the driver and the customer.
    /**
     * Status change from the bookings grid. Goes through BookingAdmin so offers, the driver and the customer
     * are updated together; only administrators may use it.
     */
    public function actionUpdateStatus()
    {
        \Yii::$app->response->format = 'json';
        if (!(User::isAdmin() || User::isSubAdmin())) {
            \Yii::$app->response->statusCode = 403;
            return ['message' => 'Not allowed'];
        }
        $post = \Yii::$app->request->post();
        $model = !empty($post['id']) ? Booking::findOne($post['id']) : null;
        if ($model === null) {
            return ['message' => 'Booking not found'];
        }
        $val = (int) ($post['val'] ?? 0);
        $adminId = \Yii::$app->user->id;
        if ($val === Booking::STATUS_WAITING_FOR_DRIVER) {
            $result = BookingAdmin::reopen($model, $adminId);
        } elseif (in_array($val, [Booking::STATUS_CANCELLED, Booking::STATUS_USER_CANCELLED], true)) {
            $result = BookingAdmin::cancel($model, 'status changed from the bookings list', $adminId);
        } else {
            $result = BookingAdmin::setProgress($model, $val, $adminId);
        }

        return $result === BookingAdmin::OK
            ? ['message' => 'Updated', 'id' => $model->status]
            : ['message' => $result];
    }
    // [doc:end] actionUpdateStatus

    // [doc:start] actionManage - /admin/booking/manage - Booking page actions: cancel, reopen, assign, progress. POST op=.
    /**
     * Booking page actions: cancel, reopen, assign, progress. POST op=...
     */
    public function actionManage($id)
    {
        $model = $this->findModel($id);
        $post = \Yii::$app->request->post();
        $adminId = \Yii::$app->user->id;
        switch ($post['op'] ?? '') {
            case 'cancel':
                $result = BookingAdmin::cancel($model, $post['reason'] ?? '', $adminId);
                break;
            case 'reopen':
                $result = BookingAdmin::reopen($model, $adminId);
                break;
            case 'assign':
                $ref = trim((string) ($post['driver'] ?? ($post['driver_id'] ?? '')));
                $driverId = 0;
                if ($ref !== '') {
                    $found = User::find()->select('id')->where(['user_role' => User::ROLE_DRIVER])
                        ->andWhere(['or', ['id' => ctype_digit($ref) && strlen($ref) <= 7 ? (int) $ref : 0], ['contact_no' => $ref]])
                        ->scalar();
                    $driverId = (int) $found;
                }
                $result = $driverId ? BookingAdmin::assignDriver($model, $driverId, $adminId) : 'No driver found with that id or mobile number.';
                break;
            case 'progress':
                $result = BookingAdmin::setProgress($model, $post['state'] ?? 0, $adminId);
                break;
            default:
                $result = 'Unknown action.';
        }
        \Yii::$app->session->setFlash($result === BookingAdmin::OK ? 'success' : 'error',
            $result === BookingAdmin::OK ? 'Booking updated.' : $result);

        return $this->redirect(['view', 'id' => $model->id]);
    }
    // [doc:end] actionManage

    /**
     * Finds the Booking model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Booking the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Booking::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(
                'The requested page does not exist.'
            );
        }
    }
}
