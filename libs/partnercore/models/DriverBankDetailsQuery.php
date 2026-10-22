<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[DriverBankDetails]].
 *
 * @see DriverBankDetails
 */
class DriverBankDetailsQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return DriverBankDetails[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return DriverBankDetails|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
