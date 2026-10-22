<?php

namespace app\modules\support\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\support\models\Category;

/**
 * CategorySearch represents the model behind the search form of `app\modules\support\models\Category`.
 */
class CategorySearch extends Category
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'state_id', 'type_id', 'create_user_id'], 'integer'],
            [['parent_id', 'title', 'created_on', 'updated_on'], 'safe'],
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
        $query = Category::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'state_id' => $this->state_id,
            'type_id' => $this->type_id,
            'created_on' => $this->created_on,
            'updated_on' => $this->updated_on,
            'create_user_id' => $this->create_user_id,
        ]);

        $query->andFilterWhere(['like', 'parent_id', $this->parent_id])
            ->andFilterWhere(['like', 'title', $this->title]);

        return $dataProvider;
    }
}
