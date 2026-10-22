<?php

namespace app\modules\admin\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\admin\models\Booking;

/**
 * app\modules\admin\models\search\BookingSearch represents the model behind the search form about `app\modules\admin\models\Booking`.
 */
 class BookingSearch extends Booking
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'user_id', 'rider_type', 'trip_type', 'car_type', 'usage_hours', 'payment_method', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['pickup_latitude', 'pickup_longitude', 'drop_latitude', 'drop_longitude', 'estimate_price'], 'number'],
            [['car_model', 'pickup_date', 'pickup_time', 'coupon_code', 'created_on', 'updated_on', 'booking_number'], 'safe'],
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
    public function search($params,$status='')
    {
        $query = Booking::find();
        if(!empty($status)){
            $query->where(['status'=>$status]);
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
            'user_id' => $this->user_id,
            'rider_type' => $this->rider_type,
            'trip_type' => $this->trip_type,
            'pickup_latitude' => $this->pickup_latitude,
            'pickup_longitude' => $this->pickup_longitude,
            'drop_latitude' => $this->drop_latitude,
            'drop_longitude' => $this->drop_longitude,
            'car_type' => $this->car_type,
            'pickup_date' => $this->pickup_date,
            'pickup_time' => $this->pickup_time,
            'estimate_price' => $this->estimate_price,
            'usage_hours' => $this->usage_hours,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'create_user_id' => $this->create_user_id,
            'update_user_id' => $this->update_user_id,
        ]);

        $query->andFilterWhere(['like', 'car_model', $this->car_model])
            ->andFilterWhere(['like', 'coupon_code', $this->coupon_code])
            ->andFilterWhere(['like', 'booking_number', $this->booking_number])
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
    public function managersearch($params,$status="")
    {
        $query = Booking::find()
                     ->where(['city_id' => \Yii::$app->user->identity->city_id])->andWhere(['status' => $status])
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
            'rider_type' => $this->rider_type,
            'trip_type' => $this->trip_type,
            'pickup_latitude' => $this->pickup_latitude,
            'pickup_longitude' => $this->pickup_longitude,
            'drop_latitude' => $this->drop_latitude,
            'drop_longitude' => $this->drop_longitude,
            'car_type' => $this->car_type,
            'pickup_date' => $this->pickup_date,
            'pickup_time' => $this->pickup_time,
            'estimate_price' => $this->estimate_price,
            'usage_hours' => $this->usage_hours,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'create_user_id' => $this->create_user_id,
            'update_user_id' => $this->update_user_id,
        ]);

        $query->andFilterWhere(['like', 'car_model', $this->car_model])
            ->andFilterWhere(['like', 'coupon_code', $this->coupon_code])
            ->andFilterWhere(['like', 'booking_number', $this->booking_number])
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
