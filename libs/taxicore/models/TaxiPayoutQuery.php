<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[TaxiPayout]].
 *
 * @see TaxiPayout
 */
class TaxiPayoutQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return TaxiPayout[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return TaxiPayout|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
