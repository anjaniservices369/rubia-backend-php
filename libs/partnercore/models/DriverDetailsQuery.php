<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[DriverDetails]].
 *
 * @see DriverDetails
 */
class DriverDetailsQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return DriverDetails[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return DriverDetails|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
