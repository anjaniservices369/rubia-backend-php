<?php

namespace app\modules\dispatch\services;

use app\models\User;
use app\modules\admin\models\DriverRequest;
use app\modules\admin\models\GcOrders;
use app\modules\admin\models\Orders;
use app\modules\admin\models\OrderStatus;
use app\modules\admin\models\RideRequest;
use app\modules\admin\models\TaxiDriverRequest;
use app\modules\admin\models\WebSetting;
use Yii;
use yii\base\Component;

/**
 * Taxi dispatcher: finds online taxi drivers near a ride's pickup and creates offers (taxi_driver_request) either
 * to one driver, to all nearby, or to the nearest group, and expires rides nobody answered
 * (expireIfUnanswered). Mode and radius come from the web settings `auto_dispatch_type` and
 * `auto_dispatch_radius`.
 *
 * The driver-service dispatcher is different and lives in UserController::actionAssignBookingToDriver
 * (see DEVELOPER_GUIDE.md section 3); cancelling unanswered driver-service bookings is BookingExpiry.
 */
class OrderDispatch extends Component
{

    // [doc:start] assignAuto - Assign Auto
    public function assignAuto()
    {
        $setting = new WebSetting();
        $auto_dispatch_type = $setting->getSettingBykey('auto_dispatch_type');
        $auto_dispatch_radius = $setting->getSettingBykey('auto_dispatch_radius');
        // Web Settings: mode 1 one by one, 2 all nearby at once (default), 3 nearest group; radius in km (default 10).
        $dispatchMode = in_array((int) $auto_dispatch_type, [1, 2, 3], true) ? (int) $auto_dispatch_type : 2;
        $radiusKm = (float) $auto_dispatch_radius > 0 ? (float) $auto_dispatch_radius : 10;

        $get = Yii::$app->request->get();
        $id = $get['ride_id'];
        if (!empty($get)) {
            $getOrder = RideRequest::find()
                ->Where(['id' => (int)$get['ride_id']])
                ->andWhere(['!=', 'status', RideRequest::STATUS_RIDE_COMPLETED])

                ->one();

            if (!empty($getOrder)) {

                $source_lat = $getOrder->pickup_latitude; //$post['source_lat'];
                $source_lng = $getOrder->pickup_longitude; //$post['source_lng'];
                //near by Drivers $pickup_lat =  $rideRequest->pickup_latitude;
                $multiplyFactor = '111.1111';
                $distanceQuery = "(" . $multiplyFactor . " *
                DEGREES(ACOS(LEAST(1.0, COS(RADIANS(user.lat))
                * COS(RADIANS(" . $source_lat . "))
                * COS(RADIANS(user.lng) - RADIANS(" . $source_lng . "))
                + SIN(RADIANS(user.lat))
                * SIN(RADIANS(" . $source_lat . "))))))";

                $nearby_drivers = User::find()
                    ->joinWith('driverDetails as dd')
                    ->select('user.*,' . $distanceQuery . ' as distance,
                (SELECT COUNT(o.id) FROM ride_request o) as rides_count')
                    ->where('user.lat')
                    ->andWhere(['<', $distanceQuery, $radiusKm])
                    ->andWhere('user.lng')
                    ->andWhere(['user_role' => User::ROLE_TAXI])
                    // offline drivers get no offers
                    ->andWhere(['user.online_status' => User::online_status_yes])
                    ->andWhere(['dd.vehical_id' => $getOrder->vehical_type])
                    ->all();

                // cos( radians('lat') ) * cos( radians('lng') - radians({$source_lng}) ) +
                // sin( radians({$source_lat}) ) * sin( radians('lat') ) ) ) AS distance")->having("distance <:distance")->addParams([

                //make dynamic from settings
                if (!empty($nearby_drivers)) {
                    //Check which Delivery Assign type
                    switch ($dispatchMode) {

                        case 1:
                            $assigned = Yii::$app->orderDispatch->assignOne($nearby_drivers, $id);

                            if ($assigned['status'] == 'NOK') {
                                $data['status'] = 'NOK';
                                $data['error'] =  $assigned['accept_state'];
                            } else {
                                $data['status'] = 'OK';
                                $data['msg'] = 'Order Assigned';
                            }
                            break;
                        case 2:
                            $assigned = Yii::$app->orderDispatch->assignAll($nearby_drivers, $id);
                            if ($assigned['status'] == 'NOK') {
                                $data['status'] = 'NOK';
                                $data['error'] = $assigned['accept_state'];
                            } else {
                                $data['status'] = 'OK';
                                $data['msg'] = $assigned['accept_state'];
                            }
                            break;
                        case 3:
                            $assigned = Yii::$app->orderDispatch->assignNearby($nearby_drivers, $id);
                            if ($assigned['status'] == 'NOK') {
                                $data['status'] = 'NOK';
                                $data['error'] = 'Order Assigning failed';
                            } else {
                                $data['status'] = 'OK';
                                $data['msg'] = 'Order Assigned to Nearby driver! Waiting for Accept';
                            }
                            break;

                        default:
                            $assigned = Yii::$app->orderDispatch->assignOne($nearby_drivers, $id);
                            if ($assigned['status'] == 'NOK') {
                                $data['status'] = 'NOK';
                                $data['error'] = 'Order Assigning failed';
                            } else {
                                $data['status'] = 'OK';
                                $data['msg'] = 'Order Assigned';
                            }
                            break;
                    }
                } else {
                    $data['status'] = 'NOK';
                    $data['error'] = 'No Nearby drivers found';
                }
            } else {
                $data['status'] = 'NOK';
                $data['error'] = 'No Order found';
            }
        } else {
            $data['status'] = 'NOK';
            $data['error'] = 'No data Posted';
        }
        return $data;
    }
    // [doc:end] assignAuto

    //Assigning One by One

    // [doc:start] assignOne - Assign One
    public function assignOne($nearby_drivers, $id)
    {

        $setting = new WebSetting();
        $auto_dispatch_wait_time = $setting->getSettingBykey('auto_dispatch_wait_time');
        $data = [];

        $i = 0;
        foreach ($nearby_drivers as $nearby_driver) {

            //Check order Accepted or Not
            $check_accept = TaxiDriverRequest::find()->Where([
                'ride_id' => $id,
                'status' => TaxiDriverRequest::RIDE_ACCEPT
            ])->one();
            if (empty($check_accept)) {

                $driver_request = new TaxiDriverRequest();
                $driver_request->ride_id = $id;
                $driver_request->driver_id = $nearby_driver['id'];
                $driver_request->status = TaxiDriverRequest::RIDE_ASSIGNED;
                $driver_request->updated_on = date('Y-m-d H:i:s');
                $driver_request->created_on = date('Y-m-d H:i:s');
                if ($driver_request->save()) {

                    $title = 'New Order #' . $id . ' assigned';
                    $body = 'New Order Request';
                    $send_noti = Yii::$app->push->notifyDriver($id, $driver_request->driver_id, $title, $body);
                    sleep(isset($auto_dispatch_wait_time) ? $auto_dispatch_wait_time : 30);

                    // Check Status of Accept

                    $check = $driver_request->find()->Where(['id' => $driver_request->id, 'driver_id' => $driver_request->driver_id])->one();

                    if ($check->status == TaxiDriverRequest::RIDE_ACCEPT) {

                        $data['status'] = "OK";
                        $data['details'] = $check;
                        break;
                    } else if ($check->status == TaxiDriverRequest::RIDE_REJECT) {
                        $data['status'] = "OK";
                        $data['details'] = $check;

                    } else {

                        $check->status = TaxiDriverRequest::RIDE_CANCELLED;
                        $check->updated_on = date('Y-m-d H:i:s');
                        if ($check->update(false)) {
                            $data['status'] = "NOK";
                            $data['details'] = $check;
                            $data['accept_state'] = "No Driver accepted your Order.Please contact Admin to assign Order manually to Delivery Driver";
                        }
                    }
                } else {
                    $data['status'] = "NOK";
                    $data['details'] = $driver_request;
                }

            } else {
                $data['status'] = "NOK";
                $data['details'] = "Order already Accepted";
                $data['accept_state'] = "Order already Accepted";
            }
        }
        return $data;
    }
    // [doc:end] assignOne

    //Assigning All Availble Drivers at once

    // [doc:start] assignAll - Assign All
    public function assignAll($nearby_drivers, $id)
    {

        $setting = new WebSetting();
        $auto_dispatch_wait_time = $setting->getSettingBykey('auto_dispatch_wait_time');
        $data = [];

        $i = 0;
        foreach ($nearby_drivers as $nearby_driver) {

            //Check order Accepted or Not
            $checkAlredyAssigned = TaxiDriverRequest::find()->where(
                [
                    'ride_id' => $id,
                    'driver_id' => $nearby_driver['id']
                ]
            )->one();

            $check_accept = TaxiDriverRequest::find()->Where([
                'ride_id' => $id,
                'status' => TaxiDriverRequest::RIDE_ACCEPT
            ])->one();
            if (empty($checkAlredyAssigned)) {
                if (empty($check_accept)) {

                    $driver_request = new TaxiDriverRequest();
                    $driver_request->ride_id = $id;
                    $driver_request->driver_id = $nearby_driver['id'];
                    $driver_request->status = TaxiDriverRequest::RIDE_ASSIGNED;
                    $driver_request->updated_on = date('Y-m-d H:i:s');
                    $driver_request->created_on = date('Y-m-d H:i:s');
                    if ($driver_request->save(false)) {
                        // Tell the driver there is a new offer (best effort, never blocks dispatch).
                        try {
                            Yii::$app->push->notifyDriver($id, $driver_request->driver_id, 'New ride request', 'A new ride request is available near you', 'newOrder');
                        } catch (\Throwable $e) {
                            Yii::warning('driver push failed: ' . $e->getMessage(), 'push');
                        }
                    } else {
                        $data['status'] = "NOK";
                        $data['details'] = $driver_request;
                        $data['accept_state'] = "failed to save request";
                    }
                    // Check Status of Accept code at Accepting Order API
                    $data['status'] = "OK";
                    $data['details'] = $driver_request;
                    $data['accept_state'] = "Ride Assigned to all Drivers";
                } else {
                    $data['status'] = "NOK";
                    $data['details'] = "Ride already Accepted";
                    $data['accept_state'] = "Ride already Accepted";
                }
            }else{
                $data['status'] = "NOK";
                $data['details'] = "All nearby drivers already assigned to this ride id";
                $data['accept_state'] = "All nearby drivers already assigned to this ride id";
            }
        }

        return $data;
    }
    // [doc:end] assignAll

    //    //Assigning Nearby One driver

    // [doc:start] assignNearby - Assign Nearby
    public function assignNearby($nearby_drivers, $id)
    {

        $setting = new WebSetting();
        $auto_dispatch_wait_time = $setting->getSettingBykey('auto_dispatch_wait_time');
        $data = [];

        $i = 0;

        //Check order Accepted or Not
        $check_accept = TaxiDriverRequest::find()
            ->Where([
                'ride_id' => $id,
                'status' => TaxiDriverRequest::RIDE_ACCEPT
            ])->one();
        if (empty($check_accept)) {

            $driver_request = new TaxiDriverRequest();
            $driver_request->ride_id = $id;
            $driver_request->driver_id = $nearby_drivers[0]['id'];
            $driver_request->status = TaxiDriverRequest::RIDE_ASSIGNED;
            $driver_request->updated_on = date('Y-m-d H:i:s');
            $driver_request->created_on = date('Y-m-d H:i:s');
            if ($driver_request->save(false)) {

                // //   $send_noti = Yii::$app->push->notifyDriver($id,$driver_request->driver_id,$title,$body );

            } else {
                $data['status'] = "NOK";
                $data['details'] = $driver_request;
            }

        } else {
            $data['status'] = "NOK";
            $data['details'] = "Order already Accepted";
        }

        return $data;
    }
    // [doc:end] assignNearby

    // [doc:start] expireIfUnanswered - Cancels a ride nobody accepted within the offer timeout, so the rider is not left waiting forever.
    /**
     * Cancels a ride nobody accepted within the offer timeout, so the rider is not left waiting forever.
     * Called while the rider polls ride-request-detail; set params['dispatch']['offerTimeout'] = 0 to disable.
     *
     * @return bool true when the ride was expired by this call
     */
    public function expireIfUnanswered($ride)
    {
        if ((int) $ride->status !== RideRequest::STATUS_NEW_REQUEST) {
            return false;
        }

        $timeout = (int) (Yii::$app->params['dispatch']['offerTimeout'] ?? 120);
        if ($timeout <= 0) {
            return false;
        }
        $created = strtotime($ride->created_on);
        if (!$created || (time() - $created) < $timeout) {
            return false;
        }

        // Only one request can win this update, and only while nobody has accepted in the meantime.
        $now = date('Y-m-d H:i:s');
        $changed = RideRequest::updateAll(
            ['status' => RideRequest::STATUS_CANCEL_BY_DRIVER, 'reject_reason' => 'No driver accepted the ride', 'updated_on' => $now],
            ['id' => $ride->id, 'status' => RideRequest::STATUS_NEW_REQUEST]
        );
        if (!$changed) {
            return false;
        }

        TaxiDriverRequest::updateAll(
            ['status' => TaxiDriverRequest::RIDE_CANCELLED],
            ['ride_id' => $ride->id, 'status' => TaxiDriverRequest::RIDE_ASSIGNED]
        );
        $ride->status = RideRequest::STATUS_CANCEL_BY_DRIVER;
        $ride->reject_reason = 'No driver accepted the ride';

        try {
            $log = new \app\modules\admin\models\RideStatuses();
            $log->ride_id = $ride->id;
            $log->status = RideRequest::STATUS_CANCEL_BY_DRIVER;
            $log->message = 'No driver accepted the ride within ' . $timeout . ' seconds';
            $log->save(false);
            Yii::$app->push->notifyUser('', $ride->user_id, 'No driver found', 'No driver accepted your ride request. Please try again.');
        } catch (\Throwable $e) {
            Yii::warning('expire follow-up failed: ' . $e->getMessage(), 'push');
        }

        return true;
    }}
    // [doc:end] expireIfUnanswered
