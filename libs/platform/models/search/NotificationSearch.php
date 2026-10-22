<?php

namespace app\modules\admin\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\admin\models\Notification;

/**
 * app\modules\admin\models\search\NotificationSearch represents the model behind the search form about `app\modules\admin\models\Notification`.
 */
 class NotificationSearch extends Notification
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'order_id', 'user_id', 'check_on_ajax', 'create_user_id', 'update_user_id'], 'integer'],
            [['title', 'module', 'icon', 'mark_read', 'model_type', 'status', 'created_on', 'updated_on'], 'safe'],
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
        $query = Notification::find();

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
            'order_id' => $this->order_id,
            'user_id' => $this->user_id,
            'check_on_ajax' => $this->check_on_ajax,
            'created_on' => $this->created_on,
            'updated_on' => $this->updated_on,
            'create_user_id' => $this->create_user_id,
            'update_user_id' => $this->update_user_id,
        ]);

        $query->andFilterWhere(['like', 'title', $this->title])
            ->andFilterWhere(['like', 'module', $this->module])
            ->andFilterWhere(['like', 'icon', $this->icon])
            ->andFilterWhere(['like', 'mark_read', $this->mark_read])
            ->andFilterWhere(['like', 'model_type', $this->model_type])
            ->andFilterWhere(['like', 'status', $this->status]);

        return $dataProvider;
    }

    /**
     * Creates data provider instance with managersearch query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function managersearch($params)
    {
        $query = Notification::find();

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
            'order_id' => $this->order_id,
            'user_id' => $this->user_id,
            'check_on_ajax' => $this->check_on_ajax,
            'created_on' => $this->created_on,
            'updated_on' => $this->updated_on,
            'create_user_id' => $this->create_user_id,
            'update_user_id' => $this->update_user_id,
        ]);

        $query->andFilterWhere(['like', 'title', $this->title])
            ->andFilterWhere(['like', 'module', $this->module])
            ->andFilterWhere(['like', 'icon', $this->icon])
            ->andFilterWhere(['like', 'mark_read', $this->mark_read])
            ->andFilterWhere(['like', 'model_type', $this->model_type])
            ->andFilterWhere(['like', 'status', $this->status]);

        return $dataProvider;
    }
}
