<?php

namespace app\modules\haltingdrivercustomer\controllers;

use app\modules\api\controllers\BKController;
use yii;
use yii\filters\AccessControl;
use yii\filters\AccessRule;
use yii\helpers\ArrayHelper;
use app\modules\auth\services\AuthSettings;
use yii\data\ActiveDataProvider;
use yii\helpers\Url;
use app\models\User;
use app\modules\admin\models\Auth;
use app\modules\admin\models\WebSetting;
use app\modules\admin\models\AuthSession;
use app\modules\admin\models\base\Booking as BaseBooking;
use app\modules\admin\models\Booking;
use app\modules\admin\models\City;
use app\modules\admin\models\Coupon;
use app\modules\admin\models\FavouriteLocation;
use app\modules\admin\models\FcmNotification;
use app\modules\admin\models\Notification;
use app\modules\admin\models\Services;
use app\modules\admin\models\UserEmergencyContacts;
use app\modules\admin\models\base\RideRequest;
use app\modules\admin\models\RideRequest as ModelsRideRequest;
use Exception;
use kartik\mpdf\Pdf;
use yii\helpers\Json;

/**
 * Customer app API: /api/booking/*  - only a booking list (list-bookings). Everything else about bookings is in
 * UserController (/api/user/*); see DEVELOPER_GUIDE.md.
 */
class BookingController extends BKController

{

    // [doc:start] behaviors - Behaviors
    public function behaviors()
    {

        return ArrayHelper::merge(parent::behaviors(), [

            'corsFilter' => [
                'class' => \yii\filters\Cors::className(),
                'cors' => [
                    // restrict access to
                    'Origin' => ['http://localhost:*', 'http://localhost:58600'],
                    // Allow only POST and PUT methods
                    'Access-Control-Request-Method' => ['POST', 'PUT'],
                    // Allow only headers 'X-Wsse'
                    'Access-Control-Request-Headers' => ['X-Wsse'],
                    // Allow credentials (cookies, authorization headers, etc.) to be exposed to the browser
                    'Access-Control-Allow-Credentials' => true,
                    // Allow OPTIONS caching
                    'Access-Control-Max-Age' => 3600,
                    // Allow the X-Pagination-Current-Page header to be exposed to the browser.
                    'Access-Control-Expose-Headers' => ['X-Pagination-Current-Page'],
                ],
            ],

            'access' => [
                'class' => AccessControl::className(),
                'ruleConfig' => [

                    'class' => AccessRule::className()
                ],

                'rules' => [
                    [
                        'actions' => [

                            'list-bookings'


                        ],

                        'allow' => true,
                        'roles' => [
                            '@'
                        ]
                    ],
                    [

                        'actions' => [

                            'list-bookings'



                        ],

                        'allow' => true,
                        'roles' => [

                            '?',
                            '*',

                        ]
                    ]
                ]
            ]

        ]);
    }
    // [doc:end] behaviors


    // [doc:start] actionIndex - Index
    public function actionIndex()
    {
        $data['details'] =  ['hello'];
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionIndex
    // [doc:start] actionListBookings - List Bookings
    public function actionListBookings()
    {
        $data = [];

        // Fetch the auth code from the request headers or query parameters
        $authCode = Yii::$app->request->headers->get('auth_code', Yii::$app->request->getQueryParam('auth_code'));

        // Fetch the user ID based on the auth code
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($authCode);

        if (!empty($user_id)) {
            $status = Yii::$app->request->get('status'); // Get the status from the request parameters
            $vehicle_id = Yii::$app->request->get('vehicle_id')??0; // Get the vehicle id from the request parameters

            if ($vehicle_id == 0 || $vehicle_id == 1 || $vehicle_id == '1' ) {
                $query = Booking::find()
                    ->where(['user_id' => $user_id]);

                if (!empty($status)) {
                    if ($status == Booking::STATUS_CANCELLED) {
                        $query->andWhere(['in', 'status', [Booking::STATUS_DRIVER_CANCELLED, Booking::STATUS_USER_CANCELLED]]);
                    } else {
                        $query->andWhere(['status' => $status]);
                    }
                } else {
                    $query->andWhere(['in', 'status', [
                        Booking::STATUS_DRIVER_ACCEPTED,
                        Booking::STATUS_START_RIDE,
                        Booking::STATUS_ARRIVED_LOCATION,
                        Booking::STATUS_GO_FOR_PICKUP,
                        // Booking::STATUS_RIDE_COMPLETED,
                        Booking::STATUS_WAITING_FOR_DRIVER,
                    ]]);
                }


                $query->orderBy(['created_on' => SORT_DESC]);

                $dataProvider = new ActiveDataProvider([
                    'query' => $query,
                    'pagination' => [
                        'pageSize' => 10, // Set the number of items per page
                        'page' => Yii::$app->request->get('page', 0), // Get the page number from the request, default to 1 if not provided
                    ],
                ]);

                $list = [];
                foreach ($dataProvider->getModels() as $booking) {
                    $list[] = $booking->asJson();
                }

                if (!empty($list)) {
                    $data['status'] = self::API_OK;
                    $data['bookings'] = $list;
                    $data['pagination'] = [
                        'total_pages' => $dataProvider->getPagination()->getPageCount(),
                        'current_page' => $dataProvider->getPagination()->getPage() + 1,
                        'per_page' => $dataProvider->getPagination()->getPageSize(),
                        'total_count' => $dataProvider->getTotalCount(),
                    ];
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = Yii::t("app", "No bookings found for the specified user");
                }
            } else {
                if (!class_exists(ModelsRideRequest::class)) {
                    // taxi rides are served by the taxi services' rider APIs (my-rides)
                    $data['status'] = self::API_NOK;
                    $data['error'] = Yii::t('app', 'No bookings found for the specified user');
                    return $this->sendJsonResponse($data);
                }
                $query = ModelsRideRequest::find()
                    ->where(['user_id' => $user_id]);
                if (!empty($status)) {
                    if ($status == BaseBooking::STATUS_CANCELLED) {
                        $query->andWhere(['in', 'status', [RideRequest::STATUS_CANCEL_BY_DRIVER, RideRequest::STATUS_CANCEL_BY_USER]])->andWhere(['vehical_type' => $vehicle_id]);
                    }
                    elseif($status == 7)
                    {
                        $query->andWhere(['in', 'status', [RideRequest::STATUS_RIDE_COMPLETED_PAID, RideRequest::STATUS_RIDE_COMPLETED]])->andWhere(['vehical_type' => $vehicle_id]);
                    }
                    else {
                        $query->andWhere(['status' => $status])->andWhere(['vehical_type' => $vehicle_id]);
                    }
                } else {
                    $query->andWhere(['in', 'status', [
                        RideRequest::STATUS_NEW_REQUEST,
                        RideRequest::STATUS_RIDE_BOOKED,
                        RideRequest::STATUS_ACCEPTED_BY_DRIVER,
                        RideRequest::STATUS_ON_THE_WAY,
                        RideRequest::STATUS_START_RIDE,
                        RideRequest::STATUS_ARRIVED,
                        RideRequest::STATUS_RIDE_COMPLETED,
                    ]])->andWhere(['vehical_type' => $vehicle_id]);
                }


                $query->orderBy(['created_on' => SORT_DESC]);

                $dataProvider = new ActiveDataProvider([
                    'query' => $query,
                    'pagination' => [
                        'pageSize' => 10, // Set the number of items per page
                        'page' => Yii::$app->request->get('page', 0), // Get the page number from the request, default to 1 if not provided
                    ],
                ]);

                $list = [];
                foreach ($dataProvider->getModels() as $booking) {
                    $list[] = $booking->asJson();
                }

                if (!empty($list)) {
                    $data['status'] = self::API_OK;
                    $data['bookings'] = $list;
                    $data['pagination'] = [
                        'total_pages' => $dataProvider->getPagination()->getPageCount(),
                        'current_page' => $dataProvider->getPagination()->getPage() + 1,
                        'per_page' => $dataProvider->getPagination()->getPageSize(),
                        'total_count' => $dataProvider->getTotalCount(),
                    ];
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = Yii::t("app", "No bookings found for the specified user");
                }
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = Yii::t("app", "No User found");
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionListBookings
}
