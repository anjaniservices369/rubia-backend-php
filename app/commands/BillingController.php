<?php

namespace app\commands;

use app\modules\billing\services\InvoiceService;
use app\modules\geo\services\ServiceModules;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Query;

/**
 * Invoices of the services.
 *
 *   php yii billing/backfill --service=1            list the completed rides that have no invoice yet (driver service)
 *   php yii billing/backfill --service=4 --apply    issue them, in date order, in the service's own series
 *
 * New rides are invoiced automatically when their earnings are recorded; this is only for rides that were completed
 * before invoicing existed. Numbers are taken in date order inside each financial year. Issue them deliberately: an
 * invoice number is a tax document.
 */
class BillingController extends Controller
{
    public $service = null;
    public $apply = false;
    public $limit = 5000;

    // [doc:start] options - Options
    public function options($actionID)
    {
        return array_merge(parent::options($actionID), ['service', 'apply', 'limit']);
    }
    // [doc:end] options

    // [doc:start] actionBackfill - Backfill
    public function actionBackfill()
    {
        $module = (int) $this->service;
        if (!ServiceModules::exists($module)) {
            $this->stderr("Give a service: --service=1 (driver), 2 (taxi), 3 (auto) or 4 (bike)\n");

            return ExitCode::USAGE;
        }
        if (ServiceModules::isTaxi($module)) {
            $ids = (new Query())->select('e.ride_id')->from(['e' => 'ride_earnings'])
                ->leftJoin(['i' => 'service_invoice'], "i.module = e.module AND i.source_type = 'ride' AND i.source_id = e.ride_id")
                ->where(['e.module' => $module, 'e.status' => 1])->andWhere(['i.id' => null])->orderBy('e.updated_on')->limit((int) $this->limit)->column();
        } else {
            $ids = (new Query())->select('e.booking_id')->from(['e' => 'driver_earnings'])
                ->leftJoin(['i' => 'service_invoice'], "i.module = 1 AND i.source_type = 'booking' AND i.source_id = e.booking_id")
                ->where(['e.module' => 1])->andWhere(['i.id' => null])->orderBy('e.created_on')->limit((int) $this->limit)->column();
        }
        $this->stdout(count($ids) . ' completed ride(s) of ' . ServiceModules::title($module) . " have no invoice.\n");
        if (!$this->apply) {
            $this->stdout("dry run - add --apply to issue them\n");

            return ExitCode::OK;
        }
        $n = 0;
        foreach ($ids as $id) {
            $r = ServiceModules::isTaxi($module) ? InvoiceService::forRide($id) : InvoiceService::forBooking($id);
            $n += $r ? 1 : 0;
        }
        $this->stdout("issued $n invoice(s)\n");

        return ExitCode::OK;
    }
    // [doc:end] actionBackfill
}
