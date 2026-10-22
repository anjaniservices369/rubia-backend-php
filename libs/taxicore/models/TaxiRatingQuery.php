<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[TaxiRating]].
 *
 * @see TaxiRating
 */
class TaxiRatingQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return TaxiRating[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return TaxiRating|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
