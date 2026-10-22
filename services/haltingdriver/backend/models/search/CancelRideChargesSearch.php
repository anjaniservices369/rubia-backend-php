<?php

namespace app\modules\admin\models\search;

use app\modules\admin\models\base\CancelRideCharges;
use yii\data\ActiveDataProvider;

/**
 * Search / filter of the cancellation charges screen (customers' and drivers' cancellations after a driver accepted).
 */
class CancelRideChargesSearch extends CancelRideCharges
{
    /** @var string first day of the period (Y-m-d) */
    public $from;
    /** @var string last day of the period (Y-m-d) */
    public $to;

    public function rules()
    {
        return [
            [['id', 'user_id', 'booking_id', 'driver_id'], 'integer'],
            [['cancel_role', 'status', 'from', 'to'], 'safe'],
        ];
    }

    public function scenarios()
    {
        return \yii\base\Model::scenarios();
    }

    // [doc:start] search - Search
    public function search($params)
    {
        $query = CancelRideCharges::find();
        $provider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
            'pagination' => ['pageSize' => 25],
        ]);
        $this->load($params);
        if (!$this->validate()) {
            $query->where('0=1');

            return $provider;
        }
        $query->andFilterWhere([
            'id' => $this->id, 'user_id' => $this->user_id, 'booking_id' => $this->booking_id, 'driver_id' => $this->driver_id,
            'cancel_role' => $this->cancel_role, 'status' => $this->status,
        ]);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $this->from)) {
            $query->andWhere(['>=', 'created_date', $this->from . ' 00:00:00']);
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $this->to)) {
            $query->andWhere(['<=', 'created_date', $this->to . ' 23:59:59']);
        }

        return $provider;
    }
    // [doc:end] search

    // [doc:start] totals - Count and money by status for the current filter.
    /**
     * Count and money by status for the current filter.
     *
     * @return array status => ['count' => int, 'charge' => float, 'company' => float]
     */
    public function totals($params)
    {
        $q = $this->search($params)->query;
        $rows = (clone $q)->select([
            'status',
            'n' => 'COUNT(*)',
            'charge' => 'COALESCE(SUM(CAST(cancel_charge AS DECIMAL(10,2))),0)',
            'company' => 'COALESCE(SUM(admin_cancellation_earnings),0)',
        ])->groupBy('status')->orderBy([])->asArray()->all();
        $out = [];
        foreach ($rows as $r) {
            $out[$r['status']] = ['count' => (int) $r['n'], 'charge' => round((float) $r['charge'], 2), 'company' => round((float) $r['company'], 2)];
        }

        return $out;
    }
    // [doc:end] totals
}
