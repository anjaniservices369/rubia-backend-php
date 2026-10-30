<?php

namespace app\modules\dispatch\services;

use app\modules\admin\models\base\Booking;
use app\modules\admin\models\base\DriverRequests;
use app\modules\admin\models\base\WebSetting;
use Yii;

/**
 * Cancels bookings nobody accepted before their pickup time and tells the customer.
 *
 * A booking still "waiting for driver" whose pickup date and time have passed is moved to
 * STATUS_BOOKING_AUTO_CANCELLED, its open driver offers are closed, and the customer gets a push and an in-app
 * message with the ride details asking them to book again.
 *
 * Runs from cron and the booking lists/details in both apps and the dashboard; acceptance also checks the cutoff atomically.
 * The optional web setting `booking_expiry_grace_minutes` (default 0) allows some minutes after the pickup time.
 */
class BookingExpiry
{
    const REASON = 'Auto cancelled: no driver was available at the pickup time';

    // [doc:start] run - Run
    /**
     * @return array [cancelled => int, notified => int]
     */
    public static function run($limit = 200, array $scope = [])
    {
        $available = self::availablePickupCondition();
        $bookings = Booking::find()
            ->where(['status' => Booking::STATUS_WAITING_FOR_DRIVER])
            ->andWhere($scope)
            ->andWhere(['not', $available])
            ->orderBy(['id' => SORT_ASC])
            ->limit((int) $limit)
            ->all();

        $result = ['cancelled' => 0, 'notified' => 0];
        foreach ($bookings as $booking) {
            // Atomic: loses cleanly against a driver accepting at the same moment.
            $changed = Booking::updateAll(
                ['status' => Booking::STATUS_BOOKING_AUTO_CANCELLED, 'cancel_reason' => self::REASON, 'updated_on' => date('Y-m-d H:i:s')],
                ['and', ['id' => $booking->id, 'status' => Booking::STATUS_WAITING_FOR_DRIVER], ['not', $available]]
            );
            if (!$changed) {
                continue;
            }
            $result['cancelled']++;

            DriverRequests::updateAll(
                ['status' => DriverRequests::STATUS_BOOKING_AUTO_CANCELLED, 'updated_on' => date('Y-m-d H:i:s')],
                ['booking_id' => $booking->id, 'status' => DriverRequests::STATUS_WAITING_FOR_DRIVER]
            );

            try {
                // the console app has no 'push' component (it is registered by the messaging module on web)
                $push = Yii::$app->has('push') ? Yii::$app->get('push') : new \app\modules\messaging\services\PushService();
                $push->notifyUser($booking->id, $booking->user_id, 'Ride cancelled - please book again', self::message($booking));
                $result['notified']++;
            } catch (\Throwable $e) {
                Yii::error('booking ' . $booking->id . ' expiry notification failed: ' . $e->getMessage(), 'push');
            }
        }

        return $result;
    }
    // [doc:end] run

    // [doc:start] availablePickupCondition - SQL condition shared by offer queries and the atomic accept, including the configured grace period.
    /** SQL condition shared by offer queries and the atomic accept, including the configured grace period. */
    public static function availablePickupCondition()
    {
        $grace = (int) (WebSetting::find()->select('value')->where(['setting_key' => 'booking_expiry_grace_minutes'])->scalar() ?: 0);
        $cutoff = date('Y-m-d H:i:s', time() - max(0, $grace) * 60);

        return ['or',
            ['pickup_date' => null],
            ['>=', 'TIMESTAMP(pickup_date, COALESCE(pickup_time, \'00:00:00\'))', $cutoff],
        ];
    }
    // [doc:end] availablePickupCondition

    private static function message(Booking $b)
    {
        $when = trim($b->pickup_date . ' ' . substr((string) $b->pickup_time, 0, 5));
        $ref = !empty($b->booking_number) ? $b->booking_number : '#' . $b->id;
        $text = 'Your ride ' . $ref . ' for ' . $when;
        if (!empty($b->pickup_address)) {
            $text .= ' from ' . $b->pickup_address;
        }
        if (!empty($b->drop_address)) {
            $text .= ' to ' . $b->drop_address;
        }

        return $text . ' was cancelled because no driver was available. Please book again.';
    }
}
