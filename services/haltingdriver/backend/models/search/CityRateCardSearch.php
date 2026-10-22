<?php

namespace app\modules\admin\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\admin\models\CityRateCard;

/**
 * app\modules\admin\models\search\CityRateCardSearch represents the model behind the search form about `app\modules\admin\models\CityRateCard`.
 */
 class CityRateCardSearch extends CityRateCard
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'city_id', 'trip_type', 'ride_type', 'base_hour', 'base_km', 'base_fare', 'extra_km_charge', 'extra_min_charge', 'night_charge', 'out_station_charge', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['facilities'], 'number'],
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
    public function search($params)
    {
        $query = CityRateCard::find();

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
            'city_id' => $this->city_id,
            'trip_type' => $this->trip_type,
            'ride_type' => $this->ride_type,
            'base_hour' => $this->base_hour,
            'base_km' => $this->base_km,
            'base_fare' => $this->base_fare,
            'extra_km_charge' => $this->extra_km_charge,
            'extra_min_charge' => $this->extra_min_charge,
            'night_charge' => $this->night_charge,
            'out_station_charge' => $this->out_station_charge,
            'facilities' => $this->facilities,
            'status' => $this->status,
            'created_on' => $this->created_on,
            'updated_on' => $this->updated_on,
            'create_user_id' => $this->create_user_id,
            'update_user_id' => $this->update_user_id,
        ]);

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
        $query = CityRateCard::find()
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
            'city_id' => $this->city_id,
            'trip_type' => $this->trip_type,
            'ride_type' => $this->ride_type,
            'base_hour' => $this->base_hour,
            'base_km' => $this->base_km,
            'base_fare' => $this->base_fare,
            'extra_km_charge' => $this->extra_km_charge,
            'extra_min_charge' => $this->extra_min_charge,
            'night_charge' => $this->night_charge,
            'out_station_charge' => $this->out_station_charge,
            'facilities' => $this->facilities,
            'status' => $this->status,
            'created_on' => $this->created_on,
            'updated_on' => $this->updated_on,
            'create_user_id' => $this->create_user_id,
            'update_user_id' => $this->update_user_id,
        ]);

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
