<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[CouponsApplied]].
 *
 * @see CouponsApplied
 */
class CouponsAppliedQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return CouponsApplied[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return CouponsApplied|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
