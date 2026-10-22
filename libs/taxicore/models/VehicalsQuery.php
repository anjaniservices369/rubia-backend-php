<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[Vehicals]].
 *
 * @see Vehicals
 */
class VehicalsQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return Vehicals[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Vehicals|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
