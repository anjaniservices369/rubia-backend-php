<?php

namespace app\modules\admin\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\admin\models\AuthSession;

/**
 * app\modules\admin\models\AuthSessionSearch represents the model behind the search form about `app\modules\admin\models\AuthSession`.
 */
 class AuthSessionSearch extends AuthSession
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'type_id', 'create_user_id'], 'integer'],
            [['auth_code', 'device_token', 'created_on', 'updated_on'], 'safe'],
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
        $query = AuthSession::find();

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
            'create_user_id' => $this->create_user_id,
            'created_on' => $this->created_on,
            'updated_on' => $this->updated_on,
        ]);

        $query->andFilterWhere(['like', 'auth_code', $this->auth_code])
            ->andFilterWhere(['like', 'device_token', $this->device_token]);

        return $dataProvider;
    }
}
