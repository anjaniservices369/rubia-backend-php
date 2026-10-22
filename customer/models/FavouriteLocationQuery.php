<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[FavouriteLocation]].
 *
 * @see FavouriteLocation
 */
class FavouriteLocationQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return FavouriteLocation[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return FavouriteLocation|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
