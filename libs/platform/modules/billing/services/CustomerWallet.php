<?php

namespace app\modules\billing\services;

use Yii;

/**
 * Whether the customer wallet is available.
 *
 * Money flows customer -> partner (cash / online to the partner) and partner -> platform (commission from the partner
 * wallet), so only the partner wallet exists. The customer wallet is switched off; its code and old balances are kept.
 * Switch on with environment CUSTOMER_WALLET=on (or params['customerWallet'] = true).
 */
class CustomerWallet
{
    // [doc:start] enabled - Enabled
    public static function enabled()
    {
        $v = getenv('CUSTOMER_WALLET');
        if ($v !== false && $v !== '') {
            return in_array(strtolower($v), ['1', 'on', 'true', 'yes'], true);
        }

        return !empty(Yii::$app->params['customerWallet']);
    }
    // [doc:end] enabled

    // [doc:start] unavailable - Response body for an API that needs the customer wallet while it is off.
    /**
     * Response body for an API that needs the customer wallet while it is off.
     */
    public static function unavailable()
    {
        return ['status' => 'NOK', 'error' => 'Wallet is not available. Please pay the driver directly.', 'wallet_enabled' => false];
    }
    // [doc:end] unavailable
}
