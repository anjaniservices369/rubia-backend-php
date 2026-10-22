<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[TaxiDriverRequest]].
 *
 * @see TaxiDriverRequest
 */
class TaxiDriverRequestQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return TaxiDriverRequest[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return TaxiDriverRequest|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
