<?php

namespace app\modules\admin\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\admin\models\RideEarnings;

/**
 * app\modules\admin\models\search\RideEarningsSearch represents the model behind the search form about `app\modules\admin\models\RideEarnings`.
 */
 class RideEarningsSearch extends RideEarnings
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'ride_id', 'driver_id', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['total_ditance_km', 'admin_earning', 'driver_earning'], 'number'],
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
    public function search($params,$type='',$vehicle_id="")
    {
        if(!empty($type)){
            // $query = RideEarnings::find()->where(['type'=>$type])->andWhere(['vehicle_id' => $vehicle_id]);
            $query = RideEarnings::find()->select(['ride_earnings.*'])
            ->leftJoin('ride_request', 'ride_request.id = ride_earnings.ride_id')
            ->where(['ride_earnings.type' => $type])
            ->andWhere(['ride_request.vehical_type' => $vehicle_id]);

        }else{
            $query = RideEarnings::find()->where(['!=','type',RideEarnings::MANUL])->andWhere(['vehicle_id' => $vehicle_id]);
            $query = RideEarnings::find()->select(['ride_earnings.*'])
            ->leftJoin('ride_request', 'ride_request.id = ride_earnings.ride_id')
            ->where(['!=','ride_earnings.type',RideEarnings::MANUL])
            ->andWhere(['ride_request.vehical_type' => $vehicle_id]);

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
            'ride_id' => $this->ride_id,
            'driver_id' => $this->driver_id,
            'total_ditance_km' => $this->total_ditance_km,
            'admin_earning' => $this->admin_earning,
            'driver_earning' => $this->driver_earning,
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
    public function managersearch($params,$vehicle_id="")
    {
        // $query = RideEarnings::find()
        //              ->where(['city_id' => \Yii::$app->user->identity->city_id]);

        $query = RideEarnings::find()->select(['ride_earnings.*'])
        ->leftJoin('ride_request', 'ride_request.id = ride_earnings.ride_id')
        ->where(['ride_request.city_id' => \Yii::$app->user->identity->city_id])
        ->andWhere(['ride_request.vehical_type' => $vehicle_id]);
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
            'ride_id' => $this->ride_id,
            'driver_id' => $this->driver_id,
            'total_ditance_km' => $this->total_ditance_km,
            'admin_earning' => $this->admin_earning,
            'driver_earning' => $this->driver_earning,
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
