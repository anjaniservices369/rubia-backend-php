<?php

namespace app\modules\admin\models\search;


use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\admin\models\base\DriverCancelDuplicate;
use app\modules\admin\models\Booking;


class DriverCancelDuplicateSearch extends Model
{
    // Your search model attributes go here
    public $status;
    public $user_id;

    public $rider_type;
    public $trip_type;
    public $payment_method;
    public $create_user_id;
    public $update_user_id;
    public $booking_id;

    public $pickup_latitude; 
    public $pickup_longitude; 

    public $drop_latitude; 

    public $drop_longitude; 

    public $estimate_price; 
    public $usage_hours; 
    public $car_type; 
    public $car_model;
    public $pickup_date;

    public $pickup_time;

    public $coupon_code;




    public function rules()
    {
        return [
            [
                [
                    'user_id',
                    'rider_type',
                    'trip_type',
                    'pickup_latitude',
                    'pickup_longitude',
                    //'car_type',
                   // 'car_model',
                   // 'pickup_date',
                   // 'pickup_time',
                   // 'usage_hours',
                    'booking_id', // Add this line for the booking_id property
                ],
                'required',
            ],
            [
                [
                    'user_id',
                    'rider_type',
                    'trip_type',
                    'payment_method',
                    'status',
                    'create_user_id',
                    'update_user_id',
                    'booking_id', // Add this line for the booking_id property
                ],
                'integer',
            ],
            [
                [
                    'pickup_latitude',
                    'pickup_longitude',
                    'drop_latitude',
                    'drop_longitude',
                    'estimate_price',
                    'usage_hours',
                ],
                'number',
            ],
            [['pickup_date', 'pickup_time', 'created_on', 'updated_on'], 'safe'],
            [
                ['car_model', 'coupon_code'],
                'string',
                'max' => 255,
            ],
            [['created_at'], 'safe'],
        ];
    }


    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function search($params)
{
    $query = DriverCancelDuplicate::find(); // Use the correct model class here

    $dataProvider = new ActiveDataProvider([
        'query' => $query,
        'sort' => [
            'defaultOrder' => [
                'created_at' => SORT_DESC,
            ],
        ],
    ]);

    $this->load($params);

    if (!$this->validate()) {
        return $dataProvider;
    }


    

    // Your existing search conditions for DriverCancelDuplicate model go here
    $query->andFilterWhere(['status' => $this->status]);
    $query->andFilterWhere(['user_id' => $this->user_id]);
    // Add other conditions as needed

    return $dataProvider;
}

}
