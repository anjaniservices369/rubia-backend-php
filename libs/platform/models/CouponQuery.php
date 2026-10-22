<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[Coupon]].
 *
 * @see Coupon
 */
class CouponQuery extends \yii\db\ActiveQuery
{
    private $scopeApplied = false;

    /**
     * Coupons belong to one service. Where the request's service is known (the customer app is the driver service, a
     * bound taxi address is that service) only that service's coupons can be seen or applied.
     */
    public function prepare($builder)
    {
        $m = \app\modules\geo\services\ServiceScope::commercial();
        if ($m !== null && !$this->scopeApplied) {
            $this->scopeApplied = true;
            $this->andWhere(['coupon.module' => $m]);
        }

        return parent::prepare($builder);
    }

    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return Coupon[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Coupon|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
