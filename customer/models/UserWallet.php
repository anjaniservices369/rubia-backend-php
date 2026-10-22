<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\UserWallet as BaseUserWallet;

/**
 * This is the model class for table "user_wallet".
 */
class UserWallet extends BaseUserWallet
{

	

    /**
     * A customer's wallet entry belongs to one service: the one named in the request, else the bound taxi address,
     * else the driver service (the customer app).
     */
    public function beforeSave($insert)
    {
        if (empty($this->module)) {
            // ONE wallet per customer: `module` only records which service an entry happened in; an entry made in the
            // customer app itself (a recharge) belongs to no service
            $this->module = \app\modules\geo\services\ServiceModules::requested(0) ?: null;
        }

        return parent::beforeSave($insert);
    }
}
