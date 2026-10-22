<?php

namespace app\modules\admin\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\admin\models\RideRequest;

/**
 * app\modules\admin\models\search\RideRequestSearch represents the model behind the search form about `app\modules\admin\models\RideRequest`.
 */
 class RideRequestSearch extends RideRequest
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'user_id', 'driver_id', 'pickup_pincode', 'drop_pincode', 'vehical_type', 'coupon_code', 'coupon_applied_id', 'otp', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['pickup_address', 'pickup_latitude', 'pickup_longitude', 'drop_address', 'drop_latitude', 'drop_longitude', 'ride_start_time', 'ride_end_time', 'created_on', 'updated_on'], 'safe'],
            [['estimated_ride_fare', 'estimated_distance', 'estimated_time', 'final_distance', 'final_time', 'final_price', 'coupon_discount', 'total_fare'], 'number'],
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
    public function search($params,$status='',$vehicel_id='')
    {
        if(!empty($status)){
            // $query = RideRequest::find()->where(['status'=>$status])->andWhere(['vehical_type'=>$vehicel_id])->andWhere(['city_id'=>$city_id]);
            $query = RideRequest::find()->where(['status'=>$status])->andWhere(['vehical_type'=>$vehicel_id]);

        }
        else{
            $query = RideRequest::find()->where(['vehical_type'=>$vehicel_id]);

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
            'driver_id' => $this->driver_id,
            'pickup_pincode' => $this->pickup_pincode,
            'drop_pincode' => $this->drop_pincode,
            'vehical_type' => $this->vehical_type,
            'estimated_ride_fare' => $this->estimated_ride_fare,
            'estimated_distance' => $this->estimated_distance,
            'estimated_time' => $this->estimated_time,
            'final_distance' => $this->final_distance,
            'final_time' => $this->final_time,
            'final_price' => $this->final_price,
            'coupon_code' => $this->coupon_code,
            'coupon_discount' => $this->coupon_discount,
            'coupon_applied_id' => $this->coupon_applied_id,
            'total_fare' => $this->total_fare,
            'otp' => $this->otp,
            'ride_start_time' => $this->ride_start_time,
            'ride_end_time' => $this->ride_end_time,
            'status' => $this->status,
            // 'created_on' => $this->created_on,
            'updated_on' => $this->updated_on,
            'create_user_id' => $this->create_user_id,
            'update_user_id' => $this->update_user_id,
        ]);

        $query->andFilterWhere(['like', 'pickup_address', $this->pickup_address])
            ->andFilterWhere(['like', 'pickup_latitude', $this->pickup_latitude])
            ->andFilterWhere(['like', 'pickup_longitude', $this->pickup_longitude])
            ->andFilterWhere(['like', 'drop_address', $this->drop_address])
            ->andFilterWhere(['like', 'drop_latitude', $this->drop_latitude])
            ->andFilterWhere(['like', 'drop_longitude', $this->drop_longitude])
            ->andFilterWhere(['like', 'created_on', $this->created_on]);
            // print_r($query->createCommand()->getRawSql());exit;

        return $dataProvider;
    }
    public function searchManager($params,$status='',$vehicel_id='',$city_id="")
    {
        if(!empty($status)){
            // $query = RideRequest::find()->where(['status'=>$status])->andWhere(['vehical_type'=>$vehicel_id])->andWhere(['city_id'=>$city_id]);
            $query = RideRequest::find()->where(['status'=>$status])->andWhere(['vehical_type'=>$vehicel_id]);

        }
        else{
            $query = RideRequest::find()->where(['vehical_type'=>$vehicel_id])->andWhere(['city_id'=>$city_id]);

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
            'driver_id' => $this->driver_id,
            'pickup_pincode' => $this->pickup_pincode,
            'drop_pincode' => $this->drop_pincode,
            'vehical_type' => $this->vehical_type,
            'estimated_ride_fare' => $this->estimated_ride_fare,
            'estimated_distance' => $this->estimated_distance,
            'estimated_time' => $this->estimated_time,
            'final_distance' => $this->final_distance,
            'final_time' => $this->final_time,
            'final_price' => $this->final_price,
            'coupon_code' => $this->coupon_code,
            'coupon_discount' => $this->coupon_discount,
            'coupon_applied_id' => $this->coupon_applied_id,
            'total_fare' => $this->total_fare,
            'otp' => $this->otp,
            'ride_start_time' => $this->ride_start_time,
            'ride_end_time' => $this->ride_end_time,
            'status' => $this->status,
            // 'created_on' => $this->created_on,
            'updated_on' => $this->updated_on,
            'create_user_id' => $this->create_user_id,
            'update_user_id' => $this->update_user_id,
        ]);

        $query->andFilterWhere(['like', 'pickup_address', $this->pickup_address])
            ->andFilterWhere(['like', 'pickup_latitude', $this->pickup_latitude])
            ->andFilterWhere(['like', 'pickup_longitude', $this->pickup_longitude])
            ->andFilterWhere(['like', 'drop_address', $this->drop_address])
            ->andFilterWhere(['like', 'drop_latitude', $this->drop_latitude])
            ->andFilterWhere(['like', 'drop_longitude', $this->drop_longitude])
            ->andFilterWhere(['like', 'created_on', $this->created_on]);
            // print_r($query->createCommand()->getRawSql());exit;

        return $dataProvider;
    }

    public function searchFranchise($params,$status='',$vehicel_id='',$city_id="")
    {
        if(!empty($status)){
            $query = RideRequest::find()->where(['status'=>$status])->andWhere(['vehical_type'=>$vehicel_id])->andWhere(['city_id'=>$city_id]);

        }
        else{
            $query = RideRequest::find()->where(['vehical_type'=>$vehicel_id])->andWhere(['city_id'=>$city_id]);

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
            'driver_id' => $this->driver_id,
            'pickup_pincode' => $this->pickup_pincode,
            'drop_pincode' => $this->drop_pincode,
            'vehical_type' => $this->vehical_type,
            'estimated_ride_fare' => $this->estimated_ride_fare,
            'estimated_distance' => $this->estimated_distance,
            'estimated_time' => $this->estimated_time,
            'final_distance' => $this->final_distance,
            'final_time' => $this->final_time,
            'final_price' => $this->final_price,
            'coupon_code' => $this->coupon_code,
            'coupon_discount' => $this->coupon_discount,
            'coupon_applied_id' => $this->coupon_applied_id,
            'total_fare' => $this->total_fare,
            'otp' => $this->otp,
            'ride_start_time' => $this->ride_start_time,
            'ride_end_time' => $this->ride_end_time,
            'status' => $this->status,
            // 'created_on' => $this->created_on,
            'updated_on' => $this->updated_on,
            'create_user_id' => $this->create_user_id,
            'update_user_id' => $this->update_user_id,
        ]);

        $query->andFilterWhere(['like', 'pickup_address', $this->pickup_address])
            ->andFilterWhere(['like', 'pickup_latitude', $this->pickup_latitude])
            ->andFilterWhere(['like', 'pickup_longitude', $this->pickup_longitude])
            ->andFilterWhere(['like', 'drop_address', $this->drop_address])
            ->andFilterWhere(['like', 'drop_latitude', $this->drop_latitude])
            ->andFilterWhere(['like', 'drop_longitude', $this->drop_longitude])
            ->andFilterWhere(['like', 'created_on', $this->created_on]);
            // print_r($query->createCommand()->getRawSql());exit;

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
        $query = RideRequest::find()
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
            'driver_id' => $this->driver_id,
            'pickup_pincode' => $this->pickup_pincode,
            'drop_pincode' => $this->drop_pincode,
            'vehical_type' => $this->vehical_type,
            'estimated_ride_fare' => $this->estimated_ride_fare,
            'estimated_distance' => $this->estimated_distance,
            'estimated_time' => $this->estimated_time,
            'final_distance' => $this->final_distance,
            'final_time' => $this->final_time,
            'final_price' => $this->final_price,
            'coupon_code' => $this->coupon_code,
            'coupon_discount' => $this->coupon_discount,
            'coupon_applied_id' => $this->coupon_applied_id,
            'total_fare' => $this->total_fare,
            'otp' => $this->otp,
            'ride_start_time' => $this->ride_start_time,
            'ride_end_time' => $this->ride_end_time,
            'status' => $this->status,
            'created_on' => $this->created_on,
            'updated_on' => $this->updated_on,
            'create_user_id' => $this->create_user_id,
            'update_user_id' => $this->update_user_id,
        ]);

        $query->andFilterWhere(['like', 'pickup_address', $this->pickup_address])
            ->andFilterWhere(['like', 'pickup_latitude', $this->pickup_latitude])
            ->andFilterWhere(['like', 'pickup_longitude', $this->pickup_longitude])
            ->andFilterWhere(['like', 'drop_address', $this->drop_address])
            ->andFilterWhere(['like', 'drop_latitude', $this->drop_latitude])
            ->andFilterWhere(['like', 'drop_longitude', $this->drop_longitude]);

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
