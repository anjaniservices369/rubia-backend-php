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
use app\modules\admin\models\BannerCategorys;
use app\modules\admin\models\Booking;
use app\modules\admin\models\RideRequest;
use app\modules\admin\models\City;
use app\modules\admin\models\Coupon;
use app\modules\admin\models\DriverBookingRatings;
use app\modules\admin\models\DriverRequests;
use app\modules\admin\models\DriverWallet;
use app\modules\admin\models\FavouriteLocation;
use app\modules\admin\models\FcmNotification;
use app\modules\admin\models\Notification;
use app\modules\admin\models\Services;
use app\modules\admin\models\UserBookingRatings;
use app\modules\admin\models\UserEmergencyContacts;
use app\modules\admin\models\UserReferralEarnings;
use app\modules\admin\models\VehicleTypes;
use app\modules\admin\models\CityRateCard;
use app\modules\admin\models\base\UserWallet;
use app\modules\admin\models\base\CancelRideCharges;




use Exception;
use kartik\mpdf\Pdf;
use yii\helpers\Json;
 
/**
 * Halting drivers service, customer side: /api/user/* booking flow (estimate, coupons, cancel, review, dispatch cron).
 * The user app's own endpoints (login, profile, address book, notifications) are in app/modules/customerapp.
 *
 * Who calls it: the Customer app. Authenticates with the `auth_code` header.
 *
 * Actions:
 *   login ......... send-otp, resend-otp, verify-otp, logout, check
 *   profile ....... update-profile, update-profile-pic, update-lat-lng, emergency contacts, favourite locations,
 *                   apply-referral, list-fcm-notifications (in-app notifications), clear-notifications
 *   booking ....... get-estimate (price quote; with ?saveBooking=true it creates the booking), vehicle-types,
 *                   coupons-list, apply-coupon, remove-coupon, check-boundary (is this place served)
 *   after booking . ride-info, view-booking-details, cancel-ride, cancellation-charges, add-review,
 *                   rating-and-reviews
 *   dispatch (cron) assign-booking-to-driver: offers waiting bookings to nearby drivers and cancels the ones whose
 *                   pickup time has passed. Called every minute by cron, not by the app.
 *
 * Rules enforced here: one booking in process per customer; estimate validation (car type, hours, city,
 * boundary, pickup in the future); coupon discount computed on the server; only the owner can cancel.
 */
class UserController extends BKController
{
    // [doc:start] behaviors - Behaviors
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'corsFilter' => [
                'class' => \yii\filters\Cors::className(),
                'cors' => [
                    // restrict access to
                    'Origin' => [
                        'http://localhost:*',
                        'http://localhost:58600',
                    ],
                    // Allow only POST and PUT methods
                    'Access-Control-Request-Method' => ['POST', 'PUT'],
                    // Allow only headers 'X-Wsse'
                    'Access-Control-Request-Headers' => ['X-Wsse'],
                    // Allow credentials (cookies, authorization headers, etc.) to be exposed to the browser
                    'Access-Control-Allow-Credentials' => true,
                    // Allow OPTIONS caching
                    'Access-Control-Max-Age' => 3600,
                    // Allow the X-Pagination-Current-Page header to be exposed to the browser.
                    'Access-Control-Expose-Headers' => [
                        'X-Pagination-Current-Page',
                    ],
                ],
            ],

            'access' => [
                'class' => AccessControl::className(),
                'ruleConfig' => [
                    'class' => AccessRule::className(),
                ],

                'rules' => [
                    [
                        'actions' => [
                            'check',
                            'verify-otp',
                            'send-otp',
                            'resend-otp',
                            'index',
                            'update-profile',
                            'add-user-emergency-contacts',
                            'add-emergency-contact',
                            'update-emergency-contact',
                            'list-emergency-contacts',
                            'delete-emergency-contact',
                            'update-lat-lng',
                            'apply-referral',
                            'services',
                            'logout',
                            'list-favourite-location',
                            'create-favourite-location',
                            'update-favourite-location',
                            'delete-favourite-location',
                            'list-fcm-notifications',
                            'get-estimate',
                            'coupons-list',
                            'check-boundary',
                            'vehicle-types',
                            'apply-coupon',
                            'assign-booking-to-driver',
                            'cancel-ride',
                            'ride-info',
                            'add-review',
                            'remove-coupon',
                            'rating-and-reviews',
                            'view-booking-details',
                            'clear-notifications',
                            'banner-with-category',
                            'update-profile-pic',
                            'my-referral-earings',
                            'pay-now',
                            'ride-request-active',
                            'ride-request-completed',
                            'ride-request-cancelled',
                          	'cancellation-charges',
                           'current-city',
                         
                        ],  

                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => [
                            'check',
                            'verify-otp',
                            'send-otp',
                            'resend-otp',
                            'index',
                            'update-profile',
                            'add-user-emergency-contacts',
                            'update-emergency-contact',
                            'list-emergency-contacts',
                            'delete-emergency-contact',
                            'update-lat-lng',
                            'apply-referral',
                            'services',
                            'logout',
                            'list-favourite-location',
                            'create-favourite-location',
                            'update-favourite-location',
                            'delete-favourite-location',
                            'list-fcm-notifications',
                            'get-estimate',
                            'coupons-list',
                            'check-boundary',
                            'vehicle-types',
                            'apply-coupon',
                            'assign-booking-to-driver',
                            'cancel-ride',
                            'ride-info',
                            'add-review',
                            'remove-coupon',
                            'rating-and-reviews',
                            'view-booking-details',
                            'clear-notifications',
                            'banner-with-category',
                            'update-profile-pic',
                            'my-referral-earings',
                            'pay-now',
                            'ride-request-active',
                            'ride-request-completed',
                            'ride-request-cancelled',
							'cancellation-charges',
                           'current-city',
                          

                        ],

                        'allow' => true,
                        'roles' => ['?', '*'],
                    ],
                ],
            ],
        ]);
    }
    // [doc:end] behaviors


       // [doc:start] actionGetEstimate - Price quote for a driver-service ride - and, with ?saveBooking=true, the booking itself.
       /**
        * Price quote for a driver-service ride - and, with ?saveBooking=true, the booking itself.
        *
        * POST /api/user/get-estimate[?saveBooking=true]
        *   rider_type 1 one way / 2 round trip, trip_type 1 inner city / 2 outstation, car_type 1 automatic / 2 manual,
        *   car_model, city_id, usage_hours, pickup_* and drop_* (lat, lng, address), pickup_date, pickup_time,
        *   is_vehicle_damage_protection, coupon_code
        * Checks (in order): car type, hours 0..168, city exists, pickup inside the city boundary, pickup not in the past,
        * and (when saving) the customer has no booking in process. The coupon discount is computed on the server.
        * Saving creates the booking as "Waiting for Driver"; the dispatch job then offers it to drivers.
        */
       public function actionGetEstimate($saveBooking = 'false')
    {
        $data = [];
        $post = Yii::$app->request->post();
        $headers = isset(Yii::$app->request->headers['auth_code'])
            ? Yii::$app->request->headers['auth_code']
            : Yii::$app->request->getQueryParam('auth_code');

        // WHO IS CALLING: the app sends its login code (auth_code). It is turned into a user id here; a blocked,
        // inactive or logged-out account gets no id and is answered "User Not Found".
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        try {
            if (!empty($user_id)) {
                //check for user wallet balance and through a error here 
                // $availableBalance = UserWallet::availableBalance($user_id);
                // if((int)$availableBalance < 0){
                //      $data['status'] = self::API_NOK;
                //      $data['error'] = "You have negative balance in your account. Please add money to wallet to book";
                //      return $this->sendJsonResponse($data);

                // }

                // 1. BUILD THE BOOKING from what the app sent (nothing is saved yet).
                $model = new Booking();
                $model->user_id = $user_id;
                $model->reference_id = rand(11111111, 99999999);
                $model->rider_type = $post['rider_type'];
                $model->trip_type = $post['trip_type'];
                $model->pickup_latitude = $post['pickup_latitude'];
                $model->pickup_longitude = $post['pickup_longitude'];
                $model->pickup_address = $post['pickup_address'];
                $model->payment_method = Booking::payment_method_cash;
                $model->payment_status = Booking::payment_status_pending;

 
                $model->coupon_code = $post['coupon_code'];
                $model->car_type = $post['car_type'];
                $model->car_model = $post['car_model'];

                $model->city_id = $post['city_id'];
                $model->pickup_date = date(
                    'Y-m-d',
                    strtotime($post['pickup_date'])
                );
                $model->pickup_time = $post['pickup_time'];
                $model->usage_hours = !empty($post['usage_hours'])
                    ? $post['usage_hours']
                    : 0;
                if(!empty($post['coupon_discount'])){
                        if($post['coupon_discount'] == 'null'){
                             // 6. COUPON: looked up by code on the server (active, in date, right city, minimum amount, capped by its maximum).
                             $model->coupon_discount = 0;

                        }else{
                         $model->coupon_discount = $post['coupon_discount'];

                        }
                }else{
                    $model->coupon_discount = 0;
                }
    //            $model->coupon_discount = isset($post['coupon_discount']) && $post['coupon_discount'] != "null"
    // ? $post['coupon_discount']
    // : 0;

                $model->is_vehicle_damage_protection = !empty(
                    $post['is_vehicle_damage_protection']
                )
                    ? $post['is_vehicle_damage_protection']
                    : Booking::is_vehicle_damage_protection_no;
                // The 4-digit OTP the customer shows the driver at pickup (see arrived-location).
                $model->otp = rand(1111, 9999);
                // A new booking always starts as "Waiting for Driver".
                $model->status = Booking::STATUS_WAITING_FOR_DRIVER;
                if ($model->rider_type == Booking::RIDER_TYPE_ONEWAY) {
                    $model->drop_latitude = !empty($post['drop_latitude'])?$post['drop_latitude']:'';
                    $model->drop_longitude = !empty($post['drop_longitude'])?$post['drop_longitude']:'';
                    $model->drop_address = !empty($post['drop_address'])?$post['drop_address']:'';
                }
                // var_dump($model->coupon_discount );exit;

                // Input rules the app cannot be trusted with.
                // 2. VALIDATE THE INPUT: the app is never trusted with values that change the price or the service area.
                $pickupAt = strtotime($model->pickup_date . ' ' . $model->pickup_time);
                $inputError = null;
                if (!in_array((int) $model->car_type, [Booking::CAR_TYPE_AUTOMATIC, Booking::CAR_TYPE_MANUAL], true)) {
                    $inputError = 'Please choose a valid car type.';
                } elseif (!is_numeric($model->usage_hours) || (float) $model->usage_hours < 0 || (float) $model->usage_hours > 168) {
                    $inputError = 'Please choose a valid number of hours.';
                } elseif (!($serviceCity = \app\modules\admin\models\City::findOne($model->city_id))) {
                    $inputError = 'Service is not available in this city.';
                } elseif (!$this->pickupInsideCity($serviceCity, $model->pickup_latitude, $model->pickup_longitude)) {
                    $inputError = 'Sorry, we do not serve this pickup location yet.';
                } elseif ($pickupAt === false || $pickupAt < time() - 300) {
                    $inputError = 'The pickup date and time must be in the future.';
                }
                if ($inputError !== null) {
                    $data['status'] = self::API_NOK;
                    $data['error'] = $inputError;
                    return $this->sendJsonResponse($data);
                }

                // One ride at a time: a customer with a booking in process cannot book another.
                // 3. ONE RIDE AT A TIME: when really booking, a customer who already has a ride in process (waiting, accepted, on the
                // way, arrived, started) is refused. Bookings whose pickup time has already passed are cancelled first.
                if ($saveBooking == 'true') {
                    // bookings whose pickup time has passed without a driver are closed first, so they do not block
                    \app\modules\dispatch\services\BookingExpiry::run();
                    $activeId = Booking::find()->select('id')
                        ->where(['user_id' => $user_id])
                        ->andWhere(['status' => [
                            Booking::STATUS_WAITING_FOR_DRIVER,
                            Booking::STATUS_DRIVER_ACCEPTED,
                            Booking::STATUS_GO_FOR_PICKUP,
                            Booking::STATUS_ARRIVED_LOCATION,
                            Booking::STATUS_START_RIDE,
                        ]])
                        ->orderBy(['id' => SORT_DESC])
                        ->scalar();
                    if ($activeId) {
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'You already have a ride in progress. Please complete or cancel it before booking another.';
                        $data['active_booking_id'] = (int) $activeId;
                        return $this->sendJsonResponse($data);
                    }
                }

                // 4. MODEL RULES (required fields etc.).
                if ($model->validate()) {
                    // Unpaid cancellation charges of this customer are shown in the quote; they are collected with this ride.
                    $cancelChargeModel = new CancelRideCharges();
                    $totalCancellationCharge = 0;
                    $userCancellationCharge = $cancelChargeModel->find()->where(['user_id' => $user_id])->one();
            
                    if (!empty($userCancellationCharge)) {
                        // Check for pending cancellation charges for the user
                        $pendingCancellationCharges = $cancelChargeModel->find()
                            ->where(['user_id' => $user_id, 'status' => CancelRideCharges::STATUS_PENDING])
                            ->all();
            
                        if (!empty($pendingCancellationCharges)) {
                            $totalCancellationCharge = 0;
            
                            foreach ($pendingCancellationCharges as $charge) {
                                $totalCancellationCharge += (float)$charge->cancel_charge;
                            }
                        }
                    }
                    try {
                        // 5. THE FARE: base fare + distance + time + night charge + fees + GST, from the city rate card (Booking::calculateEstimate).
                        $estimate = $model->calculateEstimate();
                    } catch (\app\modules\geo\services\DistanceUnavailableException $e) {
                        throw $e;
                    } catch (\Throwable $e) {
                        Yii::error('estimate failed: ' . $e->getMessage(), 'api');
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'Fares are not set up for this city and ride type yet.';
                        return $this->sendJsonResponse($data);
                    }
                    // The discount comes from the coupon on the server, never from the app's number.
                    $model->coupon_discount = 0;
                    $couponCode = trim((string) $model->coupon_code);
                    if ($couponCode !== '' && strtolower($couponCode) !== 'null') {
                        $coupon = Coupon::find()->where(['code' => $couponCode, 'status' => Coupon::STATUS_ACTIVE])->one();
                        $today = date('Y-m-d');
                        $usable = $coupon
                            && (empty($coupon->start_date) || substr($coupon->start_date, 0, 10) <= $today)
                            && (empty($coupon->end_date) || substr($coupon->end_date, 0, 10) >= $today)
                            && (empty($coupon->city_id) || (int) $coupon->city_id === (int) $model->city_id || !empty($coupon->is_global))
                            && (float) $coupon->min_ride_amount <= (float) $estimate['estimatePrice'];
                        if (!$usable) {
                            $data['status'] = self::API_NOK;
                            $data['error'] = 'This coupon is not valid for this ride.';
                            return $this->sendJsonResponse($data);
                        }
                        $off = ((float) $coupon->discount / 100) * (float) $estimate['estimatePrice'];
                        $model->coupon_discount = round(min($off, (float) $coupon->max_discount), 2);
                    }
                    $estimate['estimatePrice'] = max(0, $estimate['estimatePrice'] - $model->coupon_discount);
                 	 $estimate['cancellation_charges'] = $totalCancellationCharge;
                  
                  $userCancellationCharge = WebSetting::find()->where(['setting_key' => 'user_cancellation_charge'])->one();
       $estimate['userCancellationCharge'] = $userCancellationCharge->value; 
 
               	$driverCancellationCharge = WebSetting::find()->where(['setting_key' => 'driver_cancellation_charge'])->one();
       $estimate['driverCancellationCharge'] = $driverCancellationCharge->value; 
 
                   
                    // 7. COPY THE QUOTE onto the booking so later steps can show and audit it.
                    $model->estimate_price = $estimate['estimatePrice'];
                    $model->base_fare = $estimate['baseFare'];
                    $model->secured_fee = $estimate['securedFee'];
                    $model->platform_charges = $estimate['platformCharges'];
                    $model->additional_charges = $estimate['additionalCharges'];
                    $model->gst = $estimate['gst'];
                   
                    $model->price_detail = json_encode($estimate,true);
                    // var_dump($estimate['estimatePrice']);
                    //  var_dump( $model->price_detail );
                    // exit;

                    if ($estimate !== false) {
                        $data['status'] = self::API_OK;
                        // $data['estimate'] = round(
                        //     $estimate['estimatePrice'],
                        //     2
                        // );
                        //  $data['changedEstimate'] =
                        //     round(
                        //     $estimate['estimatePrice'],
                        //     2
                        // );
                        $data['estimate'] =$model->estimate_price ;
                        $data['calculateEstimate'] = $estimate;
                        
                        $data['coupon_discount'] = $model->coupon_discount;
                        $data['message'] = 'Booking estimate';
                        //$data['cancellation_charges'] = $totalCancellationCharge;

                        if ($saveBooking) {
                            // 8. SAVE: only here is the booking written, and the customer told "ride booked".
                            if ($saveBooking == 'true') {
                                $model->save(false);

                            
                                $title = 'New Booking';
                                $body = 'Now ride booked successfully. booking id #'.$model->id;
                               $notification  =   Yii::$app->push->notifyUser($model->id,$model->user_id,$title,$body);
                         

                                $data['booking_id'] = $model->id;
                                $data['message'] = 'Booking saved successfully';
                            }

                            // You can handle any additional logic after saving the booking
                            // For example, sending a confirmation email or triggering other processes.
                        }
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] ='Failed to calculate estimate. Please check the provided details.';
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = $model->getErrors();
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = 'No user found.';
            }
        // Anything unexpected is answered as NOK (the text is cleaned of SQL/stack details by sendJsonResponse).
        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }

        // REPLY: every path above ends here with {status: OK|NOK, details|error}.
        return $this->sendJsonResponse($data);
    }
       // [doc:end] actionGetEstimate

  
    /**
     * True when the point lies inside the city's drawn boundary. A city without a usable boundary does not block bookings.
     */
    private function pickupInsideCity($city, $lat, $lng)
    {
        // The driver service has its own map of every city (service_map, module 1); without one the city's own
        // boundary is used, as before. No usable outline = nobody is refused.
        $polygon = \app\modules\geo\services\ServiceModules::boundary(\app\modules\geo\services\ServiceModules::DRIVER, $city->id);
        if (empty($polygon)) {
            return true;
        }

        return (bool) $this->isPointInsideBoundary($lat, $lng, $polygon);
    }

    // [doc:start] actionCheckBoundary - Check Boundary
    public function actionCheckBoundary()
    {
        $data = [];
        try {
            $post = Yii::$app->request->post();
            $headers = isset(Yii::$app->request->headers['auth_code'])
                ? Yii::$app->request->headers['auth_code']
                : Yii::$app->request->getQueryParam('auth_code');

            $auth = new AuthSettings();
            $user_id = $auth->getAuthSession($headers);

            if (!empty($user_id)) {
                $pickupLat = $post['lat'];
                $pickupLng = $post['lng'];
                // $dropLat = $post['drop_lat'];
                // $dropLng = $post['drop_lng'];

                // Retrieve all city boundaries from the database
                $cities = City::find()->innerJoinWith('cityRateCards')->andWHere(['city.status'=>City::STATUS_ACTIVE])
                ->andWHere(['city_rate_card.status'=>CityRateCard::STATUS_ACTIVE])
                ->all();

                $selectedCity = null;
                $selectedCityId = null;
              
                foreach ($cities as $city) {
                   $data['postData'][] = $city->name; 
                    $boundary = Json::decode($city->boundary);
                    $polygonArray = json_decode($boundary, true);
                    // Check if pickup and drop points are inside the boundary
                    if (
                        $this->isPointInsideBoundary(
                            $pickupLat,
                            $pickupLng,
                            $polygonArray
                        )
                    ) { 
                         $data['postData'] = $city->name;                       
                        $selectedCity = $city->name;
                        $selectedCityId = $city->id;

                        break;
                    }
                }
                if ($selectedCity !== null && $selectedCityId != null) {
                    $data['status'] = self::API_OK;
                    $data['selected_city'] = $selectedCity;
                    $data['selected_city_id'] = $selectedCityId;
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] =
                        'Selected location is not inside any city boundary';
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t('app', 'No User found');
            }
        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionCheckBoundary


    // public function actionCheckBoundary()
    // {
    //     // Retrieve the polygon data from the database
    // $polygonData ="[[37.8020184941278,-122.42132261999473],[37.77244406084341,-122.4415786624752],[37.773800945220934,-122.39934996374473]]";

    // // Convert the polygon data string to an array
    // $polygonArray = json_decode($polygonData, true);

    // // Define the point to check
    // $latitude = 17.437462;
    // $longitude = 78.448288;

    // // Check if the point is inside the polygon
    // $isInside = $this->pointInPolygon($latitude, $longitude, $polygonArray);

    // if ($isInside) {
    //     // Point is inside the polygon
    //     echo "Point is inside the polygon.";
    // } else {
    //     // Point is outside the polygon
    //     echo "Point is outside the polygon.";
    // }
    // }

    // [doc:start] isPointInsideBoundary - Is Point Inside Boundary
    public function isPointInsideBoundary($latitude, $longitude, $polygon)
    {
        $verticesX = [];
        $verticesY = [];
        foreach ($polygon as $point) {
            $verticesX[] = $point[0];
            $verticesY[] = $point[1];
        }

        $count = count($verticesX);
        $i = $j = $c = 0;

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            if (
                $verticesY[$i] > $longitude != $verticesY[$j] > $longitude &&
                $latitude <
                    (($verticesX[$j] - $verticesX[$i]) *
                        ($longitude - $verticesY[$i])) /
                        ($verticesY[$j] - $verticesY[$i]) +
                        $verticesX[$i]
            ) {
                $c = !$c;
            }
        }

        return $c;
    }
    // [doc:end] isPointInsideBoundary


    // [doc:start] actionCouponsList - Coupons List
    public function actionCouponsList()
    {
        $data = [];

        // Fetch the auth code from the request headers or query parameters
        $authCode = Yii::$app->request->headers->get(
            'auth_code',
            Yii::$app->request->getQueryParam('auth_code')
        );

        // Fetch the user ID based on the auth code
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($authCode);

        if (!empty($user_id)) {
            $query = Coupon::find()->where(['!=','status',Coupon::STATUS_DELETE])->orderBy(['created_on' => SORT_DESC]);

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => 10, // Set the number of items per page
                    'page' => Yii::$app->request->get('page', 0), // Get the page number from the request, default to 1 if not provided
                ],
            ]);

            $list = [];
            foreach ($dataProvider->getModels() as $Coupon) {
                $list[] = $Coupon->asJson();
            }

            if (!empty($list)) {
                $data['status'] = self::API_OK;
                $data['details'] = $list;
                $data['pagination'] = [
                    'total_pages' => $dataProvider
                        ->getPagination()
                        ->getPageCount(),
                    'current_page' =>
                        $dataProvider->getPagination()->getPage() + 1,
                    'per_page' => $dataProvider->getPagination()->getPageSize(),
                    'total_count' => $dataProvider->getTotalCount(),
                ];
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t('app', 'No Coupons found');
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = Yii::t('app', 'No User found');
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionCouponsList


    // [doc:start] actionVehicleTypes - Vehicle Types
    public function actionVehicleTypes()
    {
        $data = [];
        $post = \Yii::$app->request->post();
        $type = !empty($post['type']) ? $post['type'] : '';

        $headers = isset(\Yii::$app->request->headers['auth_code'])
            ? \Yii::$app->request->headers['auth_code']
            : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            // the login was verified by getAuthSession (this service or the user app); the customer has no row here
            $user = $user_id;
            if (!empty($user)) {
                if (!empty($type)) {
                    $vehicle_types = VehicleTypes::find()
                        ->where(['type' => $type])
                        ->andWhere(['status' => VehicleTypes::STATUS_ACTIVE])
                        ->all();
                } else {
                    $vehicle_types = VehicleTypes::find()
                        ->where(['status' => VehicleTypes::STATUS_ACTIVE])
                        ->all();
                }
                if (!empty($vehicle_types)) {
                    foreach ($vehicle_types as $vehicle_types_data) {
                        $list[] = $vehicle_types_data->asJson();
                    }
                    if (!empty($list)) {
                        $data['status'] = self::API_OK;
                        $data['details'] = $list;
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'Vehicle type data not found';
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = 'Vehicle type data not found';
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = 'Wrong User';
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = 'User Not Found';
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionVehicleTypes


    //Apply Coupons
    // [doc:start] actionApplyCoupon - Apply Coupon
    public function actionApplyCoupon()
    {
        $data = [];
        $post = \Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code'])
            ? \Yii::$app->request->headers['auth_code']
            : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            try {
                $Coupon_id = !empty($post['coupon_id'])
                    ? $post['coupon_id']
                    : '';
                $estimate_price = !empty($post['estimate_price'])
                    ? $post['estimate_price']
                    : 0;

                if (!empty($Coupon_id)) {
                    $coupon = Coupon::find()
                        ->where(['id' => $Coupon_id])
                        ->one();
                    if (!empty($coupon)) {
                        if ($coupon['min_ride_amount'] <= $estimate_price) {
                            $amt =
                                ($coupon['discount'] / 100) * $estimate_price;
                            if ($amt >= $coupon['max_discount']) {
                                $voucher_amount = $coupon['max_discount'];
                            } else {
                                $voucher_amount = $amt;
                            }
                            $coupon_data['coupon_id'] = $coupon->id;
                            $coupon_data['coupon_code'] = $coupon->code;
                            $coupon_data['coupon_discount'] = $voucher_amount;

                            $data['status'] = self::API_OK;
                            $data['details'] = $coupon_data;
                        } else {
                            $data['status'] = self::API_NOK;
                            $data['error'] =
                                'For applying coupon your cart value should be ₹' .
                                $coupon['min_ride_amount'];
                        }
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'coupon not found';
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = 'Coupon_id required';
                }
            } catch (Exception $e) {
                $data['status'] = self::API_NOK;
                $data['error'] = $e->getMessage();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = 'NO session found';
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionApplyCoupon


    // public function actionAssignBookingToDriver()
    // {
    //     //get driver
    //     $driver = User::find()
    //         ->where(['status' => User::STATUS_ACTIVE])
    //         ->andWhere(['user_role' => User::ROLE_DRIVER])
    //         ->andWHere(['online_status' => User::online_status_yes])
    //         ->all();
    //     $booking = Booking::find()
    //         ->where(['status' => Booking::STATUS_WAITING_FOR_DRIVER])
    //         ->all();
    //     if (!empty($booking)) {
    //         foreach ($booking as $bookingData) {
    //             $booking_id = $bookingData->id;
    //             if (!empty($driver)) {
    //                 foreach ($driver as $driverData) {

               
    //                     $driver_requests_check = DriverRequests::find()
    //                         ->where(['driver_id' => $driverData->id])
    //                         ->andWhere(['booking_id' => $booking_id])

    //                         ->one();
    //                     if (!empty($driver_requests_check)) {
    //                         $driver_requests = DriverRequests::find()
    //                             ->where(['id' => $driver_requests_check->id])
    //                             ->andWhere([
    //                                 'status' =>
    //                                     DriverRequests::STATUS_WAITING_FOR_DRIVER,
    //                             ])
    //                             ->one();

    //                         if ($driver_requests) {
    //                             $driver_requests->driver_id = $driverData->id;
    //                             $driver_requests->booking_id = $booking_id;
    //                             $driver_requests->status = $bookingData->status;
    //                             $driver_requests->save(false);
    //                         }
    //                     } else {
    //                         //// Checking if booking is incomplete
    //                         $driver_check_booking = Booking::find()
    //                         ->where(['driver_id' => $driverData->id])
    //                         ->andWhere([
    //                             'IN',
    //                             'booking.status',
    //                             [
    //                                 Booking::STATUS_DRIVER_ACCEPTED,
    //                                 Booking::STATUS_GO_FOR_PICKUP,
    //                                 Booking::STATUS_START_RIDE,
    //                                 Booking::STATUS_ARRIVED_LOCATION,

    //                             ],
    //                         ])->one();
                             
                            
    //                         if(empty($driver_check_booking))
    //                         {
    //                             $driver_requests = new DriverRequests();
    //                             $driver_requests->driver_id = $driverData->id;
    //                             $driver_requests->booking_id = $booking_id;
    //                             $driver_requests->status = $bookingData->status;
    //                             $driver_requests->save(false);

    //                             $title = 'New Booking';
    //                             $body = 'You have received new ride. booking id #'.$booking_id;
    //                             Yii::$app->push->notifyUser($booking_id,$driver_requests->driver_id,$title,$body);
    //                         }
                            
    //                     }
    //                 }
    //             }
    //         }
    //     }
    // }
    // [doc:start] actionAssignBookingToDriver - Cron entry point: offer every waiting booking to nearby online drivers of the booking's city.
    /**
     * Cron entry point: offer every waiting booking to nearby online drivers of the booking's city.
     *
     * Bounded on purpose (the old version looped every waiting booking x every online driver and pushed for each
     * pair, which hit the 60 s PHP limit before reaching most drivers):
     *  - only bookings created in the last 24 hours that are still waiting for a driver,
     *  - only online, active drivers of the booking's city, nearest to the pickup point first,
     *  - drivers on a ride or already holding a request for the booking are skipped,
     *  - at most MAX_NEW_OFFERS_PER_BOOKING new offers per booking per run (the next run reaches the next nearest),
     *  - stops after 40 seconds and reports what it did.
     * If a cron token is configured (CRON_TOKEN or secrets 'cronToken') the call must carry it as ?token=...
     */
    public function actionAssignBookingToDriver()
    {
        $limit = 25; // MAX_NEW_OFFERS_PER_BOOKING
        $expected = getenv('CRON_TOKEN') ?: ((require \Yii::getAlias('@app/config/secrets-loader.php'))['cronToken'] ?? '');
        if ($expected !== '' && !hash_equals((string)$expected, (string)\Yii::$app->request->get('token', ''))) {
            \Yii::$app->response->statusCode = 403;
            return $this->sendJsonResponse(['status' => self::API_NOK, 'error' => 'Forbidden']);
        }

        // bookings whose pickup time has passed without a driver are cancelled first (the customer is told)
        \app\modules\dispatch\services\BookingExpiry::run();

        // TIME BUDGET: stop after 40 seconds so the cron call never hits PHP's time limit.
        $deadline = microtime(true) + 40;
        $summary = ['bookings' => 0, 'offers' => 0, 'stopped_early' => false];

        // WHICH BOOKINGS: still waiting for a driver and created in the last 24 hours.
        $bookings = Booking::find()
            ->where(['status' => Booking::STATUS_WAITING_FOR_DRIVER])
            ->andWhere(['>=', 'created_on', date('Y-m-d H:i:s', time() - 86400)])
            ->all();
        if (empty($bookings)) {
            return $this->sendJsonResponse(['status' => self::API_OK, 'details' => $summary]);
        }

        // WHICH DRIVERS: active, online drivers.
        $driverQuery = User::find()
            ->where(['status' => User::STATUS_ACTIVE, 'user_role' => User::ROLE_DRIVER, 'online_status' => User::online_status_yes]);
        // With "Enforce driver onboarding" on, only approved drivers (and, when there is a joining fee, those who paid it) get offers.
        $onboarding = \app\modules\payments\services\DriverOnboarding::dispatchFilter();
        if ($onboarding !== null) {
            $driverQuery->andWhere($onboarding);
        }
        $drivers = $driverQuery->all();
        // Drivers who are on a ride (accepted / on the way / arrived / started) get no offers.
        $busy = Booking::find()->select('driver_id')
            ->where(['IN', 'status', [
                Booking::STATUS_DRIVER_ACCEPTED, Booking::STATUS_GO_FOR_PICKUP,
                Booking::STATUS_ARRIVED_LOCATION, Booking::STATUS_START_RIDE,
            ]])
            ->andWhere(['IS NOT', 'driver_id', null])
            ->column();
        $busy = array_flip($busy);

        // FOR EACH BOOKING: same city, nearest first, skip busy drivers and those already offered.
        foreach ($bookings as $booking) {
            if (microtime(true) > $deadline) {
                $summary['stopped_early'] = true;
                break;
            }
            $summary['bookings']++;
            $already = array_flip(DriverRequests::find()->select('driver_id')
                ->where(['booking_id' => $booking->id])->column());

            $candidates = [];
            foreach ($drivers as $driver) {
                if (isset($busy[$driver->id]) || isset($already[$driver->id])) {
                    continue;
                }
                if (!empty($booking->city_id) && !empty($driver->city_id) && (int)$driver->city_id !== (int)$booking->city_id) {
                    continue;
                }
                $km = 1e9;
                if (is_numeric($driver->lat) && is_numeric($driver->lng) && (float)$driver->lat != 0.0
                    && is_numeric($booking->pickup_latitude) && is_numeric($booking->pickup_longitude)) {
                    $dLat = deg2rad((float)$driver->lat - (float)$booking->pickup_latitude);
                    $dLng = deg2rad((float)$driver->lng - (float)$booking->pickup_longitude);
                    $h = sin($dLat / 2) ** 2 + cos(deg2rad((float)$booking->pickup_latitude))
                        * cos(deg2rad((float)$driver->lat)) * sin($dLng / 2) ** 2;
                    $km = 12742 * asin(min(1, sqrt($h)));
                }
                $candidates[] = [$km, $driver];
            }
            // Nearest driver first (straight-line distance from the pickup).
            usort($candidates, function ($a, $b) { return $a[0] <=> $b[0]; });

            // OFFER: at most $limit new offers per booking per run; each one is a driver_requests row plus a push to the driver.
            foreach (array_slice($candidates, 0, $limit) as $row) {
                $request = new DriverRequests();
                $request->driver_id = $row[1]->id;
                $request->booking_id = $booking->id;
                $request->status = $booking->status;
                $request->save(false);
                Yii::$app->push->notifyUser($booking->id, $request->driver_id, 'New Booking',
                    'You have received a new ride. Booking ID #' . $booking->id);
                $summary['offers']++;
            }
        }
        return $this->sendJsonResponse(['status' => self::API_OK, 'details' => $summary]);
    }
    // [doc:end] actionAssignBookingToDriver



    // public function actionCancelRide()
    // {
    //     $data = [];
    //     $post = \Yii::$app->request->post();
    //     $headers = isset(\Yii::$app->request->headers['auth_code'])
    //         ? \Yii::$app->request->headers['auth_code']
    //         : Yii::$app->request->getQueryParam('auth_code');
    //     $auth = new AuthSettings();
    //     $user_id = $auth->getAuthSession($headers);
    //     if (!empty($user_id)) {
    //         try {
    //             $booking_id = !empty($post['booking_id'])? $post['booking_id']: '';
    //             $cancel_reason = !empty($post['cancel_reason'])? $post['cancel_reason']: '';

    //             if (!empty($booking_id) && !empty($cancel_reason)) {
    //                 $booking = Booking::find()
    //                     ->where(['id' => $booking_id])
    //                     ->andWhere(['!=','status' , Booking::STATUS_USER_CANCELLED])
    //                     ->one();
    //                 if (!empty($booking)) {
    //                     if($booking->status == Booking::STATUS_USER_CANCELLED)
    //                     {
    //                         $data['status'] = self::API_NOK;
    //                         $data['error'] = 'Booking already cancelled';
    //                         return $this->sendJsonResponse($data);
    //                     }
    //                     $booking->status = Booking::STATUS_USER_CANCELLED;
    //                     $booking->cancel_reason = $cancel_reason;

    //                     if ($booking->save(false)) {
    //                         $driver_requests = DriverRequests::find()
    //                             ->where(['booking_id' => $booking_id])
    //                             ->all();
    //                         if (!empty($driver_requests)) {
    //                             foreach (
    //                                 $driver_requests
    //                                 as $driver_requests_data
    //                             ) {
    //                                 $driver_requests_data->status =
    //                                     DriverRequests::STATUS_USER_CANCELLED;
    //                                 $booking->cancel_reason = 'user cancelled';
    //                                 $driver_requests_data->save(false);
    //                             }
    //                         }

    //                         $userCancellationCharge = WebSetting::find()->where(['setting_key' => 'user_cancellation_charge'])->one();
    //                         if(!empty($userCancellationCharge) && $userCancellationCharge->value != '0')
    //                         {
    //                             $userWallet = new UserWallet();
    //                             $userWallet->user_id = $user_id;
    //                             $userWallet->amount = (float)$userCancellationCharge->value;
    //                             $userWallet->payment_type = UserWallet::payment_type_debit;
    //                             $userWallet->type = UserWallet::type_booking_cancellation;
    //                             $userWallet->method_reason = 'Rs '.$userCancellationCharge->value." For Booking cancelling the ride id #".$booking_id;
    //                             $userWallet->status = UserWallet::STATUS_PENDING;
    //                             $userWallet->created_on = Date('Y-m-d H:i:s');
    //                             $userWallet->create_user_id  = $user_id;
    //                             $userWallet->save(false);
    //                         }
    //                         $data['status'] = self::API_OK;
    //                         $data['details'] = 'booking cancelled';
    //                     } else {
    //                         $data['status'] = self::API_NOK;
    //                         $data['error'] = 'booking called failed';
    //                     }
    //                 } else {
    //                     $data['status'] = self::API_NOK;
    //                         $data['error'] = 'Booking already cancelled';
    //                 }
    //             } else {
    //                 $data['status'] = self::API_NOK;
    //                 $data['error'] = 'booking_id and cancel_reason required';
    //             }
    //         } catch (Exception $e) {
    //             $data['status'] = self::API_NOK;
    //             $data['error'] = $e->getMessage();
    //         }
    //     } else {
    //         $data['status'] = self::API_NOK;
    //         $data['error'] = 'NO session found';
    //     }
    //     return $this->sendJsonResponse($data);
    // }

     // [doc:start] actionCancelRide - The customer cancels their own booking.
     /**
      * The customer cancels their own booking.
      *
      * POST /api/user/cancel-ride   booking_id, cancel_reason
      *   - after a driver accepted (or is on the way): booking -> User Cancelled, the offers are closed, and the
      *     User Cancellation Charge is recorded (part to the driver, part to the company) and collected with the next ride;
      *   - while still waiting for a driver: booking -> User Cancelled, no charge.
      * Only the owner can cancel, and only before the ride starts.
      */
     public function actionCancelRide()
{
    $data = [];
    $post = \Yii::$app->request->post();
    $headers = isset(\Yii::$app->request->headers['auth_code'])
        ? \Yii::$app->request->headers['auth_code']
        : Yii::$app->request->getQueryParam('auth_code');
    // WHO IS CALLING: the app sends its login code (auth_code). It is turned into a user id here; a blocked,
    // inactive or logged-out account gets no id and is answered "User Not Found".
    $auth = new AuthSettings();
    $user_id = $auth->getAuthSession($headers);

    if (!empty($user_id)) {
        try {
            $booking_id = !empty($post['booking_id']) ? $post['booking_id'] : '';
            $cancel_reason = !empty($post['cancel_reason']) ? $post['cancel_reason'] : '';

            if (!empty($booking_id) && !empty($cancel_reason)) {
                $booking = Booking::find()
                    ->where(['id' => $booking_id])
                    ->andWhere(['!=', 'status', Booking::STATUS_USER_CANCELLED])
                    // OWNERSHIP: someone else's booking is never found.
                    ->andWhere(['user_id' => $user_id])
                    ->andWhere(['status' => [Booking::STATUS_DRIVER_ACCEPTED, Booking::STATUS_GO_FOR_PICKUP]])
                    ->one();


                if (!empty($booking)) {
                    $booking->status = Booking::STATUS_USER_CANCELLED;
                    $booking->cancel_reason = $cancel_reason;
                    

                    $user_title = 'Booking Cancelled';
                        $user_body = 'Booking ID #' . $booking->id .' is successfully cancelled.';
                        Yii::$app->push->notifyUser($booking->id, $booking->user_id, $user_title, $user_body);

                    if ($booking->save(false)) {
                        $driver_requests = DriverRequests::find()
                            ->where(['booking_id' => $booking_id])
                            ->all();
                        

                        if (!empty($driver_requests)) {
                            foreach ($driver_requests as $driver_requests_data) {
                                $driver_requests_data->status = DriverRequests::STATUS_USER_CANCELLED;
                                $driver_requests_data->save(false);
                            }
                        }


                        // CHARGE: the amount comes from Web Settings (user_cancellation_charge); the company keeps admin_cancellation_earning of it.
                        $userCancellationCharge = WebSetting::find()->where(['setting_key' => 'user_cancellation_charge'])->one();
                       $adminCancellationEarnings = WebSetting::find()->where(['setting_key' => 'admin_cancellation_earning'])->one();
                    
                    
                      //$data['charge']=$userCancellationCharge;
    
                        // No charge configured = no charge row (and nothing to pay the driver).
                        $cancelChargeModel = null;
                        if (!empty($userCancellationCharge) && $userCancellationCharge->value != '0') {
                            // Insert into cancel_ride_charges table
                            $cancelChargeModel = new CancelRideCharges();
                            $cancelChargeModel->user_id = $user_id;
                            $cancelChargeModel->driver_id = $booking->driver_id;
                            $cancelChargeModel->booking_id = $booking_id;
                            $cancelChargeModel->cancel_role = 'user';
                            $cancelChargeModel->cancel_reason = $cancel_reason;
                            $cancelChargeModel->cancel_charge = (float)$userCancellationCharge->value;
                            $cancelChargeModel->status = 'pending';
                            $cancelChargeModel->created_date = date('Y-m-d H:i:s');
                            $cancelChargeModel->save(false);
                        }
                        if (!empty($cancelChargeModel) && !empty($booking->driver_id)) {
                            $adminCut = !empty($adminCancellationEarnings) ? (float) $adminCancellationEarnings->value : 0.0;
                            $cancel_charge = (float) $cancelChargeModel->cancel_charge - $adminCut;
                            $cancelChargeModel->admin_cancellation_earnings = $adminCut;
                            $cancelChargeModel->save(false);

                            $driverWallet = new DriverWallet();
                            $driverWallet->driver_id = $booking->driver_id;
                            $driverWallet->amount = $cancel_charge;
                            $driverWallet->payment_type = DriverWallet::payment_type_credit;
                            $driverWallet->method_reason = $cancel_charge . " credited for user ride cancellation";
                            $driverWallet->status = DriverWallet::STATUS_SUCCESS;
                            $driverWallet->created_on = date('Y-m-d H:i:s');
                            $driverWallet->create_user_id = $booking->driver_id;
                            $driverWallet->save(false);
                        }

                        $data['status'] = self::API_OK;
                        $data['details'] = 'booking cancelled';
                    
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'booking called failed';
                    }
                } else {
                    $booking = Booking::find()
                    ->where(['id' => $booking_id, 'user_id' => $user_id])
                    // a ride that is waiting for a driver; started or finished rides cannot be cancelled here
                    ->andWhere(['status' => Booking::STATUS_WAITING_FOR_DRIVER])
                    ->one();

                    if (!empty($booking)) {
                        $booking->status = Booking::STATUS_USER_CANCELLED;
                        $booking->cancel_reason = $cancel_reason;

                        if ($booking->save(false)) {
                            $driver_requests = DriverRequests::find()
                                ->where(['booking_id' => $booking_id])
                                ->all();

                            if (!empty($driver_requests)) {
                                foreach ($driver_requests as $driver_requests_data) {
                                    $driver_requests_data->status = DriverRequests::STATUS_USER_CANCELLED;
                                    $driver_requests_data->save(false);
                                }
                            }
                              $data['status'] = self::API_OK;
                            $data['error'] = 'Booking Cancelled';
                        }else{
                             $data['status'] = self::API_NOK;
                            $data['error'] = 'Failed to cancel';
                        }
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'This booking can no longer be cancelled';
                    }
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = 'booking_id and cancel_reason required';
            }
        // Anything unexpected is answered as NOK (the text is cleaned of SQL/stack details by sendJsonResponse).
        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }
    } else {
        $data['status'] = self::API_NOK;
        $data['error'] = 'No session found';
    }

    // REPLY: every path above ends here with {status: OK|NOK, details|error}.
    return $this->sendJsonResponse($data);
}
     // [doc:end] actionCancelRide

  
  // [doc:start] actionCancellationCharges - Cancellation Charges
  public function actionCancellationCharges()
    {
        $data = [];
    
        $headers = Yii::$app->request->headers->get(
            'auth_code',
            Yii::$app->request->getQueryParam('auth_code')
        );
        
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
    
        if (!empty($user_id)) {
            $cancelChargeModel = new CancelRideCharges();
            $userCancellationCharge = $cancelChargeModel->find()->where(['user_id' => $user_id])->one();
    
            if (!empty($userCancellationCharge)) {
                // Check for pending cancellation charges for the user
                $pendingCancellationCharges = $cancelChargeModel->find()
                    ->where(['user_id' => $user_id, 'status' => CancelRideCharges::STATUS_PENDING])
                    ->all();
    
                if (!empty($pendingCancellationCharges)) {
                    $totalCancellationCharge = 0;
    
                    foreach ($pendingCancellationCharges as $charge) {
                        $totalCancellationCharge += (float)$charge->cancel_charge;
                    }
    
                    $data['status'] = self::API_OK;
                    $data['details'] = [
                        'cancellation' => true,
                        'cancel_charges' => $totalCancellationCharge,
                    ];
                } else {
                    $data['status'] = self::API_OK;
                    $data['details'] = ['cancellation' => false];
                }
            } else {
                $data['status'] = self::API_OK;
                $data['details'] = ['cancellation' => false];
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = 'User Not Found';
        }
    
        return $this->sendJsonResponse($data);
    }
  // [doc:end] actionCancellationCharges

     
    // [doc:start] actionRideInfo - Ride Info
    public function actionRideInfo()
    {
        $data = [];
        $post = \Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code'])
            ? \Yii::$app->request->headers['auth_code']
            : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            try {
                $booking_id = !empty($post['booking_id'])? $post['booking_id']: '';
                if (!empty($booking_id)) {
                    $booking = Booking::find()
                        ->where(['id' => $booking_id])
                        ->one();
                    if (!empty($booking)) {
                        $data['status'] = self::API_OK;
                        $data['details'] = $booking->asJson();
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'booking data not found';
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = 'booking_id required';
                }
            } catch (Exception $e) {
                $data['status'] = self::API_NOK;
                $data['error'] = $e->getMessage();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = 'NO session found';
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionRideInfo


    // [doc:start] actionAddReview - Add Review
    public function actionAddReview()
    {
        $data = [];
        $post = \Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code'])
            ? \Yii::$app->request->headers['auth_code']
            : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            try {
                $booking_id = !empty($post['booking_id'])
                    ? $post['booking_id']
                    : '';
                $rating = !empty($post['rating']) ? $post['rating'] : '';
                $review = !empty($post['review']) ? $post['review'] : '';

                if (!empty($booking_id)) {
                    $booking = Booking::find()
                        ->where(['id' => $booking_id])
                        ->one();
                    if (!empty($booking)) {
                        $user_booking_ratings_check = UserBookingRatings::find()
                            ->where(['booking_id' => $booking_id])
                            ->one();
                        if (!empty($user_booking_ratings_check)) {
                            $user_booking_ratings = UserBookingRatings::find()
                                ->where([
                                    'id' => $user_booking_ratings_check->id,
                                ])
                                ->one();
                        } else {
                            $user_booking_ratings = new UserBookingRatings();
                        }
                        $user_booking_ratings->user_id = $user_id;
                        $user_booking_ratings->driver_id = $booking->driver_id;
                        $user_booking_ratings->booking_id = $booking_id;
                        $user_booking_ratings->rating = $rating;
                        $user_booking_ratings->review = $review;
                        $user_booking_ratings->status =
                            UserBookingRatings::STATUS_ACTIVE;
                        if ($user_booking_ratings->save(false)) {
                            //update average rating
                            $driver_average = UserBookingRatings::find()
                                ->where(['driver_id' => $booking->driver_id])
                                ->average('rating');
                            //get driver details
                            $driver = User::find()
                                ->where(['id' => $booking->driver_id])
                                ->one();
                            $driver->average_rating = $driver_average;

                            $data['status'] = self::API_OK;
                            $data['details'] = $user_booking_ratings->asJson();
                        } else {
                            $data['status'] = self::API_NOK;
                            $data['error'] = 'Rating added failed';
                        }
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'booking data not found';
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = 'booking_id required';
                }
            } catch (Exception $e) {
                $data['status'] = self::API_NOK;
                $data['error'] = $e->getMessage();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = 'NO session found';
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionAddReview


    // [doc:start] actionRemoveCoupon - Remove Coupon
    public function actionRemoveCoupon()
    {
        $data = [];
        $post = \Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code'])
            ? \Yii::$app->request->headers['auth_code']
            : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            try {
                $Coupon_id = !empty($post['coupon_id'])? $post['coupon_id']: '';

                if (!empty($Coupon_id)) {
                    $coupon = Coupon::find()
                        ->where(['id' => $Coupon_id])
                        ->one();
                    if (!empty($coupon)) {
                        $coupon_data['coupon_code'] = '';
                        $coupon_data['coupon_discount'] = 0;
                        $data['status'] = self::API_OK;
                        $data['details'] = $coupon_data;
                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'coupon not found';
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = 'Coupon_id required';
                }
            } catch (Exception $e) {
                $data['status'] = self::API_NOK;
                $data['error'] = $e->getMessage();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = 'NO session found';
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionRemoveCoupon


    // [doc:start] actionRatingAndReviews - Rating And Reviews
    public function actionRatingAndReviews()
    {
        $data = [];
        try {
            // Fetch the auth code from the request headers or query parameters
            $authCode = Yii::$app->request->headers->get(
                'auth_code',
                Yii::$app->request->getQueryParam('auth_code')
            );

            // Fetch the user ID based on the auth code
            $auth = new AuthSettings();
            $user_id = $auth->getAuthSession($authCode);

            if (!empty($user_id)) {
                $rating_one = DriverBookingRatings::find()
                    ->innerJoinWith('booking')
                    ->where(['booking.user_id' => $user_id])
                    ->andWhere(['rating' => 1])
                    ->count();
                $rating_two = DriverBookingRatings::find()
                    ->innerJoinWith('booking')
                    ->where(['booking.user_id' => $user_id])
                    ->andWhere(['rating' => 2])
                    ->count();
                $rating_three = DriverBookingRatings::find()
                    ->innerJoinWith('booking')
                    ->where(['booking.user_id' => $user_id])
                    ->andWhere(['rating' => 3])
                    ->count();
                $rating_four = DriverBookingRatings::find()
                    ->innerJoinWith('booking')
                    ->where(['booking.user_id' => $user_id])
                    ->andWhere(['rating' => 4])
                    ->count();
                $rating_five = DriverBookingRatings::find()
                    ->innerJoinWith('booking')
                    ->where(['booking.user_id' => $user_id])
                    ->andWhere(['rating' => 5])
                    ->count();
                $total_rating = DriverBookingRatings::find() 
                    ->innerJoinWith('booking')
                    ->where(['booking.user_id' => $user_id])
                    ->count();

                $query = DriverBookingRatings::find()
                    ->innerJoinWith('booking')
                    ->where(['booking.user_id' => $user_id])
                    ->orderBy([
                        'driver_booking_ratings.created_on' => SORT_DESC,
                    ]);

                $dataProvider = new ActiveDataProvider([
                    'query' => $query,
                    'pagination' => [
                        'pageSize' => 10, // Set the number of items per page
                        'page' => Yii::$app->request->get('page', 0), // Get the page number from the request, default to 1 if not provided
                    ],
                ]);

                $list = [];
                foreach ($dataProvider->getModels() as $ratings) {
                    $list[] = $ratings->asJson();
                }

                if (!empty($list)) {
                    $data['status'] = self::API_OK;
                    $data['details'] = $list;
                    $data['rating_one'] = $rating_one;
                    $data['rating_two'] = $rating_two;
                    $data['rating_three'] = $rating_three;
                    $data['rating_four'] = $rating_four;
                    $data['rating_five'] = $rating_five;
                    $data['total_rating'] = $total_rating;

                    $data['pagination'] = [
                        'total_pages' => $dataProvider
                            ->getPagination()
                            ->getPageCount(),
                        'current_page' =>
                            $dataProvider->getPagination()->getPage() + 1,
                        'per_page' => $dataProvider
                            ->getPagination()
                            ->getPageSize(),
                        'total_count' => $dataProvider->getTotalCount(),
                    ];
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = Yii::t(
                        'app',
                        'No ratings found for the specified user'
                    );
                }
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t('app', 'No User found');
            }
        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }

        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionRatingAndReviews


    // [doc:start] actionViewBookingDetails - View Booking Details
    public function actionViewBookingDetails()
    {
        $data = [];
        $post = \Yii::$app->request->post();
        $headers = isset(\Yii::$app->request->headers['auth_code'])
            ? \Yii::$app->request->headers['auth_code']
            : Yii::$app->request->getQueryParam('auth_code');
        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        if (!empty($user_id)) {
            try {
                $booking_id = !empty($post['booking_id'])
                    ? $post['booking_id']
                    : '';

                if (!empty($booking_id)) {
                    $booking = Booking::find()
                        ->where(['id' => $booking_id])
                        ->one();
                        $auto_cancel_time = WebSetting::find()->where(['setting_key' => 'user_waiting_time'])->one();
                        $notification_interval = WebSetting::find()->where(['setting_key' => 'notification_period'])->one();
                    if (!empty($booking)) {
                        $data['status'] = self::API_OK;
                        $data['details'] = $booking->asJson();
                        $data['notification_interval'] = $notification_interval->value;
                        $data['auto_cancel_time'] = $auto_cancel_time->value;
                                     

                    } else {
                        $data['status'] = self::API_NOK;
                        $data['error'] = 'booking data not found';
                    }
                } else {
                    $data['status'] = self::API_NOK;
                    $data['error'] = 'booking_id required';
                }
            } catch (Exception $e) {
                $data['status'] = self::API_NOK;
                $data['error'] = $e->getMessage();
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = 'NO session found';
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionViewBookingDetails



// [doc:start] actionMyReferralEarings - My Referral Earings
public function actionMyReferralEarings(){

    $data = [];
    $post = \Yii::$app->request->post();
    $headers = isset(\Yii::$app->request->headers['auth_code'])? \Yii::$app->request->headers['auth_code']: Yii::$app->request->getQueryParam('auth_code');
    $auth = new AuthSettings();
    $user_id = $auth->getAuthSession($headers);
    if (!empty($user_id)) {
        try {

            $user_referral_earnings = UserReferralEarnings::find()->where(['user_id'=>$user_id])->sum('earnings');
            $user_earnings['earnings'] = !empty($user_referral_earnings)?$user_referral_earnings:0;
            $data['status'] = self::API_OK;
            $data['details'] = $user_earnings;
           
        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }
    } else {
        $data['status'] = self::API_NOK;
        $data['error'] = 'NO session found';
    }
    return $this->sendJsonResponse($data);


}
// [doc:end] actionMyReferralEarings


// [doc:start] actionPayNow - Pay Now
public function actionPayNow(){

    $data = [];
    $post = \Yii::$app->request->post();
    $headers = isset(\Yii::$app->request->headers['auth_code'])? \Yii::$app->request->headers['auth_code']: Yii::$app->request->getQueryParam('auth_code');
    $auth = new AuthSettings();
    $user_id = $auth->getAuthSession($headers);
    if (!empty($user_id)) {
        try {
            $payment_method  = !empty($post['payment_method'])?$post['payment_method']:'';
            $booking_id  = !empty($post['booking_id'])?$post['booking_id']:'';
            $booking= Booking::find()->where(['id'=>$booking_id])->one();
            if(!empty($booking)){

                $booking->payment_method = $payment_method;
                $booking->payment_status =Booking::payment_status_success; 
                $booking->save(false);

                $data['status'] = self::API_OK;
                $data['details'] = 'booking data  found';


            }else{
                $data['status'] = self::API_NOK;
                $data['error'] = 'booking data  found';
            }
     
        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }
    } else {
        $data['status'] = self::API_NOK;
        $data['error'] = 'NO session found';
    }
    return $this->sendJsonResponse($data);



}
// [doc:end] actionPayNow


// [doc:start] actionRideRequestActive - Ride Request Active
public function actionRideRequestActive(){

    $data = [];
    $post = \Yii::$app->request->post();
    $headers = isset(\Yii::$app->request->headers['auth_code'])? \Yii::$app->request->headers['auth_code']: Yii::$app->request->getQueryParam('auth_code');
    $auth = new AuthSettings();
    $user_id = $auth->getAuthSession($headers);
    if (!empty($user_id)) {
        try {
            if (!class_exists(RideRequest::class)) {
                throw new Exception('No history found'); // taxi rides are served by the taxi services' rider APIs
            }
            $vehicle_type  = !empty($post['vehicle_type'])?$post['vehicle_type']:'';
            $rideRequest= RideRequest::find()->where(['vehical_type'=>$vehicle_type])->andWhere(['or',
            ['status' => RideRequest::STATUS_NEW_REQUEST],
            ['status' => RideRequest::STATUS_ACCEPTED_BY_DRIVER],
            ['status' => RideRequest::STATUS_RIDE_BOOKED],
            ['status' => RideRequest::STATUS_ON_THE_WAY],
            ['status' => RideRequest::STATUS_ARRIVED],
            ['status' => RideRequest::STATUS_START_RIDE],
        ])->all();
            if (!empty($rideRequest)) {
                $data['status'] = self::API_OK;
                $data['details'] = $rideRequest;
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t(
                    'app',
                    'No history found'
                );
            }
     
        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }
    } else {
        $data['status'] = self::API_NOK;
        $data['error'] = 'NO session found';
    }
    return $this->sendJsonResponse($data);



}
// [doc:end] actionRideRequestActive


// [doc:start] actionRideRequestCompleted - Ride Request Completed
public function actionRideRequestCompleted(){

    $data = [];
    $post = \Yii::$app->request->post();
    $headers = isset(\Yii::$app->request->headers['auth_code'])? \Yii::$app->request->headers['auth_code']: Yii::$app->request->getQueryParam('auth_code');
    $auth = new AuthSettings();
    $user_id = $auth->getAuthSession($headers);
    if (!empty($user_id)) {
        try {
            if (!class_exists(RideRequest::class)) {
                throw new Exception('No history found'); // taxi rides are served by the taxi services' rider APIs
            }
            $vehicle_type  = !empty($post['vehicle_type'])?$post['vehicle_type']:'';
            $rideRequest= RideRequest::find()->andWhere(['vehical_type'=>$vehicle_type])->andWhere(['or',
            ['status' => RideRequest::STATUS_RIDE_COMPLETED_PAID],
            ['status' => RideRequest::STATUS_RIDE_COMPLETED],
        ])->all();
            if (!empty($rideRequest)) {
                $data['status'] = self::API_OK;
                $data['details'] = $rideRequest;
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t(
                    'app',
                    'No history found'
                );
            }
     
        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }
    } else {
        $data['status'] = self::API_NOK;
        $data['error'] = 'NO session found';
    }
    return $this->sendJsonResponse($data);



}
// [doc:end] actionRideRequestCompleted

// [doc:start] actionRideRequestCancelled - Ride Request Cancelled
public function actionRideRequestCancelled(){

    $data = [];
    $post = \Yii::$app->request->post();
    $headers = isset(\Yii::$app->request->headers['auth_code'])? \Yii::$app->request->headers['auth_code']: Yii::$app->request->getQueryParam('auth_code');
    $auth = new AuthSettings();
    $user_id = $auth->getAuthSession($headers);
    if (!empty($user_id)) {
        try {
            if (!class_exists(RideRequest::class)) {
                throw new Exception('No history found'); // taxi rides are served by the taxi services' rider APIs
            }
            $vehicle_type  = !empty($post['vehicle_type'])?$post['vehicle_type']:'';
            $rideRequest= RideRequest::find()->andWhere(['vehical_type'=>$vehicle_type])->andWhere(['or',
            ['status' => RideRequest::STATUS_CANCEL_BY_USER],
            ['status' => RideRequest::STATUS_CANCEL_BY_DRIVER],
        ])->all();
            if (!empty($rideRequest)) {
                $data['status'] = self::API_OK;
                $data['details'] = $rideRequest;
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] = Yii::t(
                    'app',
                    'No history found'
                );
            }
     
        } catch (Exception $e) {
            $data['status'] = self::API_NOK;
            $data['error'] = $e->getMessage();
        }
    } else {
        $data['status'] = self::API_NOK;
        $data['error'] = 'NO session found';
    }
    return $this->sendJsonResponse($data);



}
// [doc:end] actionRideRequestCancelled

  
  
  
// [doc:start] actionCurrentCity - Current City
public function actionCurrentCity()
{
    $data = [];
    try {
        $post = Yii::$app->request->post();
        $headers = isset(Yii::$app->request->headers['auth_code'])
            ? Yii::$app->request->headers['auth_code']
            : Yii::$app->request->getQueryParam('auth_code');

        $auth = new AuthSettings();
        $user_id = $auth->getAuthSession($headers);
        $user = User::find()->where(['id'=>$user_id])->one();

        if (!empty($user_id)) {
            $pickupLat = $post['lat'];
            $pickupLng = $post['lng'];
            // $dropLat = $post['drop_lat'];
            // $dropLng = $post['drop_lng'];

            // Retrieve all city boundaries from the database
            $cities = City::find()->innerJoinWith('cityRateCards')->andWHere(['city.status'=>City::STATUS_ACTIVE])
            ->andWHere(['city_rate_card.status'=>CityRateCard::STATUS_ACTIVE])
            ->all();
            $selectedCity = null;
            $selectedCityId = null;
          
            foreach ($cities as $city) {
                $data['postData'] = $city->name;
                $boundary = Json::decode($city->boundary);
                $polygonArray = json_decode($boundary, true);
                // Check if pickup and drop points are inside the boundary
                if (
                    $this->isPointInsideBoundary(
                        $pickupLat,
                        $pickupLng,
                        $polygonArray
                    )
                ) {                        
                    $selectedCity = $city->name;
                    $selectedCityId = $city->id;

                    break;
                }
            }
            // The customer's chosen city is kept on the customer's record when this process has it; a service on its own
            // database has no customer rows (the user app keeps the choice), so only the answer is returned.
            $saved = true;
            if ($user) {
                $user->city_id = !empty($selectedCityId) ? $selectedCityId : null;
                $saved = $user->save(false);
            }

            if ($selectedCity !== null && $selectedCityId != null && $saved) {
                $data['status'] = self::API_OK;
                $data['selected_city'] = $selectedCity;
                $data['selected_city_id'] = $selectedCityId;
            } else {
                $data['status'] = self::API_NOK;
                $data['error'] =
                    'Selected location is not inside any city boundary';
            }
        } else {
            $data['status'] = self::API_NOK;
            $data['error'] = Yii::t('app', 'No User found');
        }
    // Anything unexpected is answered as NOK (the text is cleaned of SQL/stack details by sendJsonResponse).
    } catch (Exception $e) {
        $data['status'] = self::API_NOK;
        $data['error'] = $e->getMessage();
    }

    // REPLY: every path above ends here with {status: OK|NOK, details|error}.
    return $this->sendJsonResponse($data);
}
// [doc:end] actionCurrentCity
}
