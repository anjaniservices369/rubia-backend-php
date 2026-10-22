<?php

namespace app\modules\customerapp\services;

use yii\db\Query;

/**
 * Numbers of the customer (user) app on the database this process is connected to: the super admin dashboard shows them
 * next to the services (its own signed reporting endpoint when the customer service runs separately).
 */
class CustomerReport
{
    // [doc:start] summary - Summary
    /**
     * @return array customers, logins, notifications and address-book entries (counts only, no personal data)
     */
    public static function summary($from = null, $to = null)
    {
        $ok = function ($d) {
            return is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
        };
        $from = $ok($from) ? $from : date('Y-m-01');
        $to = $ok($to) ? $to : date('Y-m-d');
        $customers = (new Query())->from('user')->where(['user_role' => 'User']);
        $between = function ($col) use ($from, $to) {
            return ['between', $col, strtotime($from . ' 00:00:00'), strtotime($to . ' 23:59:59')];
        };

        return [
            'service' => 'customer',
            'title' => 'Customer app',
            'database' => (string) \Yii::$app->db->createCommand('select database()')->queryScalar(),
            'period' => ['from' => $from, 'to' => $to],
            'customers' => [
                'total' => (int) (clone $customers)->count(),
                'active' => (int) (clone $customers)->andWhere(['status' => 10])->count(),
                'blocked' => (int) (clone $customers)->andWhere(['status' => 0])->count(),
                'new_in_period' => (int) (clone $customers)->andWhere($between('created_at'))->count(),
            ],
            'logins' => (int) (new Query())->from('auth_session')->count(),
            'notifications' => (int) (new Query())->from('notification')->count(),
            'address_book' => [
                'favourite_locations' => (int) (new Query())->from('favourite_location')->count(),
                'emergency_contacts' => (int) (new Query())->from('user_emergency_contacts')->count(),
            ],
        ];
    }
    // [doc:end] summary
}
