<?php

namespace app\modules\admin\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\admin\models\WebSetting;

/**
 * app\modules\admin\models\search\WebSettingSearch represents the model behind the search form about `app\modules\admin\models\WebSetting`.
 */
 class WebSettingSearch extends WebSetting
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['setting_id', 'type_id', 'status', 'create_user_id', 'updated_user_id'], 'integer'],
            [['name', 'setting_key', 'value', 'created_date', 'updated_date'], 'safe'],
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
            'setting_id' => $this->setting_id,
            'type_id' => $this->type_id,
            'status' => $this->status,
            'created_date' => $this->created_date,
            'updated_date' => $this->updated_date,
            'create_user_id' => $this->create_user_id,
            'updated_user_id' => $this->updated_user_id,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'setting_key', $this->setting_key])
            ->andFilterWhere(['like', 'value', $this->value]);

        return $dataProvider;
    }
}
