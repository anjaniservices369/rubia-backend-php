<?php

namespace app\modules\admin\services;

use yii\db\Query;
use app\models\User;
use app\modules\admin\models\Booking;
use app\modules\admin\models\base\RideRequest;
use app\modules\messaging\services\PushNotifier;

/**
 * Numbers shown on the admin dashboard. One place for the status groupings, so "completed",
 * "ongoing" and so on mean the same thing for every vehicle type and for franchise (city) users.
 */
class DashboardStats
{
    /** Reject reason the dispatcher writes when no driver accepted within the offer timeout. */
    const NO_DRIVER_REASON = 'No driver accepted the ride';

    /**
     * Booking-flow (driver app) counters.
     *
     * @param int|null $cityId limit to a city (franchise users)
     */

    // [doc:start] categories - Ride categories shown on the dashboard: the active vehicles (Bike, Auto, Taxi, ...).
    /**
     * Ride categories shown on the dashboard: the active vehicles (Bike, Auto, Taxi, ...).
     * Labels follow the taxi service names: Bike Taxi, Auto Taxi, Car Taxi.
     *
     * @return array[] each ['id' => int, 'label' => string, 'key' => string]
     *
     * 'id' is vehicals.id, a different numbering from ServiceModules/serviceDashboard (vehicals uses
     * whatever order the rows were created in; ServiceModules fixes 2=Taxi, 3=Auto, 4=Bike). Match by
     * 'key' (the vehicle title, lowercased), not 'id', when scoping this to one service's own dashboard.
     */
    public static function categories()
    {
        $names = ['bike' => 'Bike Taxi', 'auto' => 'Auto Taxi', 'taxi' => 'Car Taxi', 'car' => 'Car Taxi'];

        $rows = (new Query())->select(['id', 'title'])->from('vehicals')->where(['status' => 1])->orderBy('id')->all();
        $categories = [];
        foreach ($rows as $row) {
            $key = strtolower(trim($row['title']));
            $categories[] = ['id' => (int) $row['id'], 'label' => $names[$key] ?? $row['title'], 'key' => $key];
        }

        return $categories;
    }
    // [doc:end] categories

    // [doc:start] bookings - Bookings
    public static function bookings($cityId = null)
    {
        if (!class_exists(\app\modules\admin\models\base\Booking::class)) {
            // the booking tables belong to the Halting drivers service; this process does not have them
            return ['all' => 0, 'completed' => 0, 'cancelled' => 0, 'pending' => 0, 'ongoing' => 0, 'noDriver' => 0];
        }
        $count = function (array $statuses = null) use ($cityId) {
            $q = Booking::find();
            if ($statuses !== null) {
                $q->andWhere(['status' => $statuses]);
            }
            if ($cityId) {
                $q->andWhere(['city_id' => $cityId]);
            }

            return (int) $q->count();
        };

        return [
            'all' => $count(),
            'completed' => $count([Booking::STATUS_RIDE_COMPLETED]),
            'cancelled' => $count([Booking::STATUS_CANCELLED, Booking::STATUS_DRIVER_CANCELLED, Booking::STATUS_USER_CANCELLED]),
            'pending' => $count([Booking::STATUS_WAITING_FOR_DRIVER]),
            'ongoing' => $count([Booking::STATUS_START_RIDE]),
            'driverRejected' => $count([Booking::STATUS_DRIVER_REJECTED]),
        ];
    }
    // [doc:end] bookings

    // [doc:start] rides - Taxi-flow (ride request) counters for one vehicle type.
    /**
     * Taxi-flow (ride request) counters for one vehicle type.
     *
     * pending  = waiting for a driver to accept
     * ongoing  = accepted, on the way, arrived or started
     * noDriver = nobody accepted within the offer timeout
     */
    public static function rides($vehicalType, $cityId = null)
    {
        if (!class_exists(RideRequest::class)) {
            // no taxi tables in this service's code: nothing to count
            return ['all' => 0, 'completed' => 0, 'cancelled' => 0, 'pending' => 0, 'ongoing' => 0, 'noDriver' => 0];
        }
        $count = function ($statuses = null, array $extra = []) use ($vehicalType, $cityId) {
            $q = RideRequest::find()->andWhere(['vehical_type' => $vehicalType]);
            if ($statuses !== null) {
                $q->andWhere(['status' => $statuses]);
            }
            if ($cityId) {
                $q->andWhere(['city_id' => $cityId]);
            }
            foreach ($extra as $condition) {
                $q->andWhere($condition);
            }

            return (int) $q->count();
        };

        $noDriver = ['reject_reason' => self::NO_DRIVER_REASON];

        return [
            'all' => $count(),
            'completed' => $count([RideRequest::STATUS_RIDE_COMPLETED, RideRequest::STATUS_RIDE_COMPLETED_PAID]),
            'cancelled' => $count(
                [RideRequest::STATUS_CANCEL_BY_USER, RideRequest::STATUS_CANCEL_BY_DRIVER],
                [['not', $noDriver]]
            ),
            'pending' => $count([RideRequest::STATUS_NEW_REQUEST, RideRequest::STATUS_RIDE_BOOKED]),
            'ongoing' => $count([
                RideRequest::STATUS_ACCEPTED_BY_DRIVER,
                RideRequest::STATUS_ON_THE_WAY,
                RideRequest::STATUS_ARRIVED,
                RideRequest::STATUS_START_RIDE,
            ]),
            'noDriver' => $count([RideRequest::STATUS_CANCEL_BY_DRIVER], [$noDriver]),
        ];
    }
    // [doc:end] rides

    // [doc:start] operations - System health numbers for administrators.
    /**
     * System health numbers for administrators.
     */
    public static function operations()
    {
        $taxiTotal = (int) User::find()->where(['user_role' => User::ROLE_TAXI])->count();
        $taxiOnline = (int) User::find()->where(['user_role' => User::ROLE_TAXI, 'online_status' => 1])->count();

        // Rides waiting for a driver: recent ones are live demand, older ones were never closed (stale).
        $since = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $waiting = 0;
        $oldest = null;
        $stale = 0;
        $noDriver24h = 0;
        if (class_exists(RideRequest::class)) {
            $waitingNow = RideRequest::find()
                ->where(['status' => RideRequest::STATUS_NEW_REQUEST])
                ->andWhere(['>=', 'created_on', $since]);
            $oldest = $waitingNow->min('created_on');
            $waiting = (int) (clone $waitingNow)->count();
            $stale = (int) RideRequest::find()
                ->where(['status' => RideRequest::STATUS_NEW_REQUEST])
                ->andWhere(['<', 'created_on', $since])
                ->count();
            $noDriver24h = (int) RideRequest::find()
                ->where(['reject_reason' => self::NO_DRIVER_REASON])
                ->andWhere(['>=', 'updated_on', $since])
                ->count();
        }

        $media = (new Query())
            ->select(['files' => 'COUNT(*)', 'bytes' => 'COALESCE(SUM(size),0)'])
            ->from('media')
            ->one();

        return [
            'taxiDrivers' => $taxiTotal,
            'taxiOnline' => $taxiOnline,
            'waitingRides' => $waiting,
            'staleRides' => $stale,
            'oldestWaitingMinutes' => $oldest ? max(0, (int) floor((time() - strtotime($oldest)) / 60)) : 0,
            'noDriver24h' => $noDriver24h,
            'mediaFiles' => (int) $media['files'],
            'mediaMb' => round($media['bytes'] / 1048576, 1),
            'push' => PushNotifier::describe(),
        ];
    }
    // [doc:end] operations
}
