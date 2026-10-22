<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[DriverPanCards]].
 *
 * @see DriverPanCards
 */
class DriverPanCardsQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return DriverPanCards[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return DriverPanCards|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
