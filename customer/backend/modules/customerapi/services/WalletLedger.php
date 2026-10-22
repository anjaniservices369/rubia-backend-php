<?php

namespace app\modules\customerapi\services;

use app\modules\admin\models\UserWallet;
use app\modules\billing\services\CustomerWallet;
use Yii;

/**
 * The customer's wallet: ONE wallet per customer, owned by the customer app (this service), whatever service the money
 * was spent in. A service never keeps a balance of its own for a customer; it asks the gateway (CustomerClient::wallet*),
 * which ends here. Each entry only records which service it happened in (`module`), for that service's own statements.
 *
 * Every method answers ['ok' => bool, 'balance' => float, 'error' => string|null].
 */
class WalletLedger
{
    // [doc:start] balance - Balance
    public static function balance($userId)
    {
        if (!CustomerWallet::enabled()) {
            return self::reply(false, 0.0, 'Wallet is not available');
        }

        return self::reply(true, (float) UserWallet::availableBalance((int) $userId));
    }
    // [doc:end] balance

    // [doc:start] debit - Takes money out of the customer's wallet for something bought in a service. Refused when the balance is too low.
    /**
     * Takes money out of the customer's wallet for something bought in a service. Refused when the balance is too low.
     * A second call with the same $ref (per customer) answers the first one's result, so a retry never charges twice.
     */
    public static function debit($userId, $amount, $module, $reason, $ref = '')
    {
        return self::move((int) $userId, (float) $amount, (int) $module, (string) $reason, (string) $ref, UserWallet::payment_type_debit, UserWallet::type_ride_booking);
    }
    // [doc:end] debit

    // [doc:start] credit - Credit
    public static function credit($userId, $amount, $module, $reason, $ref = '')
    {
        return self::move((int) $userId, (float) $amount, (int) $module, (string) $reason, (string) $ref, UserWallet::payment_type_credit, UserWallet::type_wallet_recharge);
    }
    // [doc:end] credit

    private static function move($userId, $amount, $module, $reason, $ref, $direction, $type)
    {
        if (!CustomerWallet::enabled()) {
            return self::reply(false, 0.0, 'Wallet is not available');
        }
        if ($userId <= 0 || $amount <= 0) {
            return self::reply(false, (float) UserWallet::availableBalance($userId), 'A customer and a positive amount are required');
        }
        $marker = $ref !== '' ? ' [ref ' . $module . ':' . $ref . ']' : '';
        $text = mb_substr($reason, 0, 255 - mb_strlen($marker)) . $marker;
        $tx = Yii::$app->db->beginTransaction();
        try {
            if ($marker !== '' && UserWallet::find()->where(['user_id' => $userId, 'payment_type' => $direction])->andWhere(['like', 'method_reason', $marker, false])->exists()) {
                $tx->rollBack();

                return self::reply(true, (float) UserWallet::availableBalance($userId), null);   // already done: same answer
            }
            $balance = (float) UserWallet::availableBalance($userId);
            if ($direction === UserWallet::payment_type_debit && $balance + 0.0001 < $amount) {
                $tx->rollBack();

                return self::reply(false, $balance, 'Low wallet amount');
            }
            $entry = new UserWallet();
            $entry->user_id = $userId;
            $entry->amount = $amount;
            $entry->payment_type = $direction;
            $entry->type = $type;
            $entry->status = UserWallet::STATUS_SUCCESS;
            $entry->module = $module > 0 ? $module : null;
            $entry->method_reason = $text;
            if (!$entry->save(false)) {
                throw new \RuntimeException('The wallet entry was not saved');
            }
            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            Yii::error('wallet entry failed: ' . $e->getMessage(), 'api');

            return self::reply(false, (float) UserWallet::availableBalance($userId), 'The wallet could not be changed');
        }

        return self::reply(true, (float) UserWallet::availableBalance($userId), null);
    }

    private static function reply($ok, $balance, $error = null)
    {
        return ['ok' => (bool) $ok, 'balance' => round((float) $balance, 2), 'error' => $error];
    }
}
