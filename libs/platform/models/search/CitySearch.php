<?php

namespace app\modules\admin\models\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\admin\models\City;

/**
 * CitySearch represents the model behind the search form about `app\modules\admin\models\City`.
 */
class CitySearch extends City
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            // Define rules for your search attributes, if any
            [['name'], 'safe'],
            // ... other rules
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = City::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        // load the search form data and validate
        if (!$this->load($params) || !$this->validate()) {
            return $dataProvider;
        }

        // apply filters to the query based on the search form values
        $query->andFilterWhere(['like', 'name', $this->name]);
        // ... add more conditions if needed

        return $dataProvider;
    }
}
