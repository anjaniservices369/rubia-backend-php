<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[UserBookingRatings]].
 *
 * @see UserBookingRatings
 */
class UserBookingRatingsQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return UserBookingRatings[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return UserBookingRatings|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
