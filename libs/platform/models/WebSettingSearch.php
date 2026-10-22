<?php

namespace app\modules\admin\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\admin\models\WebSetting;

/**
 * app\modules\admin\models\WebSettingSearch represents the model behind the search form about `app\modules\admin\models\WebSetting`.
 */
 class WebSettingSearch extends WebSetting
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'type_id'], 'integer'],
            [['name', 'setting_key', 'value', 'status', 'created_on', 'updated_on'], 'safe'],
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
        $query = WebSetting::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'type_id' => $this->type_id,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'setting_key', $this->setting_key])
            ->andFilterWhere(['like', 'value', $this->value])
            ->andFilterWhere(['like', 'status', $this->status])
            ->andFilterWhere(['like', 'created_on', $this->created_on])
            ->andFilterWhere(['like', 'updated_on', $this->updated_on]);

        return $dataProvider;
    }
}
