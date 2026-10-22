<?php

namespace app\modules\billing\services;

use app\modules\geo\services\ServiceModules;
use yii\db\Query;

/**
 * Numbers of one service on the database this process is connected to. The service dashboard shows them; the super
 * admin dashboard gets the same array from each separate service through its signed reporting endpoint.
 */
class ServiceReport
{
    // [doc:start] summary - Summary
    /**
     * @return array today / period rides and money, online partners, partner wallets held, invoices and tax of the period
     */
    public static function summary($module, $from = null, $to = null)
    {
        $ok = function ($d) {
            return is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
        };
        $from = $ok($from) ? $from : date('Y-m-01');
        $to = $ok($to) ? $to : date('Y-m-d');
        $taxi = ServiceModules::isTaxi($module);
        $rideTable = $taxi ? 'ride_request' : 'booking';
        $doneStatus = $taxi ? [9, 10] : [7];
        $vehicles = $taxi ? array_keys(ServiceModules::vehicles($module)) : null;

        $rides = function ($a, $b) use ($rideTable, $taxi, $vehicles) {
            $q = (new Query())->from($rideTable)->where(['between', 'created_on', $a . ' 00:00:00', $b . ' 23:59:59']);

            return $taxi ? $q->andWhere(['vehical_type' => $vehicles]) : $q;
        };
        $money = function ($a, $b) use ($module, $taxi) {
            $t = $taxi ? 'ride_earnings' : 'driver_earnings';
            $r = (new Query())->from($t)->where(['module' => $module])->andWhere(['between', 'created_on', $a . ' 00:00:00', $b . ' 23:59:59'])
                ->select(['COUNT(*) AS rides', 'COALESCE(SUM(total_amount),0) AS gross', 'COALESCE(SUM(admin_earning),0) AS company', 'COALESCE(SUM(driver_earning),0) AS partner'])->one();

            return ['rides' => (int) $r['rides'], 'gross' => round($r['gross'], 2), 'company' => round($r['company'], 2), 'partner' => round($r['partner'], 2)];
        };
        $today = date('Y-m-d');
        $role = $taxi ? 'taxi' : 'Driver';
        $partnerQuery = function () use ($role, $taxi, $module) {
            $q = (new Query())->from('user')->where(['user.user_role' => $role]);
            // taxi partners belong to the service of their vehicle
            return $taxi ? $q->innerJoin('driver_details dd', 'dd.user_id = user.id')->andWhere(new \yii\db\Expression('IFNULL((SELECT v.vehicle_type FROM vehicals v WHERE v.id = dd.vehical_id), dd.vehical_id) = ' . (int) $module)) : $q;
        };
        $online = $partnerQuery()->andWhere(['user.status' => 10, 'user.online_status' => 1])->count();
        $partners = $partnerQuery()->count();
        $w = (new Query())->from('driver_wallet')->where(['module' => $module]);
        $credit = (float) (clone $w)->andWhere(['payment_type' => 1, 'status' => 1])->sum('amount');
        $debit = (float) (clone $w)->andWhere(['payment_type' => 2, 'status' => [0, 1]])->sum('amount');
        $inv = (new Query())->from('service_invoice')->where(['module' => $module, 'status' => 'issued'])
            ->andWhere(['between', 'issued_on', $from . ' 00:00:00', $to . ' 23:59:59'])
            ->select(['COUNT(*) AS invoices', 'COALESCE(SUM(taxable),0) AS taxable', 'COALESCE(SUM(cgst+sgst+igst),0) AS tax', 'COALESCE(SUM(total),0) AS total'])->one();

        return [
            'module' => (int) $module,
            'title' => ServiceModules::title($module),
            'database' => (string) \Yii::$app->db->createCommand('select database()')->queryScalar(),
            'period' => ['from' => $from, 'to' => $to],
            'today' => [
                'rides' => (int) $rides($today, $today)->count(),
                'completed' => (int) $rides($today, $today)->andWhere(['status' => $doneStatus])->count(),
                'waiting' => (int) (new Query())->from($rideTable)->where(['status' => 1])->count(),
            ],
            'rides_in_period' => (int) $rides($from, $to)->count(),
            'money' => $money($from, $to),
            'partners' => ['total' => (int) $partners, 'online' => (int) $online],
            'partner_wallets' => ['credit' => round($credit, 2), 'debit' => round($debit, 2), 'held' => round($credit - $debit, 2)],
            'invoices' => ['count' => (int) $inv['invoices'], 'taxable' => round($inv['taxable'], 2), 'tax' => round($inv['tax'], 2), 'total' => round($inv['total'], 2)],
        ];
    }
    // [doc:end] summary
}
