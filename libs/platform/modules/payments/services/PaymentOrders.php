<?php

namespace app\modules\payments\services;

use app\models\User;
use app\modules\admin\models\WebSetting;
use Yii;

/**
 * What a partner pays through Razorpay: the joining fee, a wallet top-up, a subscription plan.
 *
 * Every payment is one row of payment_order:
 *   1. create()  - the server decides the amount (the app never sends the price of the joining fee or of a plan), creates the
 *                  order at Razorpay and keeps a row with status "created".
 *   2. confirm() - the app sends back what Razorpay gave it; the signature is checked, the payment is fetched from Razorpay
 *                  and must be captured, for this order and for the same amount.
 *   3. markPaid() - the first of confirm() and the webhook that arrives flips the row to "paid" and applies it (marks the
 *                  joining fee paid, credits the wallet, starts the subscription). A second arrival changes nothing, so the
 *                  money is never credited twice.
 */
class PaymentOrders
{
    const JOINING_FEE = 'joining_fee';
    const WALLET_TOPUP = 'wallet_topup';
    const SUBSCRIPTION = 'subscription';

    const MIN_TOPUP = 1;
    const MAX_TOPUP = 50000;

    // [doc:start] joiningFee - Joining Fee
    public static function joiningFee()
    {
        return max(0.0, (float) (new WebSetting())->getSettingBykey('driver_joining_fee'));
    }
    // [doc:end] joiningFee

    /**
     * @return float|string the amount, or an error text
     */
    private static function amountFor($purpose, $requested)
    {
        if ($purpose === self::JOINING_FEE) {
            $fee = self::joiningFee();

            return $fee > 0 ? $fee : 'There is no joining fee to pay.';
        }
        if ($purpose === self::WALLET_TOPUP) {
            $a = round((float) $requested, 2);
            if ($a < self::MIN_TOPUP || $a > self::MAX_TOPUP) {
                return 'The amount must be between ' . self::MIN_TOPUP . ' and ' . self::MAX_TOPUP . ' rupees.';
            }

            return $a;
        }
        if ($purpose === self::SUBSCRIPTION && class_exists('\\app\\modules\\admin\\models\\Plans')) {
            $plan = \app\modules\admin\models\Plans::find()->where(['status' => \app\modules\admin\models\Plans::STATUS_ACTIVE])->one();

            return $plan ? (float) $plan->price : 'There is no plan to subscribe to.';
        }

        return 'Unknown payment purpose.';
    }

    // [doc:start] create - Create
    /**
     * @return array ['ok' => bool, 'error' => string, 'checkout' => array] checkout = key_id, order_id, amount (paise), currency, name, description, prefill
     */
    public static function create($userId, $purpose, $requestedAmount = 0)
    {
        $user = User::findOne((int) $userId);
        if (!$user) {
            return ['ok' => false, 'error' => 'User not found'];
        }
        $amount = self::amountFor($purpose, $requestedAmount);
        if (!is_float($amount)) {
            return ['ok' => false, 'error' => $amount];
        }
        if ($purpose === self::JOINING_FEE && (int) $user->is_paid_joining_fee === 1) {
            return ['ok' => false, 'error' => 'The joining fee is already paid.'];
        }
        try {
            $receipt = $purpose . '-' . (int) $userId . '-' . time();
            $order = Razorpay::createOrder($amount, $receipt, ['user_id' => (string) $userId, 'purpose' => $purpose]);
        } catch (\RuntimeException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
        Yii::$app->db->createCommand()->insert('payment_order', [
            'user_id' => (int) $userId,
            'purpose' => $purpose,
            'amount' => $amount,
            'currency' => 'INR',
            'gateway' => 'razorpay',
            'gateway_order_id' => $order['id'],
            'status' => 'created',
            'applied' => 0,
            'created_on' => date('Y-m-d H:i:s'),
        ])->execute();

        $labels = [self::JOINING_FEE => 'Joining fee', self::WALLET_TOPUP => 'Wallet top-up', self::SUBSCRIPTION => 'Subscription'];

        return ['ok' => true, 'checkout' => [
            'key_id' => Razorpay::setting('key_id'),
            'order_id' => $order['id'],
            'amount' => (int) round($amount * 100),
            'currency' => 'INR',
            'name' => 'Rubia Services',
            'description' => $labels[$purpose],
            'purpose' => $purpose,
            'prefill' => ['contact' => (string) $user->contact_no, 'email' => (string) $user->email, 'name' => trim($user->first_name . ' ' . $user->last_name)],
        ]];
    }
    // [doc:end] create

    // [doc:start] confirm - Confirm
    /**
     * @param string|null $expectPurpose when given, the order must be for this purpose (the legacy endpoints pass theirs)
     * @return array ['ok' => bool, 'error' => string, 'purpose' => string, 'already' => bool]
     */
    public static function confirm($userId, $orderId, $paymentId, $signature, $expectPurpose = null)
    {
        $row = self::row('gateway_order_id', (string) $orderId);
        if (!$row || (int) $row['user_id'] !== (int) $userId) {
            return ['ok' => false, 'error' => 'Payment order not found.'];
        }
        if ($expectPurpose !== null && $row['purpose'] !== $expectPurpose) {
            return ['ok' => false, 'error' => 'This payment is not for that purpose.'];
        }
        if ($row['status'] === 'paid') {
            return ['ok' => true, 'purpose' => $row['purpose'], 'already' => true];
        }
        if (!Razorpay::verifyCheckoutSignature((string) $orderId, (string) $paymentId, (string) $signature)) {
            return ['ok' => false, 'error' => 'The payment signature is not valid.'];
        }
        try {
            $p = Razorpay::fetchPayment((string) $paymentId);
            if (($p['status'] ?? '') === 'authorized') {
                $p = Razorpay::capturePayment((string) $paymentId, (int) $p['amount']);
            }
        } catch (\RuntimeException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
        if (($p['order_id'] ?? '') !== $orderId || ($p['status'] ?? '') !== 'captured'
            || (int) ($p['amount'] ?? 0) !== (int) round($row['amount'] * 100)) {
            return ['ok' => false, 'error' => 'The payment was not completed.'];
        }
        $done = self::markPaid($row, (string) $paymentId);

        return ['ok' => true, 'purpose' => $row['purpose'], 'already' => !$done];
    }
    // [doc:end] confirm

    // [doc:start] createCashfree - Create Cashfree
    /**
     * The same as create(), for Cashfree: the server decides the amount, creates the Cashfree order and keeps a payment_order row
     * (gateway "cashfree"). The app opens Cashfree's checkout with order_id and payment_session_id.
     *
     * @return array ['ok' => bool, 'error' => string, 'checkout' => array] checkout = order_id, payment_session_id, mode, amount, currency
     */
    public static function createCashfree($userId, $purpose, $requestedAmount = 0)
    {
        $user = User::findOne((int) $userId);
        if (!$user) {
            return ['ok' => false, 'error' => 'User not found'];
        }
        $amount = self::amountFor($purpose, $requestedAmount);
        if (!is_float($amount)) {
            return ['ok' => false, 'error' => $amount];
        }
        if ($purpose === self::JOINING_FEE && (int) $user->is_paid_joining_fee === 1) {
            return ['ok' => false, 'error' => 'The joining fee is already paid.'];
        }
        $orderId = 'cf_' . $purpose . '_' . (int) $userId . '_' . time();
        $reply = (new CashFree())->CreateOrders([
            'name' => 'partner' . (int) $userId,
            'email' => (string) ($user->email ?: 'partner' . (int) $userId . '@example.invalid'),
            'customer_phone' => (string) $user->contact_no,
            'order_id' => $orderId,
            'order_amount' => number_format($amount, 2, '.', ''),
        ]);
        $order = json_decode((string) $reply, true);
        if (!is_array($order) || empty($order['payment_session_id'])) {
            Yii::error('Cashfree order not created: ' . (string) $reply, 'payments');

            return ['ok' => false, 'error' => 'Cashfree could not start the payment' . (is_array($order) && !empty($order['message']) ? ': ' . $order['message'] : '.')];
        }
        Yii::$app->db->createCommand()->insert('payment_order', [
            'user_id' => (int) $userId,
            'purpose' => $purpose,
            'amount' => $amount,
            'currency' => 'INR',
            'gateway' => 'cashfree',
            'gateway_order_id' => $orderId,
            'status' => 'created',
            'applied' => 0,
            'created_on' => date('Y-m-d H:i:s'),
        ])->execute();

        return ['ok' => true, 'checkout' => [
            'order_id' => $orderId,
            'payment_session_id' => $order['payment_session_id'],
            'mode' => CashFree::cred('cashfree_pg_mode') === 'production' ? 'production' : 'sandbox',
            'amount' => $amount,
            'currency' => 'INR',
            'purpose' => $purpose,
        ]];
    }
    // [doc:end] createCashfree

    // [doc:start] confirmCashfree - Confirm Cashfree
    /**
     * The app says the Cashfree checkout finished. Nothing the app sends is trusted: the order is read back from Cashfree and must be
     * PAID for this exact amount; then the first caller applies it (see markPaid()).
     *
     * @return array ['ok' => bool, 'error' => string, 'purpose' => string, 'already' => bool]
     */
    public static function confirmCashfree($userId, $orderId, $expectPurpose = null)
    {
        $row = self::row('gateway_order_id', (string) $orderId, 'cashfree');
        if (!$row || (int) $row['user_id'] !== (int) $userId) {
            return ['ok' => false, 'error' => 'Payment order not found.'];
        }
        if ($expectPurpose !== null && $row['purpose'] !== $expectPurpose) {
            return ['ok' => false, 'error' => 'This payment is not for that purpose.'];
        }
        if ($row['status'] === 'paid') {
            return ['ok' => true, 'purpose' => $row['purpose'], 'already' => true];
        }
        $o = json_decode((string) (new CashFree())->verifyPayment((string) $orderId), true);
        if (!is_array($o) || ($o['order_status'] ?? '') !== 'PAID') {
            return ['ok' => false, 'error' => 'The payment was not completed.'];
        }
        if (abs((float) ($o['order_amount'] ?? 0) - (float) $row['amount']) > 0.001) {
            return ['ok' => false, 'error' => 'The paid amount does not match the order.'];
        }
        $done = self::markPaid($row, (string) ($o['cf_order_id'] ?? $orderId));

        return ['ok' => true, 'purpose' => $row['purpose'], 'already' => !$done];
    }
    // [doc:end] confirmCashfree

    // [doc:start] fromWebhook - From Webhook
    /**
     * @return string what was done: paid | failed | ignored
     */
    public static function fromWebhook(array $event)
    {
        $type = $event['event'] ?? '';
        if ($type === 'qr_code.credited' || $type === 'qr_code.closed') {
            // The QR code was paid (or closed): ask Razorpay, do not trust the body, then apply once.
            $qrId = $event['payload']['qr_code']['entity']['id'] ?? '';
            $qrRow = $qrId !== '' ? self::row('gateway_order_id', (string) $qrId, 'razorpay_qr') : null;
            if ($qrRow && $qrRow['status'] !== 'paid') {
                $r = self::checkQr($qrRow);

                return $r === 'paid' ? 'paid' : 'ignored';
            }

            return 'ignored';
        }
        $pay = $event['payload']['payment']['entity'] ?? null;
        if (!$pay || empty($pay['order_id'])) {
            return 'ignored';
        }
        $row = self::row('gateway_order_id', (string) $pay['order_id']);
        if (!$row) {
            return 'ignored';
        }
        if (in_array($type, ['payment.captured', 'order.paid'], true) && ($pay['status'] ?? '') === 'captured'
            && (int) ($pay['amount'] ?? 0) === (int) round($row['amount'] * 100)) {
            self::markPaid($row, (string) $pay['id']);

            return 'paid';
        }
        if ($type === 'payment.failed' && $row['status'] === 'created') {
            Yii::$app->db->createCommand()->update('payment_order', ['status' => 'failed', 'note' => substr((string) ($pay['error_description'] ?? ''), 0, 250)],
                ['id' => $row['id'], 'status' => 'created'])->execute();

            return 'failed';
        }

        return 'ignored';
    }
    // [doc:end] fromWebhook

    // [doc:start] options - Options
    /**
     * @param string|null $purpose when given, the reply also carries the amount the server will charge for it
     * @return array ['amount' => float|null, 'checkout' => bool, 'qr' => bool, 'manual' => ['enabled' => bool, 'upi_id' => string, 'name' => string]]
     */
    public static function options($purpose = null, $requestedAmount = 0)
    {
        $upi = trim((string) (new WebSetting())->getSettingBykey('company_upi_id'));

        $amount = $purpose !== null ? self::amountFor($purpose, $requestedAmount) : null;

        return [
            'amount' => is_float($amount) ? $amount : null,
            'checkout' => Razorpay::isConfigured(),
            'qr' => Razorpay::isConfigured(),
            'manual' => ['enabled' => $upi !== '', 'upi_id' => $upi, 'name' => trim((string) (new WebSetting())->getSettingBykey('company_upi_name')) ?: 'Rubia Services'],
        ];
    }
    // [doc:end] options

    // [doc:start] createQr - Starts the automated QR payment: a Razorpay UPI QR for the same server-decided amount, kept as a payment_order row.
    /**
     * Starts the automated QR payment: a Razorpay UPI QR for the same server-decided amount, kept as a payment_order row
     * (gateway razorpay_qr). The app shows the image and asks qrStatus() every few seconds; when the driver pays with any UPI app the
     * payment is confirmed and applied without anything else to do.
     *
     * @return array ['ok' => bool, 'error' => string, 'qr' => array] qr = id, image_url, amount (rupees), expires_at
     */
    public static function createQr($userId, $purpose, $requestedAmount = 0)
    {
        $user = User::findOne((int) $userId);
        if (!$user) {
            return ['ok' => false, 'error' => 'User not found'];
        }
        $amount = self::amountFor($purpose, $requestedAmount);
        if (!is_float($amount)) {
            return ['ok' => false, 'error' => $amount];
        }
        if ($purpose === self::JOINING_FEE && (int) $user->is_paid_joining_fee === 1) {
            return ['ok' => false, 'error' => 'The joining fee is already paid.'];
        }
        try {
            $qr = Razorpay::createQr($amount, ucfirst(str_replace('_', ' ', $purpose)) . ' ' . (int) $userId, ['user_id' => (string) $userId, 'purpose' => $purpose]);
        } catch (\RuntimeException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
        Yii::$app->db->createCommand()->insert('payment_order', [
            'user_id' => (int) $userId,
            'purpose' => $purpose,
            'amount' => $amount,
            'currency' => 'INR',
            'gateway' => 'razorpay_qr',
            'gateway_order_id' => $qr['id'],
            'status' => 'created',
            'applied' => 0,
            'created_on' => date('Y-m-d H:i:s'),
        ])->execute();

        return ['ok' => true, 'qr' => [
            'id' => $qr['id'],
            'image_url' => $qr['image_url'] ?? '',
            'amount' => $amount,
            'expires_at' => (int) ($qr['close_by'] ?? (time() + 7200)),
            'purpose' => $purpose,
        ]];
    }
    // [doc:end] createQr

    // [doc:start] qrStatus - Qr Status
    /**
     * @return array ['ok' => bool, 'status' => waiting|paid|expired, 'purpose' => string]
     */
    public static function qrStatus($userId, $qrId)
    {
        $row = self::row('gateway_order_id', (string) $qrId, 'razorpay_qr');
        if (!$row || (int) $row['user_id'] !== (int) $userId) {
            return ['ok' => false, 'error' => 'Payment not found.'];
        }
        $state = $row['status'] === 'paid' ? 'paid' : self::checkQr($row);

        return ['ok' => true, 'status' => $state, 'purpose' => $row['purpose']];
    }
    // [doc:end] qrStatus

    /**
     * Asks Razorpay for the payments made to the QR and applies the first captured one of the exact amount.
     *
     * @return string waiting | paid | expired
     */
    private static function checkQr(array $row)
    {
        try {
            $list = Razorpay::fetchQrPayments($row['gateway_order_id']);
        } catch (\RuntimeException $e) {
            return 'waiting';
        }
        foreach ($list['items'] ?? [] as $p) {
            if (($p['status'] ?? '') === 'captured' && (int) ($p['amount'] ?? 0) === (int) round($row['amount'] * 100)) {
                self::markPaid($row, (string) $p['id']);

                return 'paid';
            }
        }

        return strtotime($row['created_on']) + 7200 < time() ? 'expired' : 'waiting';
    }

    // [doc:start] manualSubmit - The last resort when the gateway is down: the driver paid the company UPI id and sends the UTR (bank reference) number.
    /**
     * The last resort when the gateway is down: the driver paid the company UPI id and sends the UTR (bank reference) number.
     * Nothing is credited yet: the payment waits as "review" in the dashboard (Payments screen) until the company checks its bank
     * statement and approves it with one tap.
     *
     * @return array ['ok' => bool, 'error' => string, 'id' => int]
     */
    public static function manualSubmit($userId, $purpose, $requestedAmount, $utr)
    {
        $utr = strtoupper(trim((string) $utr));
        if (!preg_match('/^[A-Z0-9]{10,30}$/', $utr)) {
            return ['ok' => false, 'error' => 'Enter the UTR / reference number of the payment (10 to 30 letters and digits).'];
        }
        if (!self::options()['manual']['enabled']) {
            return ['ok' => false, 'error' => 'Paying by UPI QR is not set up yet.'];
        }
        $user = User::findOne((int) $userId);
        $amount = self::amountFor($purpose, $requestedAmount);
        if (!$user || !is_float($amount)) {
            return ['ok' => false, 'error' => $user ? $amount : 'User not found'];
        }
        if ($purpose === self::JOINING_FEE && (int) $user->is_paid_joining_fee === 1) {
            return ['ok' => false, 'error' => 'The joining fee is already paid.'];
        }
        if ((new \yii\db\Query())->from('payment_order')->where(['gateway' => 'manual', 'gateway_payment_id' => $utr])->exists()) {
            return ['ok' => false, 'error' => 'This reference number was already submitted.'];
        }
        Yii::$app->db->createCommand()->insert('payment_order', [
            'user_id' => (int) $userId,
            'purpose' => $purpose,
            'amount' => $amount,
            'currency' => 'INR',
            'gateway' => 'manual',
            'gateway_order_id' => 'M' . bin2hex(random_bytes(8)),
            'gateway_payment_id' => $utr,
            'status' => 'review',
            'applied' => 0,
            'note' => 'UTR ' . $utr,
            'created_on' => date('Y-m-d H:i:s'),
        ])->execute();

        return ['ok' => true, 'id' => (int) Yii::$app->db->getLastInsertID()];
    }
    // [doc:end] manualSubmit

    // [doc:start] manualDecision - Manual Decision
    /**
     * @return string approved | rejected | missing
     */
    public static function manualDecision($id, $approve)
    {
        $row = (new \yii\db\Query())->from('payment_order')->where(['id' => (int) $id, 'gateway' => 'manual', 'status' => 'review'])->one();
        if (!$row) {
            return 'missing';
        }
        if ($approve) {
            self::markPaid($row, $row['gateway_payment_id']);

            return 'approved';
        }
        Yii::$app->db->createCommand()->update('payment_order', ['status' => 'rejected'], ['id' => $row['id'], 'status' => 'review'])->execute();

        return 'rejected';
    }
    // [doc:end] manualDecision

    // [doc:start] recentlyPaid - True when this partner has a payment of this purpose that was paid in the last 30 minutes (already applied). Used by the.
    /**
     * True when this partner has a payment of this purpose that was paid in the last 30 minutes (already applied). Used by the
     * screens that call the "apply" endpoint after a QR payment, which the server has already applied.
     */
    public static function recentlyPaid($userId, $purpose)
    {
        return (new \yii\db\Query())->from('payment_order')->where(['user_id' => (int) $userId, 'purpose' => $purpose, 'status' => 'paid'])
            ->andWhere(['>=', 'paid_on', date('Y-m-d H:i:s', time() - 1800)])->exists();
    }
    // [doc:end] recentlyPaid

    // [doc:start] manualStatus - Manual Status
    public static function manualStatus($userId)
    {
        return (new \yii\db\Query())->select(['id', 'purpose', 'amount', 'status', 'created_on'])->from('payment_order')
            ->where(['user_id' => (int) $userId, 'gateway' => 'manual'])->orderBy(['id' => SORT_DESC])->limit(10)->all();
    }
    // [doc:end] manualStatus

    private static function row($column, $value, $gateway = 'razorpay')
    {
        return (new \yii\db\Query())->from('payment_order')->where([$column => $value, 'gateway' => $gateway])->one() ?: null;
    }

    private static function markPaid(array $row, $paymentId)
    {
        $db = Yii::$app->db;
        $tx = $db->beginTransaction();
        try {
            // Only the caller that changes the row from "not paid" applies the payment.
            $n = $db->createCommand('UPDATE payment_order SET status = :s, gateway_payment_id = :p, paid_on = :t, applied = 1 WHERE id = :id AND status <> :s2',
                [':s' => 'paid', ':s2' => 'paid', ':p' => $paymentId, ':t' => date('Y-m-d H:i:s'), ':id' => $row['id']])->execute();
            if ($n === 1) {
                self::apply($row);
            }
            $tx->commit();

            return $n === 1;
        } catch (\Throwable $e) {
            $tx->rollBack();
            Yii::error('payment_order ' . $row['id'] . ' could not be applied: ' . $e->getMessage(), 'payments');
            throw $e;
        }
    }

    private static function apply(array $row)
    {
        $uid = (int) $row['user_id'];
        if ($row['purpose'] === self::JOINING_FEE) {
            $u = User::findOne($uid);
            $u->is_paid_joining_fee = User::is_paid_joining_fee_yes;
            $u->paid_amount = $row['amount'];
            $u->save(false);
        } elseif ($row['purpose'] === self::WALLET_TOPUP) {
            $w = new \app\modules\admin\models\DriverWallet();
            $w->driver_id = $uid;
            $w->amount = (int) round($row['amount']);
            $w->payment_type = \app\modules\admin\models\DriverWallet::payment_type_credit;
            $w->method_reason = 'Amount credit ' . $row['amount'] . ' (' . ($row['gateway'] === 'manual' ? 'UPI, approved by the company' : ucfirst($row['gateway']) . ' ' . $row['gateway_order_id']) . ')';
            $w->status = \app\modules\admin\models\DriverWallet::STATUS_SUCCESS;
            $w->save(false);
        } elseif ($row['purpose'] === self::SUBSCRIPTION) {
            $plan = \app\modules\admin\models\Plans::find()->where(['status' => \app\modules\admin\models\Plans::STATUS_ACTIVE])->one();
            $sub = \app\modules\admin\models\DriverSubscription::find()->where(['driver_id' => $uid])->one() ?: new \app\modules\admin\models\DriverSubscription();
            $today = date('Y-m-d');
            $sub->driver_id = $uid;
            $sub->plan_id = $plan->id;
            // A plan that is still running is extended; an expired or new one starts today.
            if (!empty($sub->end_date) && $sub->end_date > $today && (int) $sub->status === \app\modules\admin\models\DriverSubscription::STATUS_ACTIVE) {
                $sub->end_date = date('Y-m-d', strtotime($sub->end_date . ' +' . (int) $plan->validity_in_days . ' days'));
            } else {
                $sub->start_date = $today;
                $sub->end_date = date('Y-m-d', strtotime('+' . (int) $plan->validity_in_days . ' days'));
            }
            // The payment was verified with Razorpay, so the plan starts at once (no manual approval needed).
            $sub->status = \app\modules\admin\models\DriverSubscription::STATUS_ACTIVE;
            $sub->save(false);
        }
    }
}
