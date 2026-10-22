<?php

namespace app\modules\admin\models\base;

use Yii;
use app\modules\geo\services\DrivingDistance;
use app\modules\admin\models\UserBookingRatings;
use app\modules\admin\models\base\DriverRequests;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use yii\helpers\StringHelper;

/**
 * This is the base model class for table "driver_cancel_duplicate".
 *
 * @property integer $id
 * @property integer $booking_id
 * @property integer $user_id
 * @property integer $driver_id
 * @property integer $city_id
 * @property integer $rider_type
 * @property integer $trip_type
 * @property string $pickup_latitude
 * @property string $pickup_longitude
 * @property string $drop_latitude
 * @property string $drop_longitude
 * @property integer $car_type
 * @property string $car_model
 * @property string $pickup_date
 * @property string $pickup_time
 * @property string $estimate_price
 * @property integer $usage_hours
 * @property string $coupon_code
 * @property integer $payment_method
 * @property integer $status
 * @property string $created_on
 * @property string $updated_on
 * @property integer $create_user_id
 * @property integer $update_user_id
 * @property string $pickup_address
 * @property string $drop_address
 * @property string $cancel_reason
 */
class DriverCancelDuplicate extends \yii\db\ActiveRecord
{
    use \mootensai\relation\RelationTrait;

    const STATUS_DRIVER_CANCELLED = 10;

 
const RIDER_TYPE_ONEWAY = 1;
const RIDER_TYPE_ROUNDTRIP = 2;

const TRIP_TYPE_INTERCITY = 1;
const TRIP_TYPE_OUTSTATION = 2;

const STATUS_WAITING_FOR_DRIVER = 'waiting_for_driver';



const STATUS_DRIVER_ACCEPTED = 'accepted';
const STATUS_DRIVER_REJECTED = 'rejected';



    public $booking_id; // Add this line for the booking_id property

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'driver_cancel_duplicate';
    }

    /**
     * {@inheritdoc}
     */
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
                    // 'car_type',
                    // 'car_model',
                    // 'pickup_date',
                    // 'pickup_time',
                    // 'usage_hours',
                  //  'booking_id', // Add this line for the booking_id property
                ],
                'required',
            ],
            [
                [
                    'user_id',
                  //  'rider_type',
                    //'trip_type',
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

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            [
                'driver_id',
                'booking_id', // Add this line for the booking_id property
                'city_id',
                'rider_type',
                'trip_type',
                'pickup_latitude',
                'user_id',
                'pickup_longitude',
                'drop_latitude',
                'drop_longitude',
                'car_type',
                'car_model',
                'pickup_date',
                'pickup_time',
                'estimate_price',
                'usage_hours',
                'coupon_code',
                'payment_method',
                'status',
                'created_on',
                'updated_on',
                'pickup_address',
                'drop_address',
                'cancel_reason',
                'created_at',
            ]
        );
    }


    public static function geRideType()
    {
        return [
            self::RIDER_TYPE_ONEWAY => 'One Way',
            self::RIDER_TYPE_ROUNDTRIP => 'Round Trip',
        ];
    }

    public static function getTripType()
    {
        return [
            self::TRIP_TYPE_INTERCITY => 'Inner City',
            self::TRIP_TYPE_OUTSTATION => 'Out Station',
        ];
    }

    public function getRideTypeBadges()
    {
        if ($this->rider_type == self::RIDER_TYPE_ONEWAY) {
            return '<span class="badge text-bg-success">One Way</span>';
        } elseif ($this->rider_type == self::RIDER_TYPE_ROUNDTRIP) {
            return '<span class="badge text-bg-danger">Round Trip</span>';
        }
    }

    public function getTripTypeBadges()
    {
        if ($this->trip_type == self::TRIP_TYPE_INTERCITY) {
            return '<span class="badge text-bg-success">Inner City</span>';
        } elseif ($this->trip_type == self::TRIP_TYPE_OUTSTATION) {
            return '<span class="badge text-bg-danger">Out Station</span>';
        }
    }

    public function getStateOptions()
    {
        return [
            self::STATUS_WAITING_FOR_DRIVER => 'Waiting for Driver',
            self::STATUS_DRIVER_ACCEPTED => 'Driver Accepted',
            self::STATUS_DRIVER_REJECTED => 'Driver Rejected',
           
            self::STATUS_DRIVER_CANCELLED => 'Driver Cancelled',
           
        ];
    }

    public function getStateOptionsBadges()
    {
        $status = $this->status;

        if ($status == self::STATUS_WAITING_FOR_DRIVER) {
            return '<span class="badge text-bg-primary">Waiting for Driver</span>';
        } elseif ($status == self::STATUS_DRIVER_ACCEPTED) {
            return '<span class="badge text-bg-info">Driver Accepted</span>';
        } elseif ($status == self::STATUS_DRIVER_REJECTED) {
            return '<span class="badge text-bg-warning">Driver Rejected</span>';
        
       
        } elseif ($status == self::STATUS_DRIVER_CANCELLED) {
            return '<span class="badge text-bg-danger">Driver Cancelled</span>';
          }

        return ''; // Return an empty string if the status is not recognized
    }

    public function getFeatureOptions()
    {
        return [
            self::IS_FEATURED => 'Is Featured',
            self::IS_NOT_FEATURED => 'Not Featured',
        ];
    }

    public function getFeatureOptionsBadges()
    {
        if ($this->is_featured == self::IS_FEATURED) {
            return '<span class="badge text-bg-success">Featured</span>';
        } elseif ($this->is_featured == self::IS_NOT_FEATURED) {
            return '<span class="badge text-bg-danger">Not Featured</span>';
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'rider_type' => 'Rider Type',
            'trip_type' => 'Trip Type',
            'pickup_latitude' => 'Pickup Latitude',
            'pickup_longitude' => 'Pickup Longitude',
            'drop_latitude' => 'Drop Latitude',
            'drop_longitude' => 'Drop Longitude',
            'car_type' => 'Car Type',
            'car_model' => 'Car Model',
            'pickup_date' => 'Pickup Date',
            'pickup_time' => 'Pickup Time',
            'estimate_price' => 'Estimate Price',
            'usage_hours' => 'Usage Hours',
            'coupon_code' => 'Coupon Code',
            'payment_method' => 'Payment Method',
            'status' => 'Status',
            'created_on' => 'Created On',
            'updated_on' => 'Updated On',
            'create_user_id' => 'Create User ID',
            'update_user_id' => 'Update User ID',
        ];
    }

    /**
     * The customer of this record, asked from the user-app system (never read from a local customer table).
     *
     * @return array [id, name, phone, email, dob, gender] with empty strings when unknown
     */
    public function getCustomer()
    {
        return \app\modules\customerapi\services\CustomerClient::profileCached($this->user_id);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(\app\models\User::className(), [
            'id' => 'user_id',
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCarModel()
    {
        return $this->hasOne(
            \app\modules\admin\models\VehicleTypes::className(),
            ['id' => 'car_model']
        );
    }

    // public static function getCarTypes()
    // {
    //     return [
    //         self::CAR_TYPE_SUV => 'SUV',
    //         self::CAR_TYPE_SEDAN => 'Sedan',
    //         self::CAR_TYPE_SUV_PLUS => 'SUV+',
    //         self::CAR_TYPE_RUBIA_SERVICES => 'Rubia Services',
    //         self::CAR_TYPE_HATCHBACK => 'Hatchback',
    //     ];
    // }


   



    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDriver()
    {
        return $this->hasOne(\app\modules\admin\models\User::className(), [
            'id' => 'driver_id',
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVehicleVerifications()
    {
        return $this->hasMany(
            \app\modules\admin\models\VehicleVerification::className(),
            ['booking_id' => 'id']
        );
    }

    public function getDriverRequests()
    {
        return $this->hasMany(
            \app\modules\admin\models\DriverRequests::className(),
            ['booking_id' => 'id']
        );
    }

    /**
     * @inheritdoc
     * @return array mixed
     */
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'created_on',
                'updatedAtAttribute' => 'updated_on',
                'value' => date('Y-m-d H:i:s'),
            ],
            'blameable' => [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'create_user_id',
                'updatedByAttribute' => 'update_user_id',
            ],
            
        ];
    }

    /**
     * @inheritdoc
     * @return \app\modules\admin\models\BookingQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\admin\models\BookingQuery(get_called_class());
    }

    public static function finalDistance(
        $finalPickupLatitude,
        $finalPickupLongitude,
        $finalDropLatitude,
        $finalDropLongitude
    ) {
        // $finalPickupLatitude = 37.7749; // Latitude of the final pickup location
        // $finalPickupLongitude = -122.4194; // Longitude of the final pickup location
        // $finalDropLatitude = 34.0522; // Latitude of the final drop location
        // $finalDropLongitude = -118.2437; // Longitude of the final drop location
        $earthRadius = 6371;

        // Convert coordinates to radians
        $pickupLatitudeRad = deg2rad($finalPickupLatitude);
        $pickupLongitudeRad = deg2rad($finalPickupLongitude);
        $dropLatitudeRad = deg2rad($finalDropLatitude);
        $dropLongitudeRad = deg2rad($finalDropLongitude);

        // Calculate the differences between the latitudes and longitudes
        $latitudeDiff = $dropLatitudeRad - $pickupLatitudeRad;
        $longitudeDiff = $dropLongitudeRad - $pickupLongitudeRad;

        // Apply the Haversine formula
        $a =
            sin($latitudeDiff / 2) * sin($latitudeDiff / 2) +
            cos($pickupLatitudeRad) *
                cos($dropLatitudeRad) *
                sin($longitudeDiff / 2) *
                sin($longitudeDiff / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        // Output the total distance
        return round($distance, 2) . ' km';
    }

    

    public function asJson($driverId = "")
    {
        $data = [];
        $data['id'] = $this->id;

        $data['reference_id'] = $this->reference_id;

        $data['user_id'] = $this->user_id;

        $data['user_details'] = \app\modules\customerapi\services\CustomerClient::userJson($this->user_id);

        $data['driver_id'] = $this->driver_id;

        $first_name = !empty($this->driver->first_name)? $this->driver->first_name: '';
        $last_name = !empty($this->driver->last_name)? $this->driver->last_name: '';
        $driver_image = !empty($this->driver->profile_image)? $this->driver->profile_image: '';
        $data['driver_name'] = $first_name . ' ' . $last_name;
        $data['driver_image'] = $driver_image;
        $data['driver_contact'] = !empty($this->driver->contact_no)? $this->driver->contact_no: '';
        $data['rider_type'] = $this->rider_type;
        $driverRequests = DriverRequests::find()
            ->where(['driver_id' => $this->driver_id])
            ->andWHere(['booking_id' => $this->id])
            ->one();

        $data['trip_type'] = $this->trip_type;

        $data['pickup_latitude'] = $this->pickup_latitude;

        $data['pickup_longitude'] = $this->pickup_longitude;

        $data['drop_latitude'] = $this->drop_latitude;

        $data['drop_longitude'] = $this->drop_longitude;

        $data['pickup_address'] = $this->pickup_address;

        $data['drop_address'] = $this->drop_address;

        $data['car_type'] = $this->car_type;

        $data['car_model'] = $this->car_model; 

        $data['pickup_date'] = $this->pickup_date;

        $data['pickup_time'] = date('h:i A', strtotime($this->pickup_time));

        $data['otp'] = $this->otp;

        $data['estimate_price'] = $this->estimate_price;

        $data['gst'] = $this->gst;

        $data['base_fare'] = $this->base_fare;

        $data['secured_fee'] = $this->secured_fee;

        $data['platform_charges'] = $this->platform_charges;

        $data['additional_charges'] = $this->additional_charges;
        

        $data['subtotal'] = $this->subtotal;

        $data['final_price'] = $this->final_price;
      

        $data['extra_hours_price'] = $this->extra_hours_price;

        $data['total_price'] = $this->total_price;

        $data['usage_hours'] = $this->usage_hours;

        $data['coupon_code'] = $this->coupon_code;

        $data['payment_method'] = $this->payment_method;

        $data['total_distance'] = $this->total_distance;

        $data['start_time'] = $this->start_time;

        $data['end_time'] = date('h:i A',strtotime($this->end_time));
      
      	$data['end_date'] = $this->end_time;

        $data['car_number'] = $this->car_number;

        $cityCard = CityRateCard::find()->where(['city_id' => $this->city_id])->andWhere(['ride_type' => $this->rider_type])->andWhere(['trip_type' => $this->trip_type])->one();
        if(!empty($cityCard))
        {
            $data['night_charge'] = $cityCard->night_charge;
        }
        
        /// check reject by driver
        $checkDriverRequest = DriverRequests::find()->andWhere(['driver_id' => $driverId])->andWhere(['booking_id' => $this->id])->andWhere(['status' => DriverRequests::STATUS_DRIVER_REJECTED])->one();
        if(!empty($checkDriverRequest))
        {
            $data['status'] = $checkDriverRequest->status;
        }
        else
        {
            $data['status'] = $this->status;
        }
        
        $data['final_drop_latitude'] = $this->final_drop_latitude;

        $data['final_drop_longitude'] = $this->final_drop_longitude;

        $data['final_drop_address'] = $this->final_drop_address;

        $data['is_vehicle_damage_protection'] = $this->is_vehicle_damage_protection;

        $data['created_on'] = $this->created_on;

        $data['updated_on'] = $this->updated_on;

        $data['create_user_id'] = $this->create_user_id;

        $data['update_user_id'] = $this->update_user_id;

        $data['update_user_id'] = $this->update_user_id;

        $data['vehicle_number'] = $this->vehicle_number;
        $data['coupon_discount'] = $this->coupon_discount;
        $data['payment_status'] = $this->payment_status;
        $data['price_detail'] = json_decode($this->price_detail,true);

 
        

        $vehicleVerifications = $this->getVehicleVerifications()->all();
        $data['vehicle_verifications'] = [];
        foreach ($vehicleVerifications as $verification) {
            $verificationData = [];
            $verificationData['id'] = $verification->id;
            $verificationData['image'] = $verification->image;
            $verificationData['image_type'] = $verification->getImageType();
            $verificationData['type'] = $verification->type;
            $verificationData['status'] = $verification->status;

            $data['vehicle_verifications'][] = $verificationData;
        }
        $UserBookingRatings = UserBookingRatings::find()
            ->where(['booking_id' => $this->id])
            ->one();
        if (!empty($UserBookingRatings)) {
            $data['user_my_ratings'] = $UserBookingRatings->asJson();
        } else {
            $data['user_my_ratings'] = '';
        }

        return $data;
    } 
 

}
