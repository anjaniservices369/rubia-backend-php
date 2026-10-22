<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[DriverSubscription]].
 *
 * @see DriverSubscription
 */
class DriverSubscriptionQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return DriverSubscription[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return DriverSubscription|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
