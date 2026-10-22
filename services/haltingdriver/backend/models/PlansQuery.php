<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[Plans]].
 *
 * @see Plans
 */
class PlansQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return Plans[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Plans|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
