<?php

namespace app\modules\taxicore\partner\services;

use Yii;
use yii\base\Component;
use app\modules\admin\models\RejectLog;
use app\modules\admin\models\RideEarnings;
use app\modules\admin\models\RideStatuses;
use app\modules\admin\models\TaxiDriverRequest;
use app\modules\admin\models\base\RideRequest;

/**
 * What a taxi driver can do with ride offers: see them, accept one, reject one.
 *
 * Rules:
 *  - an offer is only valid while the ride is still waiting for a driver (status NEW_REQUEST);
 *  - the first driver to accept wins (atomic claim); the other drivers' offers are withdrawn;
 *  - a reject only withdraws that driver's own offer, the ride stays open for the others.
 */
class TaxiOfferService extends Component
{
    // [doc:start] pendingFor - Rides that are offered to the driver and still waiting for a driver, oldest first.
    /**
     * Rides that are offered to the driver and still waiting for a driver, oldest first.
     *
     * @return RideRequest[]
     */
    public function pendingFor($driverId)
    {
        $rideIds = TaxiDriverRequest::find()
            ->select('ride_id')
            ->where(['driver_id' => $driverId, 'status' => TaxiDriverRequest::RIDE_ASSIGNED])
            ->orderBy('id')
            ->column();
        if (!$rideIds) {
            return [];
        }

        return RideRequest::find()
            ->where(['id' => $rideIds, 'status' => RideRequest::STATUS_NEW_REQUEST])
            ->orderBy('id')
            ->all();
    }
    // [doc:end] pendingFor

    // [doc:start] accept - Accepts the ride for the driver.
    /**
     * Accepts the ride for the driver.
     *
     * @return array ['ok' => bool, 'error' => string|null, 'ride' => RideRequest|null]
     */
    public function accept($driverId, $rideId)
    {
        // Each service has its own wallet and its own minimum balance (Web Settings): taxi, auto and bike are checked
        // against their own. Enforcement is off until the service's switch (wallet_min_enforced_<module>) is set to 1.
        $rideRow = RideRequest::findOne($rideId);   // limited to the bound service by RideRequestQuery
        if (!$rideRow) {
            return ['ok' => false, 'ride' => null, 'error' => 'Another driver has accepted this ride. Wait for the next ride'];
        }
        {
            $module = \app\modules\geo\services\ServiceModules::ofVehicle($rideRow->vehical_type);
            $minKey = [2 => 'taxi_rider_mim_wallet_balance', 3 => 'taxi_min_wallet_balance', 4 => 'bike_rider_mim_wallet_balance'][$module] ?? null;
            $settings = new \app\modules\admin\models\WebSetting();
            if ($minKey && (int) $settings->getSettingBykey('wallet_min_enforced_' . $module) === 1) {
                $min = (float) $settings->getSettingBykey($minKey);
                $balance = (float) \app\modules\admin\models\base\DriverWallet::availableBalance($driverId, $module);
                if ($balance < $min) {
                    return ['ok' => false, 'ride' => null, 'error' => 'Your ' . \app\modules\geo\services\ServiceModules::title($module) . ' wallet balance (Rs ' . $balance . ') is below the minimum of Rs ' . $min . '. Please add money to accept rides.'];
                }
            }
        }

        // Atomic claim: only one driver can move the ride out of NEW_REQUEST.
        $claimed = RideRequest::updateAll(
            [
                'driver_id' => $driverId,
                'status' => RideRequest::STATUS_ACCEPTED_BY_DRIVER,
                'updated_on' => date('Y-m-d H:i:s'),
            ],
            ['id' => $rideId, 'status' => RideRequest::STATUS_NEW_REQUEST]
        );
        if (!$claimed) {
            return ['ok' => false, 'error' => 'Another driver has accepted this ride. Wait for the next ride', 'ride' => null];
        }

        $ride = RideRequest::findOne($rideId);
        $ride->syncOffers();

        $name = $ride->driver->first_name ?? '';

        $earnings = new RideEarnings();
        $earnings->ride_id = $ride->id;
        $earnings->driver_id = $ride->driver_id;
        $earnings->total_ditance_km = 0;
        $earnings->admin_earning = 0;
        $earnings->driver_earning = 0;
        $earnings->total_amount = 0;
        $earnings->payment_method = $ride->payment_method;
        $earnings->status = RideEarnings::STATUS_PENDING;
        $earnings->save(false);

        $log = new RideStatuses();
        $log->ride_id = $ride->id;
        $log->status = $ride->status;
        $log->message = 'Request Accepted By ' . $name;
        $log->save(false);

        Yii::$app->push->notifyUser(
            '',
            $ride->user_id,
            'Ride Accepted',
            'Driver ' . $name . ' has accepted your ride. Your OTP for this ride is ' . $ride->otp
        );

        return ['ok' => true, 'error' => null, 'ride' => $ride];
    }
    // [doc:end] accept

    // [doc:start] reject - Rejects the offer for this driver only.
    /**
     * Rejects the offer for this driver only.
     */
    public function reject($driverId, $rideId)
    {
        $log = new RejectLog();
        $log->taxi_driver_id = $driverId; // column keeps its historical name
        $log->ride_id = $rideId;
        $log->status = RejectLog::STATUS_ACTIVE;
        if (!$log->save(false)) {
            return false;
        }

        TaxiDriverRequest::updateAll(
            ['status' => TaxiDriverRequest::RIDE_REJECT],
            ['ride_id' => $rideId, 'driver_id' => $driverId, 'status' => TaxiDriverRequest::RIDE_ASSIGNED]
        );

        return true;
    }
    // [doc:end] reject

    // [doc:start] isOpen - Whether the ride can still be answered (waiting for a driver).
    /**
     * Whether the ride can still be answered (waiting for a driver).
     */
    public function isOpen($rideId)
    {
        return RideRequest::find()
            ->where(['id' => $rideId, 'status' => RideRequest::STATUS_NEW_REQUEST])
            ->exists();
    }
    // [doc:end] isOpen
}