<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[RideCompletionLog]].
 *
 * @see RideCompletionLog
 */
class RideCompletionLogQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return RideCompletionLog[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return RideCompletionLog|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
