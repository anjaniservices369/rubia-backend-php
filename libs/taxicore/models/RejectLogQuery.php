<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[RejectLog]].
 *
 * @see RejectLog
 */
class RejectLogQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return RejectLog[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return RejectLog|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
