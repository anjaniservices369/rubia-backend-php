<?php

namespace app\modules\billing\services;

use app\modules\geo\services\ServiceModules;
use Yii;
use yii\db\Query;

/**
 * Invoices and tax of each service, on their own.
 *
 * Every service (1 driver, 2 taxi, 3 auto, 4 bike) has its own billing profile (legal name, GSTIN, invoice prefix),
 * its own invoice number series per financial year (April-March) and its own invoices. An invoice is issued once per
 * ride, when the money is recorded: a booking of the driver service at its earnings row, a taxi / auto / bike ride
 * when its earnings are approved (paid). Issuing is idempotent (one invoice per source) and never breaks a ride:
 * a failure is logged and the ride goes on.
 *
 * Amounts: the ride total is what the customer paid and it includes GST. taxable = total - GST, split into CGST and
 * SGST halves (IGST stays 0 until a service sells across states). The commission column is the company's share of
 * the ride, kept for the tax and settlement reports.
 */
class InvoiceService
{
    // [doc:start] financialYear - Indian financial year of a date: 2026-04-01 .. 2027-03-31 is "2026-27".
    /** Indian financial year of a date: 2026-04-01 .. 2027-03-31 is "2026-27". */
    public static function financialYear($date = null)
    {
        $t = $date ? strtotime($date) : time();
        $y = (int) date('Y', $t);
        $start = ((int) date('n', $t) >= 4) ? $y : $y - 1;

        return $start . '-' . substr((string) ($start + 1), 2);
    }
    // [doc:end] financialYear

    // [doc:start] forBooking - Invoice for a completed driver-service booking. @return array|null the invoice row.
    /** Invoice for a completed driver-service booking. @return array|null the invoice row */
    public static function forBooking($bookingId)
    {
        try {
            $b = (new Query())->from('booking')->where(['id' => (int) $bookingId])->one();
            $e = (new Query())->from('driver_earnings')->where(['booking_id' => (int) $bookingId])->orderBy(['id' => SORT_DESC])->one();
            if (!$b || !$e) {
                return null;
            }
            $total = round((float) $e['total_amount'], 2);
            $gst = round(min((float) $b['gst'], $total), 2);

            return self::issue(ServiceModules::DRIVER, 'booking', (int) $bookingId, [
                'customer_id' => (int) $b['user_id'], 'partner_id' => (int) $b['driver_id'], 'city_id' => (int) $b['city_id'],
                'total' => $total, 'gst' => $gst, 'commission' => round((float) $e['admin_earning'], 2),
                'date' => $e['created_on'] ?: date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $ex) {
            Yii::error('invoice for booking ' . $bookingId . ' failed: ' . $ex->getMessage(), 'api');

            return null;
        }
    }
    // [doc:end] forBooking

    // [doc:start] forRide - Invoice for a paid taxi / auto / bike ride. @return array|null the invoice row.
    /** Invoice for a paid taxi / auto / bike ride. @return array|null the invoice row */
    public static function forRide($rideId)
    {
        try {
            $r = (new Query())->from('ride_request')->where(['id' => (int) $rideId])->one();
            $e = (new Query())->from('ride_earnings')->where(['ride_id' => (int) $rideId])->orderBy(['id' => SORT_DESC])->one();
            if (!$r || !$e) {
                return null;
            }
            $module = (int) ($e['module'] ?: ServiceModules::ofVehicle($r['vehical_type']));
            if (!ServiceModules::isTaxi($module)) {
                return null;
            }
            $total = round((float) ($e['total_amount'] ?: $r['final_price']), 2);
            $cg = (float) ($r['cgst'] !== null && $r['cgst'] !== '' ? $r['cgst'] : (new \app\modules\admin\models\WebSetting())->getSettingBykey('cgst'));
            $sg = (float) ($r['sgst'] !== null && $r['sgst'] !== '' ? $r['sgst'] : (new \app\modules\admin\models\WebSetting())->getSettingBykey('sgst'));
            $pct = max(0.0, $cg + $sg);
            $gst = $pct > 0 ? round($total - $total / (1 + $pct / 100), 2) : 0.0;

            return self::issue($module, 'ride', (int) $rideId, [
                'customer_id' => (int) $r['user_id'], 'partner_id' => (int) $r['driver_id'], 'city_id' => (int) $r['city_id'],
                'total' => $total, 'gst' => $gst, 'commission' => round((float) $e['admin_earning'], 2),
                'date' => $e['updated_on'] ?: date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $ex) {
            Yii::error('invoice for ride ' . $rideId . ' failed: ' . $ex->getMessage(), 'api');

            return null;
        }
    }
    // [doc:end] forRide

    /**
     * Writes one invoice (or returns the one that exists for the source) and takes the next number of the service's
     * series for the financial year.
     */
    private static function issue($module, $sourceType, $sourceId, array $d)
    {
        $existing = (new Query())->from('service_invoice')
            ->where(['module' => $module, 'source_type' => $sourceType, 'source_id' => $sourceId])->one();
        if ($existing) {
            return $existing;
        }
        $db = Yii::$app->db;
        $fy = self::financialYear($d['date']);
        $profile = self::profile($module);
        $tx = $db->beginTransaction();
        try {
            $db->createCommand(
                'INSERT INTO service_invoice_counter (module, fy, last_seq) VALUES (:m, :fy, 1) ON DUPLICATE KEY UPDATE last_seq = last_seq + 1',
                [':m' => $module, ':fy' => $fy]
            )->execute();
            $seq = (int) (new Query())->select('last_seq')->from('service_invoice_counter')->where(['module' => $module, 'fy' => $fy])->scalar();
            $number = $profile['invoice_prefix'] . '/' . $fy . '/' . str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
            $gst = (float) $d['gst'];
            $half = round($gst / 2, 2);
            $db->createCommand()->insert('service_invoice', [
                'module' => $module, 'number' => $number, 'fy' => $fy, 'source_type' => $sourceType, 'source_id' => $sourceId,
                'customer_id' => $d['customer_id'] ?: null, 'partner_id' => $d['partner_id'] ?: null, 'city_id' => $d['city_id'] ?: null,
                'issued_on' => $d['date'], 'taxable' => round($d['total'] - $gst, 2), 'cgst' => $half, 'sgst' => round($gst - $half, 2),
                'igst' => 0, 'total' => $d['total'], 'commission' => $d['commission'], 'status' => 'issued',
                'supplier_gstin' => $profile['gstin'],
            ])->execute();
            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            // a concurrent request may have issued it first
            $again = (new Query())->from('service_invoice')
                ->where(['module' => $module, 'source_type' => $sourceType, 'source_id' => $sourceId])->one();
            if ($again) {
                return $again;
            }
            throw $e;
        }

        return (new Query())->from('service_invoice')->where(['module' => $module, 'number' => $number])->one();
    }

    // [doc:start] profile - Profile
    public static function profile($module)
    {
        $p = (new Query())->from('service_billing_profile')->where(['module' => (int) $module])->one();

        return $p ?: ['module' => (int) $module, 'legal_name' => '', 'gstin' => '', 'pan' => '', 'address' => '',
            'state_code' => '', 'invoice_prefix' => strtoupper(substr(ServiceModules::all()[(int) $module]['key'] ?? 'INV', 0, 3)), 'sac_code' => '9964'];
    }
    // [doc:end] profile
}
