<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[RideCommision]].
 *
 * @see RideCommision
 */
class RideCommisionQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return RideCommision[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return RideCommision|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
