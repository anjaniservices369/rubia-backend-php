<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[RideEarnings]].
 *
 * @see RideEarnings
 */
class RideEarningsQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return RideEarnings[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return RideEarnings|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
