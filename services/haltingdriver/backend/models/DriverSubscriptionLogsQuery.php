<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[DriverSubscriptionLogs]].
 *
 * @see DriverSubscriptionLogs
 */
class DriverSubscriptionLogsQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return DriverSubscriptionLogs[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return DriverSubscriptionLogs|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
