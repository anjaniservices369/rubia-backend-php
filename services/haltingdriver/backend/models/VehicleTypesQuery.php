<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[VehicleTypes]].
 *
 * @see VehicleTypes
 */
class VehicleTypesQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return VehicleTypes[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return VehicleTypes|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
