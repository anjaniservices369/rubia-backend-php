<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[UserWallet]].
 *
 * @see UserWallet
 */
class UserWalletQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return UserWallet[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return UserWallet|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
