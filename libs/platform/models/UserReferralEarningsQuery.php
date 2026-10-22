<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[UserReferralEarnings]].
 *
 * @see UserReferralEarnings
 */
class UserReferralEarningsQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return UserReferralEarnings[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return UserReferralEarnings|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
