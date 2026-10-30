<?php

define('YII_ENV', 'test');
define('YII_DEBUG', true);
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php';

use app\modules\admin\models\base\Booking;
use app\modules\admin\models\base\DriverRequests;
use app\modules\dispatch\services\BookingExpiry;

$dsn = getenv('DB_DSN');
if (!$dsn || !preg_match('/host=(127\.0\.0\.1|localhost)(;|$)/', $dsn)
    || !preg_match('/dbname=[^;]*(test|e2e)/i', $dsn)) {
    throw new RuntimeException('Set DB_DSN to an isolated local test/e2e database copy.');
}
$app = new yii\console\Application(require dirname(__DIR__) . '/config/console.php');
$push = new class extends yii\base\Component {
    public $messages = [];

    public function notifyUser($ref, $userId, $title, $body)
    {
        $this->messages[] = [$ref, $userId, $title, $body];
        return true;
    }
};
$app->set('push', $push);
$db = $app->db;
$engines = $db->createCommand("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('booking', 'driver_requests', 'web_setting')")->queryColumn();
if (count($engines) !== 3 || array_filter($engines, function ($engine) { return strcasecmp($engine, 'InnoDB') !== 0; })) {
    throw new RuntimeException('Fixture rollback requires InnoDB tables.');
}
$transaction = $db->beginTransaction();
$checks = 0;
$assert = function ($condition, $message) use (&$checks) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
};

try {
    $template = (new yii\db\Query())->from('booking')->one();
    if (!$template) {
        throw new RuntimeException('The test copy needs an existing booking as a fixture template.');
    }
    $db->createCommand()->delete('web_setting', ['setting_key' => 'booking_expiry_grace_minutes'])->execute();
    $db->createCommand()->insert('web_setting', ['setting_key' => 'booking_expiry_grace_minutes', 'value' => '0'])->execute();
    $fixture = function ($seconds, $status = Booking::STATUS_WAITING_FOR_DRIVER) use ($db, $template) {
        $row = $template;
        unset($row['id']);
        $row['status'] = $status;
        $row['driver_id'] = null;
        $row['booking_number'] = 'EXPIRY-TEST-' . bin2hex(random_bytes(6));
        $row['pickup_date'] = date('Y-m-d', time() + $seconds);
        $row['pickup_time'] = date('H:i:s', time() + $seconds);
        $db->createCommand()->insert('booking', $row)->execute();
        return (int) $db->lastInsertID;
    };
    $past = $fixture(-3600);
    $future = $fixture(3600);
    $accepted = $fixture(-3600, Booking::STATUS_DRIVER_ACCEPTED);
    $driverId = (new yii\db\Query())->select('id')->from('user')->where(['user_role' => 'Driver'])->scalar();
    if (!$driverId) {
        throw new RuntimeException('The test copy needs a driver.');
    }
    $db->createCommand()->insert('driver_requests', [
        'booking_id' => $past, 'driver_id' => $driverId, 'status' => DriverRequests::STATUS_WAITING_FOR_DRIVER,
    ])->execute();
    $scope = ['id' => [$past, $future, $accepted]];
    $assert(!Booking::find()->where(['id' => $past])->andWhere(BookingExpiry::availablePickupCondition())->exists(), 'Past pickup must not be offered.');
    $condition = BookingExpiry::availablePickupCondition();
    $cutoff = $condition[2][2];
    $boundary = $fixture(0);
    $db->createCommand()->update('booking', [
        'pickup_date' => substr($cutoff, 0, 10), 'pickup_time' => substr($cutoff, 11),
    ], ['id' => $boundary])->execute();
    $assert(Booking::find()->where(['id' => $boundary])->andWhere($condition)->exists(), 'Pickup equal to cutoff must remain available.');
    $before = date('Y-m-d H:i:s', strtotime($cutoff) - 1);
    $db->createCommand()->update('booking', [
        'pickup_date' => substr($before, 0, 10), 'pickup_time' => substr($before, 11),
    ], ['id' => $boundary])->execute();
    $assert(!Booking::find()->where(['id' => $boundary])->andWhere($condition)->exists(), 'One second after pickup must be unavailable.');
    $claimed = Booking::updateAll(['status' => Booking::STATUS_DRIVER_ACCEPTED], ['and',
        ['id' => $past, 'status' => Booking::STATUS_WAITING_FOR_DRIVER], BookingExpiry::availablePickupCondition(),
    ]);
    $assert($claimed === 0, 'Atomic accept must refuse past pickup without running expiry first.');
    $result = BookingExpiry::run(200, $scope);
    $assert($result === ['cancelled' => 1, 'notified' => 1], 'Only overdue unanswered booking should expire.');
    $assert((int) Booking::findOne($past)->status === Booking::STATUS_BOOKING_AUTO_CANCELLED, 'Past booking must be auto-cancelled.');
    $assert((int) DriverRequests::find()->where(['booking_id' => $past])->one()->status === DriverRequests::STATUS_BOOKING_AUTO_CANCELLED, 'Offer must be closed.');
    $assert((int) Booking::findOne($future)->status === Booking::STATUS_WAITING_FOR_DRIVER, 'Future booking must remain waiting.');
    $assert((int) Booking::findOne($accepted)->status === Booking::STATUS_DRIVER_ACCEPTED, 'Accepted past pickup must remain accepted.');
    $assert(BookingExpiry::run(200, $scope) === ['cancelled' => 0, 'notified' => 0], 'Expiry must be idempotent.');
    $assert(count($push->messages) === 1, 'Customer must be notified once.');
    $claimed = Booking::updateAll(['status' => Booking::STATUS_DRIVER_ACCEPTED], ['and',
        ['id' => $future, 'status' => Booking::STATUS_WAITING_FOR_DRIVER], BookingExpiry::availablePickupCondition(),
    ]);
    $assert($claimed === 1, 'Atomic accept must still allow future pickup.');
    $recent = $fixture(-60);
    $db->createCommand()->update('web_setting', ['value' => '5'], ['setting_key' => 'booking_expiry_grace_minutes'])->execute();
    $assert(BookingExpiry::run(1, ['id' => $recent])['cancelled'] === 0, 'Configured grace must be respected.');
    $db->createCommand()->update('web_setting', ['value' => '0'], ['setting_key' => 'booking_expiry_grace_minutes'])->execute();
    $assert(BookingExpiry::run(1, ['id' => $recent])['cancelled'] === 1, 'Zero grace must expire a recently past pickup.');
    $backlog = [];
    for ($i = 0; $i < 201; $i++) {
        $backlog[] = $fixture(-3600);
    }
    $assert(BookingExpiry::run(200, ['id' => $backlog])['cancelled'] === 200, 'Cleanup must respect its 200-row limit.');
    $assert(!Booking::find()->where(['id' => $backlog, 'status' => Booking::STATUS_WAITING_FOR_DRIVER])
        ->andWhere(BookingExpiry::availablePickupCondition())->exists(), 'Unprocessed expired backlog must not leak into available lists.');
    echo "PASS: $checks booking expiry regression checks.\n";
} finally {
    $transaction->rollBack();
}
