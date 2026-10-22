<?php

namespace app\modules\dispatch\services;

use app\models\User;
use app\modules\admin\models\base\Booking;
use app\modules\admin\models\base\BookingLogs;
use app\modules\admin\models\base\DriverRequests;
use Yii;
use yii\db\Exception as DbException;

/**
 * What an administrator can do to a booking from the dashboard.
 *
 *   cancel()        close the booking (any state before completion), free the driver, tell both sides
 *   reopen()        take the driver off and offer the booking to drivers again
 *   assignDriver()  give the booking to a chosen driver and mark it accepted
 *   setProgress()   move an assigned ride between accepted / on the way / arrived / started
 *
 * Completing a ride is deliberately not here: it posts the fare, commission and wallet entries and belongs to the
 * driver's complete-ride step. A ride stuck in progress is cancelled instead.
 * Every change is written to booking_logs with the administrator's id.
 */
class BookingAdmin
{
    const OK = 'ok';

    // [doc:start] progressStates - States an administrator may set on an assigned ride.
    /** States an administrator may set on an assigned ride. */
    public static function progressStates()
    {
        return [
            Booking::STATUS_DRIVER_ACCEPTED => 'Driver accepted',
            Booking::STATUS_GO_FOR_PICKUP => 'On the way to pickup',
            Booking::STATUS_ARRIVED_LOCATION => 'Arrived at pickup',
            Booking::STATUS_START_RIDE => 'Ride started',
        ];
    }
    // [doc:end] progressStates

    // [doc:start] isOpen - Bookings that are still open, i.e. may be changed.
    /** Bookings that are still open, i.e. may be changed. */
    public static function isOpen(Booking $b)
    {
        return in_array((int) $b->status, [
            Booking::STATUS_WAITING_FOR_DRIVER, Booking::STATUS_DRIVER_ACCEPTED, Booking::STATUS_GO_FOR_PICKUP,
            Booking::STATUS_ARRIVED_LOCATION, Booking::STATUS_START_RIDE, Booking::STATUS_DRIVER_REJECTED,
            Booking::STATUS_DRIVER_CANCELLED,
        ], true);
    }
    // [doc:end] isOpen

    // [doc:start] cancel - Cancel
    /**
     * @return string self::OK or the reason it was refused
     */
    public static function cancel(Booking $b, $reason, $adminId)
    {
        if (!self::isOpen($b)) {
            return 'This booking is already closed.';
        }
        $reason = trim((string) $reason);
        if ($reason === '') {
            return 'Please give a reason.';
        }
        $driverId = $b->driver_id;
        self::move($b, Booking::STATUS_CANCELLED, ['cancel_reason' => 'Cancelled by admin: ' . $reason, 'driver_id' => null]);
        DriverRequests::updateAll(
            ['status' => DriverRequests::STATUS_BOOKING_AUTO_CANCELLED, 'updated_on' => date('Y-m-d H:i:s')],
            ['and', ['booking_id' => $b->id], ['<>', 'status', DriverRequests::STATUS_BOOKING_AUTO_CANCELLED]]
        );
        self::log($b, 'Cancelled by admin: ' . $reason, $adminId);
        self::tell($b->user_id, $b->id, 'Ride cancelled',
            'Your ride ' . self::ref($b) . ' was cancelled by Rubia support. ' . $reason . ' Please book again if you still need a ride.');
        if ($driverId) {
            self::tell($driverId, $b->id, 'Ride cancelled', 'Booking ' . self::ref($b) . ' was cancelled by Rubia support.');
        }

        return self::OK;
    }
    // [doc:end] cancel

    // [doc:start] reopen - Reopen
    public static function reopen(Booking $b, $adminId)
    {
        if (!self::isOpen($b)) {
            return 'This booking is already closed.';
        }
        if ((int) $b->status === Booking::STATUS_START_RIDE) {
            return 'A ride that has started cannot be sent back to drivers. Cancel it instead.';
        }
        $driverId = $b->driver_id;
        self::move($b, Booking::STATUS_WAITING_FOR_DRIVER, ['driver_id' => null, 'cancel_reason' => null]);
        if ($driverId) {
            DriverRequests::updateAll(
                ['status' => DriverRequests::STATUS_DRIVER_CANCELLED, 'updated_on' => date('Y-m-d H:i:s')],
                ['booking_id' => $b->id, 'driver_id' => $driverId]
            );
            self::tell($driverId, $b->id, 'Ride reassigned', 'Booking ' . self::ref($b) . ' was taken off you by Rubia support.');
        }
        self::log($b, 'Sent back to drivers by admin', $adminId);
        self::tell($b->user_id, $b->id, 'Finding you a driver', 'Your ride ' . self::ref($b) . ' is being offered to nearby drivers again.');

        return self::OK;
    }
    // [doc:end] reopen

    // [doc:start] assignDriver - Assign Driver
    public static function assignDriver(Booking $b, $driverId, $adminId)
    {
        if (!self::isOpen($b) || (int) $b->status === Booking::STATUS_START_RIDE) {
            return 'This booking cannot be assigned any more.';
        }
        $driver = User::find()->where(['id' => (int) $driverId, 'user_role' => User::ROLE_DRIVER])->one();
        if (!$driver || (int) $driver->status !== User::STATUS_ACTIVE) {
            return 'Please choose an active driver.';
        }
        $busy = Booking::find()->where(['driver_id' => $driver->id, 'status' => [
            Booking::STATUS_DRIVER_ACCEPTED, Booking::STATUS_GO_FOR_PICKUP,
            Booking::STATUS_ARRIVED_LOCATION, Booking::STATUS_START_RIDE,
        ]])->andWhere(['<>', 'id', $b->id])->exists();
        if ($busy) {
            return 'This driver already has a ride in progress.';
        }

        $previous = $b->driver_id;
        $tx = Yii::$app->db->beginTransaction();
        try {
            self::move($b, Booking::STATUS_DRIVER_ACCEPTED, ['driver_id' => $driver->id, 'cancel_reason' => null]);
            // The chosen driver's offer is the accepted one; every other offer is closed.
            DriverRequests::updateAll(
                ['status' => DriverRequests::STATUS_BOOKING_AUTO_CANCELLED, 'updated_on' => date('Y-m-d H:i:s')],
                ['and', ['booking_id' => $b->id], ['<>', 'driver_id', $driver->id]]
            );
            $offer = DriverRequests::find()->where(['booking_id' => $b->id, 'driver_id' => $driver->id])->one() ?: new DriverRequests();
            $offer->booking_id = $b->id;
            $offer->driver_id = $driver->id;
            $offer->status = Booking::STATUS_DRIVER_ACCEPTED;
            $offer->save(false);
            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            Yii::error('admin assign failed: ' . $e->getMessage(), 'api');

            return 'Could not assign the driver. Please try again.';
        }

        self::log($b, 'Assigned to driver #' . $driver->id . ' by admin', $adminId);
        self::tell($driver->id, $b->id, 'New ride assigned', 'Rubia support assigned booking ' . self::ref($b) . ' to you.');
        self::tell($b->user_id, $b->id, 'Driver assigned', 'A driver has been assigned to your ride ' . self::ref($b) . '.');
        if ($previous && (int) $previous !== (int) $driver->id) {
            self::tell($previous, $b->id, 'Ride reassigned', 'Booking ' . self::ref($b) . ' was given to another driver.');
        }

        return self::OK;
    }
    // [doc:end] assignDriver

    // [doc:start] setProgress - Set Progress
    public static function setProgress(Booking $b, $status, $adminId)
    {
        $states = self::progressStates();
        if (!isset($states[(int) $status])) {
            return 'Please choose a valid ride state.';
        }
        if (empty($b->driver_id) || !self::isOpen($b) || (int) $b->status === Booking::STATUS_WAITING_FOR_DRIVER) {
            return 'Assign a driver first.';
        }
        $extra = [];
        if ((int) $status === Booking::STATUS_START_RIDE && empty($b->start_time)) {
            $extra['start_time'] = date('Y-m-d H:i:s');
        }
        self::move($b, (int) $status, $extra);
        self::log($b, 'Ride state set to "' . $states[(int) $status] . '" by admin', $adminId);

        return self::OK;
    }
    // [doc:end] setProgress

    private static function move(Booking $b, $status, array $extra = [])
    {
        $attrs = array_merge(['status' => $status, 'updated_on' => date('Y-m-d H:i:s')], $extra);
        Booking::updateAll($attrs, ['id' => $b->id]);
        foreach ($attrs as $k => $v) {
            $b->$k = $v;
        }
    }

    private static function log(Booking $b, $text, $adminId)
    {
        try {
            $log = new BookingLogs();
            $log->booking_id = $b->id;
            $log->description = $text;
            $log->status = BookingLogs::STATUS_ACTIVE;
            $log->create_user_id = $adminId;
            $log->save(false);
        } catch (DbException $e) {
            Yii::error('booking log failed: ' . $e->getMessage(), 'api');
        }
    }

    private static function tell($userId, $bookingId, $title, $body)
    {
        try {
            $push = Yii::$app->has('push') ? Yii::$app->get('push') : new \app\modules\messaging\services\PushService();
            $push->notifyUser($bookingId, $userId, $title, $body);
        } catch (\Throwable $e) {
            Yii::error('admin booking notification failed: ' . $e->getMessage(), 'push');
        }
    }

    private static function ref(Booking $b)
    {
        return !empty($b->booking_number) ? $b->booking_number : '#' . $b->id;
    }
}
