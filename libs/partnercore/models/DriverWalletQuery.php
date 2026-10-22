<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[DriverWallet]].
 *
 * @see DriverWallet
 */
class DriverWalletQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return DriverWallet[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return DriverWallet|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
