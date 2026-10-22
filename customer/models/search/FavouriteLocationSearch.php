<?php

namespace app\modules\admin\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\admin\models\FavouriteLocation;

/**
 * app\modules\admin\models\search\FavouriteLocationSearch represents the model behind the search form about `app\modules\admin\models\FavouriteLocation`.
 */
 class FavouriteLocationSearch extends FavouriteLocation
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'user_id', 'pincode', 'status', 'update_user_id', 'create_user_id'], 'integer'],
            [['latitude', 'logitude', 'address_type', 'address', 'created_on', 'updated_on'], 'safe'],
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
        $query = FavouriteLocation::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'created_on' => SORT_DESC,
                ],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'user_id' => $this->user_id,
            'pincode' => $this->pincode,
            'status' => $this->status,
            'created_on' => $this->created_on,
            'updated_on' => $this->updated_on,
            'update_user_id' => $this->update_user_id,
            'create_user_id' => $this->create_user_id,
        ]);

        $query->andFilterWhere(['like', 'latitude', $this->latitude])
            ->andFilterWhere(['like', 'logitude', $this->logitude])
            ->andFilterWhere(['like', 'address_type', $this->address_type])
            ->andFilterWhere(['like', 'address', $this->address]);

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
        $query = FavouriteLocation::find()
                     ->where(['city_id' => \Yii::$app->user->identity->city_id])
        ;

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'created_on' => SORT_DESC,
                ],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'user_id' => $this->user_id,
            'pincode' => $this->pincode,
            'status' => $this->status,
            'created_on' => $this->created_on,
            'updated_on' => $this->updated_on,
            'update_user_id' => $this->update_user_id,
            'create_user_id' => $this->create_user_id,
        ]);

        $query->andFilterWhere(['like', 'latitude', $this->latitude])
            ->andFilterWhere(['like', 'logitude', $this->logitude])
            ->andFilterWhere(['like', 'address_type', $this->address_type])
            ->andFilterWhere(['like', 'address', $this->address]);

        if(isset ($this->created_on)&&$this->created_on!=''){ 
           
           //you dont need the if function if yourse sure you have a not null date
            $date_explode=explode(" - ",$this->created_on);
         //   var_dump($date_explode);exit;
            $date1=trim($date_explode[0]);
           $date2=trim($date_explode[1]);
           $query->andFilterWhere(['between','created_on',$date1,$date2]);
          // var_dump($query->createCommand()->getRawSql());exit;
          }
       if(isset ($this->updated_on)&&$this->updated_on!=''){ 
      
           //you dont need the if function if yourse sure you have a not null date
            $date_explode=explode(" - ",$this->updated_on);
         //   var_dump($date_explode);exit;
            $date1=trim($date_explode[0]);
           $date2=trim($date_explode[1]);
           $query->andFilterWhere(['between','updated_on',$date1,$date2]);
          //  var_dump($query->createCommand()->getRawSql());exit;
          }

        return $dataProvider;
    }
}
