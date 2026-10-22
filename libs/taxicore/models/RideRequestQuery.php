<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[RideRequest]].
 *
 * @see RideRequest
 */
class RideRequestQuery extends \yii\db\ActiveQuery
{
    /**
     * Inside a service-bound API module (bike / auto / car) only that service's rides are visible, whatever the
     * query: lists, lookups by id, counts. Outside such a module (admin, console) nothing is filtered.
     */
    public function prepare($builder)
    {
        $ids = \app\modules\geo\services\ServiceScope::vehicleIds();
        if ($ids !== null && !$this->scopeApplied) {
            $this->scopeApplied = true;
            $this->andWhere(['ride_request.vehical_type' => $ids ?: [0]]);
        }

        return parent::prepare($builder);
    }

    private $scopeApplied = false;

    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return RideRequest[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return RideRequest|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
