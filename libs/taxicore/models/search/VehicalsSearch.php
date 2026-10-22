<?php

namespace app\modules\admin\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\admin\models\Vehicals;

/**
 * Search model of the admin "Vehicals" list (Bike, Auto, Taxi, ...).
 */
class VehicalsSearch extends Vehicals
{
    public function rules()
    {
        return [
            [['id', 'no_of_person', 'vehicle_type', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['title', 'image', 'admin_commison_type', 'created_on', 'updated_on'], 'safe'],
            [['base_fare', 'min_distance', 'fare_per_distance', 'waiting_time_limit', 'charges_per_minute',
                'fare_per_minute', 'cancellation_fee', 'admin_commison'], 'number'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Vehicals::find();
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['id' => SORT_ASC]],
        ]);

        $this->load($params);
        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'vehicle_type' => $this->vehicle_type,
            'no_of_person' => $this->no_of_person,
            'status' => $this->status,
            'create_user_id' => $this->create_user_id,
            'update_user_id' => $this->update_user_id,
        ]);
        $query->andFilterWhere(['like', 'title', $this->title])
            ->andFilterWhere(['like', 'admin_commison_type', $this->admin_commison_type]);

        return $dataProvider;
    }

    /**
     * Manager variant. Vehicles are not city specific, so it lists the same rows as search().
     */
    public function managersearch($params)
    {
        return $this->search($params);
    }
}