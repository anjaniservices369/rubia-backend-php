<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[CityRateCard]].
 *
 * @see CityRateCard
 */
class CityRateCardQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return CityRateCard[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return CityRateCard|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
