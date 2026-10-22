<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[FcmNotification]].
 *
 * @see FcmNotification
 */
class FcmNotificationQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return FcmNotification[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return FcmNotification|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
