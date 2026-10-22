<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[BookingLogs]].
 *
 * @see BookingLogs
 */
class BookingLogsQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return BookingLogs[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return BookingLogs|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
