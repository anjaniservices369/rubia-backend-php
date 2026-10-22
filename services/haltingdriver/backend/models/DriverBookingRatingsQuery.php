<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[DriverBookingRatings]].
 *
 * @see DriverBookingRatings
 */
class DriverBookingRatingsQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return DriverBookingRatings[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return DriverBookingRatings|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
