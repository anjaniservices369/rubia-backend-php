<?php

namespace app\modules\admin\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\admin\models\Page;

/**
 * app\modules\admin\models\search\PageSearch represents the model behind the search form about `app\modules\admin\models\Page`.
 */
 class PageSearch extends Page
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'state_id', 'type_id', 'create_user_id', 'updated_user_id'], 'integer'],
            [['title', 'slug', 'description', 'created_date', 'updated_date'], 'safe'],
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
        $query = Page::find();

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
            'state_id' => $this->state_id,
            'type_id' => $this->type_id,
            'created_date' => $this->created_date,
            'updated_date' => $this->updated_date,
            'create_user_id' => $this->create_user_id,
            'updated_user_id' => $this->updated_user_id,
        ]);

        $query->andFilterWhere(['like', 'title', $this->title])
            ->andFilterWhere(['like', 'slug', $this->slug])
            ->andFilterWhere(['like', 'description', $this->description]);

        return $dataProvider;
    }
}
