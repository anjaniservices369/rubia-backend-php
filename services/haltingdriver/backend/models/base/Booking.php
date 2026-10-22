<?php

namespace app\modules\admin\models\base;

use app\modules\geo\services\DrivingDistance;
use app\modules\admin\models\UserBookingRatings;
use app\modules\admin\models\base\DriverRequests;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use yii\helpers\StringHelper;
use app\modules\admin\models\base\CancelRideCharges;
/**
 * This is the base model class for table "booking".
 *
 * @property integer $id
 * @property integer $user_id
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
 * @property integer $notif_status
 * @property string $created_on
 * @property string $updated_on
 * @property integer $create_user_id
 * @property integer $update_user_id
 *
 * @property \app\modules\admin\models\User $user
 */
class Booking extends \yii\db\ActiveRecord
{
    use \mootensai\relation\RelationTrait;

    /**
     * This function helps \mootensai\relation\RelationTrait runs faster
     * @return array relation names of this model 
     */
    public function relationNames()
    {
        return [
            'user',
            'carModel',
            'driver',
            'vehicleVerifications',
            'driverRequests',
        ];
    } 

    const STATUS_WAITING_FOR_DRIVER = 1;
    const STATUS_DRIVER_ACCEPTED = 2;
    const STATUS_DRIVER_REJECTED = 3;
    const STATUS_GO_FOR_PICKUP = 4;
    const STATUS_ARRIVED_LOCATION = 5;
    const STATUS_START_RIDE = 6;
    const STATUS_RIDE_COMPLETED = 7;
    const STATUS_USER_CANCELLED = 8;
    const STATUS_BOOKING_AUTO_CANCELLED = 9;
    const STATUS_DRIVER_CANCELLED = 10;
    const STATUS_CANCELLED = 11;
    const STATUS_ACCEPTED_RIDES = 12;
   const STATUS_EXPIRED = 13;
 

    const IS_FEATURED = 1;
    const IS_NOT_FEATURED = 0;

    const RIDER_TYPE_ONEWAY = 1;
    const RIDER_TYPE_ROUNDTRIP = 2;

    const TRIP_TYPE_INTERCITY = 1;
    const TRIP_TYPE_OUTSTATION = 2;

    const CAR_TYPE_AUTOMATIC = 1;
    const CAR_TYPE_MANUAL = 2;

    const is_vehicle_damage_protection_yes = 1;
    const is_vehicle_damage_protection_no = 2;

    const payment_method_wallet = 1;
    const payment_method_qr = 2;
    const payment_method_cash = 3;

    const payment_status_success = 1;
    const payment_status_pending = 2;
    const payment_status_failed = 3;

    const CAR_TYPE_SUV = 'suv';
    const CAR_TYPE_SEDAN = 'sedan';
    const CAR_TYPE_SUV_PLUS = 'suv_plus'; // Assuming CAR_SUV was a typo and it should be CAR_TYPE_SUV_PLUS
    const CAR_TYPE_RUBIA_SERVICES = 'rubia_services';
    const CAR_TYPE_HATCHBACK = 'hatchback';

    /**
     * @inheritdoc
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
                    'car_type',
                    'car_model',
                    'pickup_date',
                    'pickup_time',
                    'usage_hours',
                ],
                'required',
            ],
            [
                [
                    'user_id',
                    'rider_type',
                    'trip_type',
                   // 'car_type',
                    'payment_method',
                    'status',
                    'notif_status',
                    'create_user_id',
                    'update_user_id',
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
            [['pickup_date', 'pickup_time','gst','price_detail','odometer_end_image','odometer_end','odometer_start','odometer_start_image'], 'safe'],
            [
                ['car_model', 'coupon_code', 'created_on', 'updated_on'],
                'string',
                'max' => 255,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    /**
     * Every booking gets a unique, human readable number (BK-yymmdd-XXXXXX) when it is created.
     * The column is NOT NULL with a unique index, so an empty value would collide on the second booking.
     */
    public function beforeSave($insert)
    {
        if ($insert && ($this->booking_number === null || $this->booking_number === '')) {
            $this->booking_number = static::generateBookingNumber();
        }

        return parent::beforeSave($insert);
    }

    public static function generateBookingNumber()
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no 0/O/1/I
        $prefix = 'BK-' . date('ymd') . '-';

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $number = $prefix;
            for ($i = 0; $i < 6; $i++) {
                $number .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            if (!static::find()->where(['booking_number' => $number])->exists()) {
                return $number;
            }
        }

        // Practically unreachable; the unique index still protects against a duplicate.
        return $prefix . strtoupper(bin2hex(random_bytes(6)));
    }

    public static function tableName()
    {
        return 'booking';
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
            self::STATUS_GO_FOR_PICKUP => 'Go for Pickup',
            self::STATUS_ARRIVED_LOCATION => 'Arrived at Location',
            self::STATUS_START_RIDE => 'Start Ride',
            self::STATUS_RIDE_COMPLETED => 'Ride Completed',
            self::STATUS_USER_CANCELLED => 'User Cancelled',
            self::STATUS_BOOKING_AUTO_CANCELLED => 'Booking Auto Cancelled',
            self::STATUS_DRIVER_CANCELLED => 'Driver Cancelled',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_ACCEPTED_RIDES => 'Accepted Rides',
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
        } elseif ($status == self::STATUS_GO_FOR_PICKUP) {
            return '<span class="badge text-bg-primary">Go for Pickup</span>';
        } elseif ($status == self::STATUS_ARRIVED_LOCATION) {
            return '<span class="badge text-bg-primary">Arrived at Location</span>';
        } elseif ($status == self::STATUS_START_RIDE) {
            return '<span class="badge text-bg-primary">Start Ride</span>';
        } elseif ($status == self::STATUS_RIDE_COMPLETED) {
            return '<span class="badge text-bg-primary">Ride Completed</span>';
        } elseif ($status == self::STATUS_USER_CANCELLED) {
            return '<span class="badge text-bg-danger">User Cancelled</span>';
        } elseif ($status == self::STATUS_BOOKING_AUTO_CANCELLED) {
            return '<span class="badge text-bg-danger">Booking Auto Cancelled</span>';
        } elseif ($status == self::STATUS_DRIVER_CANCELLED) {
            return '<span class="badge text-bg-danger">Driver Cancelled</span>';
        } elseif ($status == self::STATUS_CANCELLED) {
            return '<span class="badge text-bg-danger">Cancelled</span>';
        } elseif ($status == self::STATUS_ACCEPTED_RIDES) {
            return '<span class="badge text-bg-primary">Accepted Rides</span>';
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

    public static function getCarTypes()
    {
        return [
            self::CAR_TYPE_SUV => 'SUV',
            self::CAR_TYPE_SEDAN => 'Sedan',
            self::CAR_TYPE_SUV_PLUS => 'SUV+',
            self::CAR_TYPE_RUBIA_SERVICES => 'Rubia Services',
            self::CAR_TYPE_HATCHBACK => 'Hatchback',
        ];
    }



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
        
      	$data['notif_status'] = $this->notif_status;
      
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

      
      $cancelChargeModel = new CancelRideCharges();
        $totalCancellationCharge = 0;
     	$cancellation_charges = 0;
        $userCancellationCharge = $cancelChargeModel->find()->where(['user_id' => $this->user_id])->one();

        if (!empty($userCancellationCharge)) {
          
            // Check for pending cancellation charges for the user
            $pendingCancellationCharges = $cancelChargeModel->find()
                ->where(['user_id' => $this->user_id, 'status' => CancelRideCharges::STATUS_PENDING])
                ->all();
          
          $cancellationSum= $cancelChargeModel->find()
                ->where(['settlement_booking_id' => $this->id])
                ->all();

            if (!empty($pendingCancellationCharges)) {
                $totalCancellationCharge = 0;

                foreach ($pendingCancellationCharges as $charge) {
                    $totalCancellationCharge += (float)$charge->cancel_charge;
                }
            }
          
          if (!empty($cancellationSum)) {
                $cancellation_charges = 0;

                foreach ($cancellationSum as $charge) {
                    $cancellation_charges += (float)$charge->cancel_charge;
                }
            }
        }

        $data['Pending_cancellation_charges'] = $totalCancellationCharge;
      	$data['cancellation_charges'] = $cancellation_charges;
       	$userCancellationCharge = WebSetting::find()->where(['setting_key' => 'user_cancellation_charge'])->one();
        $data['userCancellationCharge'] = $userCancellationCharge->value; 
 
               	$driverCancellationCharge = WebSetting::find()->where(['setting_key' => 'driver_cancellation_charge'])->one();
        $data['driverCancellationCharge'] = $driverCancellationCharge->value; 
 

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
 
    public function calculateEstimate()
    {
        $estimatePrice = 0;

        switch ($this->rider_type) {
            case 1:
                switch ($this->trip_type) {
                    case 1:
                        $estimatePrice = $this->calculateIntercityOneWayEstimate();
                        break;
                    case 2:
                        $estimatePrice = $this->calculateOutstationOneWayEstimate();
                        break;
                    default:
                        // Handle unsupported trip_type
                        break;
                }
                break;

            case 2:
                switch ($this->trip_type) {
                    case 1:
                        $estimatePrice = $this->calculateIntercityRoundTripEstimate();
                        break;
                    case 2:
                        $estimatePrice = $this->calculateOutstationRoundTripEstimate();
                        break;
                    default:
                        // Handle unsupported trip_type
                        break;
                }
                break;

            default:
                // Handle unsupported rider_type
                break;
        }
        return $estimatePrice;
    }

    // [doc:start] nightsTouched - Number of nights (22:00 to 06:00) a ride touches, so a ride of several days pays the night charge for each night.
    /**
     * Number of nights a ride touches. A night is the window 22:00 to 06:00 (from 22:00 of a day to 06:00 of the next).
     * The ride runs from $fromTs for $hours hours. A ride that starts at 23:00 and ends at 02:00 touches one night; a ride
     * of three days starting at 10:00 touches three; a ride that starts at 05:00 touches the night that is just ending.
     * The night charge of the rate card is charged once for every night touched.
     */
    public static function nightsTouched($fromTs, $hours)
    {
        $fromTs = (int) $fromTs;
        $end = $fromTs + (int) round(max(0.0, (float) $hours) * 3600);
        $day = strtotime(date('Y-m-d', $fromTs) . ' 00:00:00 -1 day');   // the night that began the evening before
        $nights = 0;
        for ($guard = 0; $day <= $end && $guard < 400; $guard++) {
            $nightStart = strtotime(date('Y-m-d', $day) . ' 22:00:00');
            $nightEnd = strtotime(date('Y-m-d', $day) . ' 22:00:00 +8 hours');
            if ($nightStart <= $end && $nightEnd > $fromTs) {
                $nights++;
            }
            $day = strtotime(date('Y-m-d', $day) . ' 00:00:00 +1 day');
        }

        return $nights;
    }
    // [doc:end] nightsTouched

    public function calculateIntercityOneWayEstimate($isFinal = '',$finalDistance='')
    {
       
        $driving_distance = new DrivingDistance();
        // $dropTimestamp = "0";
        if ($isFinal == 'true') {
            
            $start_time = $this->start_time;
            $end_time = $this->end_time;

            $pickupTimestamp = strtotime($start_time);
            $dropTimestamp = strtotime($end_time);

            $timeDifference = $dropTimestamp - $pickupTimestamp;
            // $usageHours = ceil($timeDifference / 3600);
            $usageHours = $timeDifference / 3600;

            if ($usageHours == 0) {
                $usageHours = 1;
            }
            $pickupTime = strtotime($this->start_time);

            $pickupDistance = $driving_distance->getDrivingDistanceGoogleMap(
                $this->final_pickup_latitude,
                $this->final_pickup_longitude,
                $this->final_drop_latitude,
                $this->final_drop_longitude
            );
          
        } else { 
            $pickupTime = strtotime($this->pickup_time);
            $usageHours = $this->usage_hours;
           $end_time = $this->end_time;
            $dropTimestamp = strtotime($end_time);

            $pickupDistance = $driving_distance->getDrivingDistanceGoogleMap(
                $this->pickup_latitude,
                $this->pickup_longitude,
                $this->drop_latitude,
                $this->drop_longitude
            );
          
        }

        // var_dump($this->drop_latitude);
        // var_dump($pickupDistance);exit;
        if($finalDistance !=null){
 $pickupDistance = $finalDistance;
        }else{
 $pickupDistance = ceil($pickupDistance['meters'] / 1000);
        }

        $cityRateCard = CityRateCard::findOne([
            'city_id' => $this->city_id,
            'ride_type' => $this->rider_type,
            'trip_type' => $this->trip_type,
        ]);

        // Retrieve the necessary fields from the CityRateCard object
        $baseHour = $cityRateCard->base_hour;
        $baseKm = $cityRateCard->base_km;
        $baseFare = $cityRateCard->base_fare;
        $extraKmCharge = $cityRateCard->extra_km_charge;
        $extraMinCharge = $cityRateCard->extra_min_charge;
        $nightCharge = $cityRateCard->night_charge;
        // $outStationCharge = $cityRateCard->out_station_charge;
        $outStationCharge = 0;

        // Check if the booking time is between 6:00 AM and 10:00 PM
        $bookingHour = date('H', $pickupTime);
        $isBookingWithinTimeRange = $bookingHour >= 6 && $bookingHour <= 22;

        // Check if the usage hours are between 1 and 10
        $isUsageHoursValid = $baseHour >= 1;

        // Calculate the estimate based on the conditions
        if ($isBookingWithinTimeRange && $isUsageHoursValid) {
            // Calculate the total distance (pickup distance + drop distance)
            $totalDistance = $pickupDistance;

            // Calculate the additional charges based on the pickup and drop distances
            if ($pickupDistance > $baseKm) {
                $additionalDistance = $pickupDistance - $baseKm;
                $additionalCharges = $additionalDistance * $extraKmCharge;
            } else {
                $additionalCharges = 0;
            }
            

          

            // Calculate the estimate price (base charge + additional charges)
            $estimatePrice = $baseFare + $additionalCharges;

            // Calculate the extra minute charges for usage hours beyond the base hours
            $extraMinuteCharges = 0;

            if ($usageHours > $baseHour) {
                $extraMinutes = ($usageHours - $baseHour) * 60;
                $extraMinuteCharges = $extraMinutes * $extraMinCharge;
            } 
           

            // Add the extra minute charges to the estimate price
            $estimatePrice += $extraMinuteCharges;

            // Night charge: once for every night (22:00-06:00) the ride touches, so a ride of several days pays for each night.
            $nights = self::nightsTouched($pickupTime, $usageHours);
            $isNightChargeApplicable = $nights > 0;
            $estimatePrice += $nightCharge * $nights;

            // Return the estimate price
            // return $estimatePrice;
            $secured_fee = WebSetting::find()->where(['setting_key' => 'secured_fee'])->one();
            $platform_charges = WebSetting::find()->where(['setting_key' => 'platform_charges'])->one();
            $gstPer = WebSetting::find()->where(['setting_key' => 'gst'])->one();

            // var_dump($gstPer->value);exit;


           
            $gst =  ceil(($estimatePrice + ( !empty($secured_fee) ? (int)$secured_fee->value:0) + ( !empty($platform_charges) ? (int)$platform_charges->value:0)) * ( !empty($gstPer) ? (((int)$gstPer->value)/100):1));



          
            $price['estimatePrice'] = ceil($estimatePrice+$gst) + ( !empty($secured_fee) ? (int)$secured_fee->value:0) + ( !empty($platform_charges) ? (int)$platform_charges->value:0);
            $price['baseFare'] = $baseFare;
            $price['securedFee'] = $secured_fee->value;
            $price['platformCharges'] = $platform_charges->value;
            $price['gst'] = $gst;
            $price['baseHour'] = $baseHour;
            $price['baseKm'] = $baseKm;
            $price['extraKmCharge'] = $extraKmCharge;
            $price['extraMinCharge'] = $extraMinCharge;
            $price['nightCharge'] = $nightCharge * $nights;
            $price['nightCount'] = $nights;
            $price['nightChargePerNight'] = $nightCharge;
            $price['outStationCharge'] = $outStationCharge;
            $price['estimatedDistance'] = ceil($pickupDistance);
            $price['additionalCharges'] = ceil(
                $additionalCharges + $extraMinuteCharges
            );
            $price['isNightChargeApplicable'] = $isNightChargeApplicable;
            $price['usageHours'] = $usageHours;
            $price['pickupDistance'] = $pickupDistance;
            //  'pickupDistance' =>$pickupDistance,

            // Return the estimate price
            return $price;
        } elseif (!$isBookingWithinTimeRange && $isUsageHoursValid) {
            // Calculate the total distance (pickup distance + drop distance)
            $totalDistance = $pickupDistance;

            // Calculate the additional charges based on the pickup and drop distances
            if ($pickupDistance > $baseKm) {
                $additionalDistance = $pickupDistance - $baseKm;
                $additionalCharges = $additionalDistance * $extraKmCharge;
            } else {
                $additionalCharges = 0;
            }

            // Calculate the estimate price (base charge + additional charges)
            $estimatePrice = $baseFare + $additionalCharges;
            // Check if the driver went outside of the city radius
            // Assuming the city radius is 5 kilometers
            $isOutsideCity = $totalDistance > 35;

            // Add the outside city charge if applicable
            if ($isOutsideCity) {
                $additionalCharges += $outStationCharge;
            }else{
                $outStationCharge = 0;
            }

            // Calculate the extra minute charges for usage hours beyond the base hours
            $extraMinuteCharges = 0;

            if ($usageHours > $baseHour) {
                $extraMinutes = ($usageHours - $baseHour) * 60;
                $extraMinuteCharges = $extraMinutes * $extraMinCharge;
            }
          
            // Add the extra minute charges to the estimate price
            $estimatePrice += $extraMinuteCharges;

            // Add the night charge for every night touched (at least the night of the pickup)
            $nights = max(1, self::nightsTouched($pickupTime, $usageHours));
            $estimatePrice += $nightCharge * $nights;
            $secured_fee = WebSetting::find()->where(['setting_key' => 'secured_fee'])->one();
            $platform_charges = WebSetting::find()->where(['setting_key' => 'platform_charges'])->one();
            $gstPer = WebSetting::find()->where(['setting_key' => 'gst'])->one();
            $price['estimatePrice'] = $estimatePrice + ( !empty($secured_fee) ? (int)$secured_fee->value:0) + ( !empty($platform_charges) ? (int)$platform_charges->value:0);
            $price['baseFare'] = $baseFare;
            $price['securedFee'] = $secured_fee->value;
            $price['platformCharges'] = $platform_charges->value;
            $price['gst'] = ceil(($price['estimatePrice'] + ( !empty($secured_fee) ? (int)$secured_fee->value:0) + ( !empty($platform_charges) ? (int)$platform_charges->value:0)) *( !empty($gstPer) ? (((int)$gstPer->value)/100):1));
            $price['baseHour'] = $baseHour;
            $price['baseKm'] = $baseKm;
            $price['extraKmCharge'] = $extraKmCharge;
            $price['extraMinCharge'] = $extraMinCharge;
            $price['nightCharge'] = $nightCharge * $nights;
            $price['nightCount'] = $nights;
            $price['nightChargePerNight'] = $nightCharge;
            $price['outStationCharge'] = $outStationCharge;
            $price['estimatedDistance'] = ceil($pickupDistance);
            $price['additionalCharges'] = ceil($additionalCharges);
            $price['isNightChargeApplicable'] = true;
            $price['usageHours'] = $usageHours;
            $price['pickupDistance'] = $pickupDistance;

            // Return the estimate price
            return $price;
        }

        // Return false if the conditions are not met
        return false;
    }
//     public function calculateIntercityOneWayEstimate($isFinal = '')
// {
//     $driving_distance = new DrivingDistance();
//     if ($isFinal == 'true') {
//         $start_time = $this->start_time;
//         $end_time = $this->end_time;

//         $pickupTimestamp = strtotime($start_time);
//         $dropTimestamp = strtotime($end_time);

//         $timeDifference = $dropTimestamp - $pickupTimestamp;
//         $usageHours = ceil($timeDifference / 3600);

//         if ($usageHours == 0) {
//             $usageHours = 1;
//         }
//         $pickupTime = strtotime($this->start_time);
//          $dropTime = strtotime($this->end_time);


//         $pickupDistance = $driving_distance->getDrivingDistanceGoogleMap(
//             $this->final_pickup_latitude,
//             $this->final_pickup_longitude,
//             $this->final_drop_latitude,
//             $this->final_drop_longitude
//         );
//     } else {
//         $pickupTime = strtotime($this->pickup_time);
//         $usageHours = $this->usage_hours;

//         $pickupDistance = $driving_distance->getDrivingDistanceGoogleMap(
//             $this->pickup_latitude,
//             $this->pickup_longitude,
//             $this->drop_latitude,
//             $this->drop_longitude
//         );
//     }

//     $pickupDistance = ceil($pickupDistance['meters'] / 1000);

//     $cityRateCard = CityRateCard::findOne([
//         'city_id' => $this->city_id,
//         'ride_type' => $this->rider_type,
//         'trip_type' => $this->trip_type,
//     ]);

//     $baseHour = $cityRateCard->base_hour;
//     $baseKm = $cityRateCard->base_km;
//     $baseFare = $cityRateCard->base_fare;
//     $extraKmCharge = $cityRateCard->extra_km_charge;
//     $extraMinCharge = $cityRateCard->extra_min_charge;
//     $nightCharge = $cityRateCard->night_charge;
//     $outStationCharge = 0;

//     $bookingHour = date('H', $pickupTime);
//     $isBookingWithinTimeRange = $bookingHour >= 6 && $bookingHour <= 22;

//     $isUsageHoursValid = $baseHour >= 1;

//     if ($isBookingWithinTimeRange && $isUsageHoursValid) {
//         $totalDistance = $pickupDistance;

//         if ($pickupDistance > $baseKm) {
//             $additionalDistance = $pickupDistance - $baseKm;
//             $additionalCharges = $additionalDistance * $extraKmCharge;
//         } else {
//             $additionalCharges = 0;
//         }

//         $estimatePrice = $baseFare + $additionalCharges;

//         $extraMinuteCharges = 0;

//         if ($usageHours > $baseHour) {
//             $extraMinutes = ($usageHours - $baseHour) * 60;
//             $extraMinuteCharges = $extraMinutes * $extraMinCharge;
//         }

//         $estimatePrice += $extraMinuteCharges;

//         $pickupTimeWithUsageHours = $pickupTime + $usageHours * 3600;
//         $bookingHourWithUsageHours = date('H', $pickupTimeWithUsageHours);

        
//                // Check if pickup and drop times fall under night hours
//         if($dropTime == null){
//  $isNightChargeApplicable = date('H', $pickupTime) >= 22 || date('H', $pickupTime) < 6;
//         }else{
//  $isNightChargeApplicable = (date('H', $pickupTime) >= 22 || date('H', $pickupTime) < 6) ||
//                             (date('H', $dropTime) >= 22 || date('H', $dropTime) < 6) ||
//                             (date('H', $pickupTime) < 6 || date('H', $dropTime) >= 6);
//         }
       

//         if ($isNightChargeApplicable) {
//             $estimatePrice += $nightCharge;
//         }

//         $secured_fee = WebSetting::find()->where(['setting_key' => 'secured_fee'])->one();
//         $platform_charges = WebSetting::find()->where(['setting_key' => 'platform_charges'])->one();
//         $gstPer = WebSetting::find()->where(['setting_key' => 'gst'])->one();

//         $gst = ceil(($estimatePrice + (!empty($secured_fee) ? (int)$secured_fee->value : 0) + (!empty($platform_charges) ? (int)$platform_charges->value : 0)) * (!empty($gstPer) ? (((int)$gstPer->value) / 100) : 1));

//         $price['estimatePrice'] = ceil($estimatePrice + $gst) + (!empty($secured_fee) ? (int)$secured_fee->value : 0) + (!empty($platform_charges) ? (int)$platform_charges->value : 0);
//         $price['baseFare'] = $baseFare;
//         $price['securedFee'] = $secured_fee->value;
//         $price['platformCharges'] = $platform_charges->value;
//         $price['gst'] = $gst;
//         $price['baseHour'] = $baseHour;
//         $price['baseKm'] = $baseKm;
//         $price['extraKmCharge'] = $extraKmCharge;
//         $price['extraMinCharge'] = $extraMinCharge;
//         $price['nightCharge'] = $isNightChargeApplicable ? $nightCharge : 0;
//         $price['outStationCharge'] = $outStationCharge;
//         $price['estimatedDistance'] = ceil($pickupDistance);
//         $price['additionalCharges'] = ceil($additionalCharges + $extraMinuteCharges);
//         $price['isNightChargeApplicable'] = $isNightChargeApplicable;
//         $price['usageHours'] = $usageHours;
//         $price['pickupDistance'] = $pickupDistance;
//         // var_dump($price);exit;
//         return $price;
//     } elseif (!$isBookingWithinTimeRange && $isUsageHoursValid) {
//         // die("hereeeee");
//         $totalDistance = $pickupDistance;

//         if ($pickupDistance > $baseKm) {
//             $additionalDistance = $pickupDistance - $baseKm;
//             $additionalCharges = $additionalDistance * $extraKmCharge;
//         } else {
//             $additionalCharges = 0;
//         }

//         $estimatePrice = $baseFare + $additionalCharges;

//         $isOutsideCity = $totalDistance > 35;

//         if ($isOutsideCity) {
//             $additionalCharges += $outStationCharge;
//         } else {
//             $outStationCharge = 0;
//         }

//         $extraMinuteCharges = 0;

//         if ($usageHours > $baseHour) {
//             $extraMinutes = ($usageHours - $baseHour) * 60;
//             $extraMinuteCharges = $extraMinutes * $extraMinCharge;
//         }

//         $estimatePrice += $extraMinuteCharges;

//         $estimatePrice += $nightCharge;
//         $secured_fee = WebSetting::find()->where(['setting_key' => 'secured_fee'])->one();
//         $platform_charges = WebSetting::find()->where(['setting_key' => 'platform_charges'])->one();
//         $gstPer = WebSetting::find()->where(['setting_key' => 'gst'])->one();
//         $price['estimatePrice'] = $estimatePrice + (!empty($secured_fee) ? (int)$secured_fee->value : 0) + (!empty($platform_charges) ? (int)$platform_charges->value : 0);
//         $price['baseFare'] = $baseFare;
//         $price['securedFee'] = $secured_fee->value;
//         $price['platformCharges'] = $platform_charges->value;
//         $price['gst'] = ceil(($price['estimatePrice'] + (!empty($secured_fee) ? (int)$secured_fee->value : 0) + (!empty($platform_charges) ? (int)$platform_charges->value : 0)) * (!empty($gstPer) ? (((int)$gstPer->value) / 100) : 1));
//         $price['baseHour'] = $baseHour;
//         $price['baseKm'] = $baseKm;
//         $price['extraKmCharge'] = $extraKmCharge;
//         $price['extraMinCharge'] = $extraMinCharge;
//         $price['nightCharge'] = $nightCharge;
//         $price['outStationCharge'] = $outStationCharge;
//         $price['estimatedDistance'] = ceil($pickupDistance);
//         $price['additionalCharges'] = ceil($additionalCharges);
//         $price['isNightChargeApplicable'] = true;
//         $price['usageHours'] = $usageHours;
//         $price['pickupDistance'] = $pickupDistance;

//         return $price;
//     }

//     return false;
// }


    public function calculateOutstationOneWayEstimate($isFinal = '',$finalDistance='')
    {
        $secured_fee = WebSetting::find()->where(['setting_key' => 'secured_fee'])->one();
        $platform_charges = WebSetting::find()->where(['setting_key' => 'platform_charges'])->one();
        $gstPer = WebSetting::find()->where(['setting_key' => 'gst'])->one();
        $pickupTime = strtotime($this->pickup_time);
        $usageHours = $this->usage_hours;
        $driving_distance = new DrivingDistance();

        if ($isFinal == 'true') {
            $start_time = $this->start_time;
            $end_time = $this->end_time;

            $pickupTimestamp = strtotime($start_time);
            $dropTimestamp = strtotime($end_time);

            $timeDifference = $dropTimestamp - $pickupTimestamp;
            // $usageHours = ceil($timeDifference / 3600);
            $usageHours = $timeDifference / 3600;

            if ($usageHours == 0) {
                $usageHours = 1;
            }
            $pickupTime = strtotime($this->start_time);

            $pickupDistance = $driving_distance->getDrivingDistanceGoogleMap(
                $this->final_pickup_latitude,
                $this->final_pickup_longitude,
                $this->final_drop_latitude,
                $this->final_drop_longitude
            );
        } else {
            $pickupTime = strtotime($this->pickup_time);
            $usageHours = $this->usage_hours;
           $end_time = $this->end_time;
            $dropTimestamp = strtotime($end_time);

            $pickupDistance = $driving_distance->getDrivingDistanceGoogleMap(
                $this->pickup_latitude,
                $this->pickup_longitude,
                $this->drop_latitude,
                $this->drop_longitude
            );
        }

        // var_dump($this->drop_latitude);
        // var_dump($pickupDistance);exit;
      
        if($finalDistance !=null){
 $pickupDistance = $finalDistance;
        }else{
 $pickupDistance = ceil($pickupDistance['meters'] / 1000);
        }
       
        $cityRateCard = CityRateCard::findOne([
            'city_id' => $this->city_id,
            'ride_type' => $this->rider_type,
            'trip_type' => $this->trip_type,
        ]);

        // var_dump($cityRateCard);
        // exit();

        // Retrieve the necessary fields from the CityRateCard object
        $baseHour = isset($cityRateCard->base_hour)?$cityRateCard->base_hour:0;
        $baseKm = isset($cityRateCard->base_km)?$cityRateCard->base_km:0;
        $baseFare = isset($cityRateCard->base_fare)?$cityRateCard->base_fare:0;
        $extraKmCharge = isset($cityRateCard->extra_km_charge)? $cityRateCard->extra_km_charge:0;
        $extraMinCharge = isset($cityRateCard->extra_min_charge)?$cityRateCard->extra_min_charge:0;
        $nightCharge = isset($cityRateCard->night_charge)?$cityRateCard->night_charge:0;
        $outStationCharge = isset($cityRateCard->out_station_charge)?$cityRateCard->out_station_charge:0;
        

        // Calculate the estimate price
        $estimatePrice = $baseFare;

        $extraMinuteCharges = 0;
        // Add extra minute charges for usage hours beyond the base hours
        if ($usageHours > $baseHour) {
            $extraMinutes = ($usageHours - $baseHour) * 60;
            $extraMinuteCharges = $extraMinutes * $extraMinCharge;
            $estimatePrice += $extraMinuteCharges;
        }

        // Night charge: once for every night (22:00-06:00) the ride touches, so a ride of several days pays for each night.
        $nights = self::nightsTouched($pickupTime, $usageHours);
        $nightChargePerNight = $nightCharge;
        $nightCharge = $nightChargePerNight * $nights;
        $estimatePrice += $nightCharge;

        // Add outstation charge
        $estimatePrice += $outStationCharge;

        $additionalCharges = 0;
        // Add extra km charges if applicable
        if ($pickupDistance > $baseKm) {
            $additionalDistance = $pickupDistance - $baseKm;
            $additionalCharges = $additionalDistance * $extraKmCharge;
            $estimatePrice += $additionalCharges;
        }

        // Prepare the price details array
        $gst =  ceil(($estimatePrice + ( !empty($secured_fee) ? (int)$secured_fee->value:0) + ( !empty($platform_charges) ? (int)$platform_charges->value:0)) * ( !empty($gstPer) ? (((int)$gstPer->value)/100):1));
        $price = [
            'estimatePrice' => ceil($estimatePrice+$gst) + ( !empty($secured_fee) ? (int)$secured_fee->value:0) + ( !empty($platform_charges) ? (int)$platform_charges->value:0),
            'baseFare' => $baseFare,
            'baseHour' => $baseHour,
            'securedFee'=>$secured_fee->value,
            'platformCharges'=>$platform_charges->value,
            'gst' => $gst,
            'baseKm' => $baseKm,
            'extraKmCharge' => $extraKmCharge,
            'extraMinCharge' => $extraMinCharge,
            'nightCharge' => $nightCharge,
            'outStationCharge' => $outStationCharge,
            'additionalCharges' => ceil(
                $extraMinuteCharges + $additionalCharges
            ),
            'estimatedDistance' => ceil($pickupDistance),

            'isNightChargeApplicable' => $nights > 0,
            'nightCount' => $nights,
            'nightChargePerNight' => $nightChargePerNight,
            'usageHours' => $usageHours,
            'pickupDistance' =>$pickupDistance,

        ];

        return $price;
    }

    public function calculateIntercityRoundTripEstimate()
    {
        $secured_fee = WebSetting::find()->where(['setting_key' => 'secured_fee'])->one();
        $platform_charges = WebSetting::find()->where(['setting_key' => 'platform_charges'])->one();
        $gstPer = WebSetting::find()->where(['setting_key' => 'gst'])->one();
        // Retrieve the CityRateCard based on the selected city
        $cityRateCard = CityRateCard::findOne([
            'city_id' => $this->city_id,
            'ride_type' => $this->rider_type,
            'trip_type' => $this->trip_type,
        ]);

        // Retrieve the necessary fields from the CityRateCard object
        $baseHour = isset($cityRateCard->base_hour)?$cityRateCard->base_hour:0;
        $baseFare = isset($cityRateCard->base_fare)?$cityRateCard->base_fare:0;
        $extraMinCharge = isset($cityRateCard->extra_min_charge)?$cityRateCard->extra_min_charge:0;
        $nightCharge = isset($cityRateCard->night_charge)?$cityRateCard->night_charge:0;
        // $outStationCharge = isset($cityRateCard->out_station_charge)?$cityRateCard->out_station_charge:0;
        $outStationCharge = 0;

        // Get the pickup time and calculate the booking hour
        $pickupTime = strtotime($this->pickup_time);
        $bookingHour = date('H', $pickupTime);

        // Check if the booking hour is within the time range of 10pm to 6am
        $isNightTimeRange = $bookingHour >= 22 || $bookingHour < 6;

        // Calculate the usage hours
        $usageHours = $this->usage_hours;

        // Calculate the estimate price
        $estimatePrice = $baseFare;

        $extraMinuteCharges = 0;
        // Add extra minute charges for usage hours beyond the base hours
        if ($usageHours > $baseHour) {
            $extraMinutes = ($usageHours - $baseHour) * 60;
            $extraMinuteCharges = $extraMinutes * $extraMinCharge;
            $estimatePrice += $extraMinuteCharges;
        }

        // Night charge: once for every night (22:00-06:00) the trip touches, so a trip of several days pays for each night.
        $nights = self::nightsTouched($pickupTime, $usageHours);
        $estimatePrice += $nightCharge * $nights;
        $gst =  ceil(($estimatePrice + ( !empty($secured_fee) ? (int)$secured_fee->value:0) + ( !empty($platform_charges) ? (int)$platform_charges->value:0)) * ( !empty($gstPer) ? (((int)$gstPer->value)/100):1));
        $price['estimatePrice'] = $estimatePrice + $gst + ( !empty($secured_fee) ? (int)$secured_fee->value:0) + ( !empty($platform_charges) ? (int)$platform_charges->value:0);
        $price['baseFare'] = $baseFare;
        $price['baseHour'] = $baseHour;
        $price['securedFee'] = $secured_fee->value;
        $price['platformCharges'] = $platform_charges->value;
        $price['gst'] = $gst;
        $price['baseKm'] = 0;
        $price['extraKmCharge'] = 0;
        $price['extraMinCharge'] = $extraMinCharge;
        $price['nightCharge'] = $nightCharge * $nights;
        $price['nightCount'] = $nights;
        $price['nightChargePerNight'] = $nightCharge;
        $price['outStationCharge'] = $outStationCharge;
        $price['additionalCharges'] = ceil($extraMinuteCharges);
        $price['estimatedDistance'] = 0;

        $price['isNightChargeApplicable'] = $nights > 0;
        $price['usageHours'] = $usageHours;

        return $price;
    }

    // private function calculateIntercityRoundTripEstimate()
    // {
    //     $pickupTime = strtotime($this->pickup_time);
    //     $usageHours = $this->usage_hours;

    //     // Define the base charges and additional charges for round trip

    //     // $baseChargeDay = 500;
    //     // $baseChargeNight = 600;
    //     // $extraMinuteCharge = 20;
    //     // $extraMinuteChargeNight = 25;
    //     // $extraKmCharge = 20;
    //     // $extraKmChargeNight = 25;
    //     // $extraHourCharge = 200;
    //     // $outsideCityCharge = 200;
    //     // $outsideCityChargeNight = 500;

    //     $baseChargeDay = 500;
    //     $baseChargeNight = 600;
    //     $extraMinuteChargeDay = 20;
    //     $extraMinuteChargeNight = 25;
    //     $outsideCityChargeDay = 300;
    //     $outsideCityChargeNight = 500;
    //     $minUsageHoursDay = 4;
    //     $minUsageHoursNight = 1.5;

    //     // Check if the booking time is between 6:00 AM and 10:00 PM (daytime)
    //     $bookingHour = date('H', $pickupTime);
    //     $isBookingDaytime = $bookingHour >= 6 && $bookingHour <= 22;

    //     // Calculate the estimate based on the conditions for round trip
    //     if ($isBookingDaytime && $usageHours >= $minUsageHoursDay) {
    //         // Calculate the additional minute charges based on the usage hours
    //         $extraMinuteCharges =
    //             ($usageHours - $minUsageHoursDay) * 60 * $extraMinuteChargeDay;

    //         // Calculate the total estimate price (base charge + additional minute charges)
    //         $estimatePrice = $baseChargeDay + $extraMinuteCharges;

    //         // Check if the driver went outside of the city boundaries
    //         $isOutsideCity = true; // Add your own condition here based on your city boundaries

    //         // Add the outside city charge if applicable
    //         if ($isOutsideCity) {
    //             $estimatePrice += $outsideCityChargeDay;
    //         }

    //         // Return the estimate price for round trip (daytime)
    //         return $estimatePrice;
    //     } elseif (!$isBookingDaytime && $usageHours >= $minUsageHoursNight) {
    //         // Calculate the additional minute charges based on the usage hours
    //         $extraMinuteCharges =
    //             ($usageHours - $minUsageHoursNight) *
    //             60 *
    //             $extraMinuteChargeNight;

    //         // Calculate the total estimate price (base charge + additional minute charges)
    //         $estimatePrice = $baseChargeNight + $extraMinuteCharges;

    //         // Check if the driver went outside of the city boundaries
    //         $isOutsideCity = true; // Add your own condition here based on your city boundaries

    //         // Add the outside city charge if applicable
    //         if ($isOutsideCity) {
    //             $estimatePrice += $outsideCityChargeNight;
    //         }

    //         // Return the estimate price for round trip (nighttime)

    //         $price['estimatePrice'] = $estimatePrice;
    //         $price['baseChargeDay'] = $baseChargeDay;
    //         $price['baseChargeNight'] = $baseChargeNight;
    //         $price['extraMinuteChargeDay'] = $extraMinuteChargeDay;
    //         $price['extraMinuteChargeNight'] = $extraMinuteChargeNight;
    //         $price['outsideCityChargeDay'] = $outsideCityChargeDay;
    //         $price['outsideCityChargeNight'] = $outsideCityChargeNight;
    //         $price['minUsageHoursDay'] = $minUsageHoursDay;
    //         $price['minUsageHoursNight'] = $minUsageHoursNight;

    //         // Return the estimate price
    //         return $price;
    //     }

    //     // Return false if the conditions are not met
    //     return false;
    // }

    public function calculateOutstationRoundTripEstimate()
    {
        // Calculate the estimate for Outstation Round Trip trip type
        // Add your logic here

        // return $estimatePrice;

        $secured_fee = WebSetting::find()->where(['setting_key' => 'secured_fee'])->one();
        $platform_charges = WebSetting::find()->where(['setting_key' => 'platform_charges'])->one();
        $gstPer = WebSetting::find()->where(['setting_key' => 'gst'])->one();
        // Retrieve the CityRateCard based on the selected city
        $cityRateCard = CityRateCard::findOne([
            'city_id' => $this->city_id,
            'ride_type' => $this->rider_type,
            'trip_type' => $this->trip_type,
        ]);

        // Retrieve the necessary fields from the CityRateCard object
        $baseHour = isset($cityRateCard->base_hour)?$cityRateCard->base_hour:0;
        $baseFare = isset($cityRateCard->base_fare)?$cityRateCard->base_fare:0;
        $extraMinCharge = isset($cityRateCard->extra_min_charge)?$cityRateCard->extra_min_charge:0;
        $nightCharge = isset($cityRateCard->night_charge)?$cityRateCard->night_charge:0;
        $outStationCharge = isset($cityRateCard->out_station_charge)?$cityRateCard->out_station_charge:0;

        // Get the pickup time and calculate the booking hour
        $pickupTime = strtotime($this->pickup_time);
        $bookingHour = date('H', $pickupTime);

        // Check if the booking hour is within the time range of 10pm to 6am
        $isNightTimeRange = $bookingHour >= 22 || $bookingHour < 6;

        // Calculate the usage hours
        $usageHours = $this->usage_hours;

        // Calculate the estimate price
        $estimatePrice = $baseFare;

        $extraMinuteCharges = 0;
        // Add extra minute charges for usage hours beyond the base hours
        if ($usageHours > $baseHour) {
            $extraMinutes = ($usageHours - $baseHour) * 60;
            $extraMinuteCharges = $extraMinutes * $extraMinCharge;
            $estimatePrice += $extraMinuteCharges;
        }

        // Night charge: once for every night (22:00-06:00) the trip touches, so a trip of several days pays for each night.
        $nights = self::nightsTouched($pickupTime, $usageHours);
        $estimatePrice += $nightCharge * $nights;
         $estimatePrice += $outStationCharge;
         
        $gst =  ceil(($estimatePrice  + ( !empty($secured_fee) ? (int)$secured_fee->value:0) + ( !empty($platform_charges) ? (int)$platform_charges->value:0)) * ( !empty($gstPer) ? (((int)$gstPer->value)/100):1));
        $price['estimatePrice'] = $estimatePrice + $gst + ( !empty($secured_fee) ? (int)$secured_fee->value:0) + ( !empty($platform_charges) ? (int)$platform_charges->value:0);
        $price['baseFare'] = $baseFare;
        $price['baseHour'] = $baseHour;
        $price['securedFee'] = $secured_fee->value;
        $price['platformCharges'] = $platform_charges->value;
        $price['gst'] = $gst;
        $price['baseKm'] = 0;
        $price['extraKmCharge'] = 0;
        $price['extraMinCharge'] = $extraMinCharge;
        $price['nightCharge'] = $nightCharge * $nights;
        $price['nightCount'] = $nights;
        $price['nightChargePerNight'] = $nightCharge;
        $price['outStationCharge'] = $outStationCharge;
        $price['additionalCharges'] = ceil($extraMinuteCharges);
        $price['estimatedDistance'] = 0;

        $price['isNightChargeApplicable'] = $nights > 0;
        $price['usageHours'] = $usageHours;

        return $price;
    }
}