<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[RideStatuses]].
 *
 * @see RideStatuses
 */
class RideStatusesQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return RideStatuses[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return RideStatuses|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
