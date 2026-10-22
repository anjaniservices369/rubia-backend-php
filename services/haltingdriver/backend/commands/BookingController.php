<?php

namespace app\commands;

use app\modules\dispatch\services\BookingExpiry;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Booking housekeeping.
 *
 *   php yii booking/expire-unanswered
 *
 * Cancels bookings that nobody accepted before their pickup time and notifies the customer.
 * Run it every minute from cron, next to the dispatch call.
 */
class BookingController extends Controller
{
    // [doc:start] actionExpireUnanswered - Expire Unanswered
    public function actionExpireUnanswered()
    {
        $r = BookingExpiry::run();
        $this->stdout("cancelled {$r['cancelled']}, customers notified {$r['notified']}\n");

        return ExitCode::OK;
    }
    // [doc:end] actionExpireUnanswered
}
