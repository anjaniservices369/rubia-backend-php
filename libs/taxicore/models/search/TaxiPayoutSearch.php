<?php

namespace app\modules\admin\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\admin\models\TaxiPayout;

/**
 * app\modules\admin\models\search\TaxiPayoutSearch represents the model behind the search form about `app\modules\admin\models\TaxiPayout`.
 */
 class TaxiPayoutSearch extends TaxiPayout
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'driver_id', 'payment_type', 'type_id', 'refrence_id',  'create_user_id', 'update_user_id'], 'integer'],
            [['created_on', 'updated_on'], 'safe'],
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
    public function search($params,$vehicle_id = "")
    {
        $query = TaxiPayout::find()->where(['vehicle_type'=>$vehicle_id]);

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
            'driver_id' => $this->driver_id,
            'amount' => $this->amount,
            'payment_type' => $this->payment_type,
            'type_id' => $this->type_id,
            'refrence_id' => $this->refrence_id,
            'status' => $this->status,
            'create_user_id' => $this->create_user_id,
            'update_user_id' => $this->update_user_id,
        ]);

        $query->andFilterWhere(['like', 'method_reason', $this->method_reason])
            ->andFilterWhere(['like', 'created_on', $this->created_on])
            ->andFilterWhere(['like', 'updated_on', $this->updated_on]);

        return $dataProvider;
    }

    public function withdrawrequestsearch($params,$status,$vehicle_id="")
    {
        if(!empty($status)){
            $query = TaxiPayout::find()->where(['is_withdraw_request'=>TaxiPayout::WITHDRAW_REQUEST])->andWhere(['status'=>$status])->andWhere(['vehicle_type'=>$vehicle_id]);

        }else{
            $query = TaxiPayout::find()->where(['is_withdraw_request'=>TaxiPayout::WITHDRAW_REQUEST])   ;
        }

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
            'driver_id' => $this->driver_id,
            'amount' => $this->amount,
            'payment_type' => $this->payment_type,
            'type_id' => $this->type_id,
            'refrence_id' => $this->refrence_id,
            'status' => $this->status,
            'create_user_id' => $this->create_user_id,
            'update_user_id' => $this->update_user_id,
        ]);

        $query->andFilterWhere(['like', 'method_reason', $this->method_reason])
            ->andFilterWhere(['like', 'created_on', $this->created_on])
            ->andFilterWhere(['like', 'updated_on', $this->updated_on]);

        return $dataProvider;
    }

    public function withdrawrequestsearchmanager($params,$status,$vehicle_id="")
    {
       
        $query = TaxiPayout::find()->where(['is_withdraw_request'=>TaxiPayout::WITHDRAW_REQUEST])->andWhere(['status'=>$status])->andWhere(['vehicle_type'=>$vehicle_id])->andWhere(['city_id'=>\Yii::$app->user->identity->city_id]);
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
            'driver_id' => $this->driver_id,
            'amount' => $this->amount,
            'payment_type' => $this->payment_type,
            'type_id' => $this->type_id,
            'refrence_id' => $this->refrence_id,
            'status' => $this->status,
            'create_user_id' => $this->create_user_id,
            'update_user_id' => $this->update_user_id,
        ]);

        $query->andFilterWhere(['like', 'method_reason', $this->method_reason])
            ->andFilterWhere(['like', 'created_on', $this->created_on])
            ->andFilterWhere(['like', 'updated_on', $this->updated_on]);

        return $dataProvider;
    }


    /**
     * Creates data provider instance with managersearch query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function managersearch($params,$vehicle_id="")
    {
        $query = TaxiPayout::find()
                     ->where(['city_id' => \Yii::$app->user->identity->city_id])
                     ->andWhere(['vehicle_type' => $vehicle_id]);

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
            'driver_id' => $this->driver_id,
            'amount' => $this->amount,
            'payment_type' => $this->payment_type,
            'type_id' => $this->type_id,
            'refrence_id' => $this->refrence_id,
            'status' => $this->status,
            'create_user_id' => $this->create_user_id,
            'update_user_id' => $this->update_user_id,
        ]);

        $query->andFilterWhere(['like', 'method_reason', $this->method_reason])
            ->andFilterWhere(['like', 'created_on', $this->created_on])
            ->andFilterWhere(['like', 'updated_on', $this->updated_on]);

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
