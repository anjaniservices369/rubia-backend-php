<?php

namespace app\modules\admin\models\base;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

/**
 * This is the base model class for table "ride_request".
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $driver_id
 * @property string $pickup_address
 * @property integer $pickup_pincode
 * @property string $pickup_latitude
 * @property integer $pickup_longitude
 * @property string $drop_address
 * @property integer $drop_pincode
 * @property string $drop_latitude
 * @property string $drop_longitude
 * @property integer $vehical_type
 * @property double $ride_fare
 * @property integer $coupon_code
 * @property double $coupon_discount
 * @property integer $coupon_applied_id
 * @property double $total_fare
 * @property integer $otp
 * @property integer $status
 * @property string $created_on
 * @property string $updated_on
 * @property integer $create_user_id
 * @property integer $update_user_id
 *
 * @property \app\modules\admin\models\User $user
 * @property \app\modules\admin\models\User $driver
 * @property \app\modules\admin\models\Vehicals $vehicalType
 */
class RideRequest extends \yii\db\ActiveRecord
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
            'driver',
            'driverDetail',
            'vehicalType',
            'rideCommision',
            'city'
        ];
    }

    public const STATUS_NEW_REQUEST = 1;
    public const STATUS_RIDE_BOOKED = 2;
    public const STATUS_CANCEL_BY_USER = 3;
    public const STATUS_ACCEPTED_BY_DRIVER = 4;
    /** @deprecated use STATUS_ACCEPTED_BY_DRIVER */
    public const STATUS_ACCEPTED_BY_SKIPPER = self::STATUS_ACCEPTED_BY_DRIVER;
    public const STATUS_ON_THE_WAY = 5;
    public const STATUS_ARRIVED = 6;
    public const STATUS_CANCEL_BY_DRIVER = 7;
    /** @deprecated use STATUS_CANCEL_BY_DRIVER */
    public const STATUS_CANCEL_BY_SKIPPER = self::STATUS_CANCEL_BY_DRIVER;
    public const STATUS_START_RIDE = 8;
    public const STATUS_RIDE_COMPLETED = 9;
    public const STATUS_RIDE_COMPLETED_PAID = 10;

    public const IS_FEATURED = 1;
    public const IS_NOT_FEATURED = 0;

    public const PAYMENT_METHOD_CASH = 1;
    public const PAYMENT_METHOD_PAY_ON_DROP = 2;
    public const PAYMENT_METHOD_WALLET = 3;
    public const PAYMENT_METHOD_ONLINE = 4;

    public const PAYMENT_STATUS_PAID = 1;
    public const PAYMENT_STATUS_PENDING = 2;
    public const PAYMENT_STATUS_FAILED = 3;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'driver_id', 'pickup_address', 'pickup_pincode', 'pickup_latitude', 'pickup_longitude', 'drop_address', 'drop_pincode', 'drop_latitude', 'drop_longitude', 'vehical_type', 'ride_fare', 'coupon_code', 'coupon_discount', 'coupon_applied_id', 'total_fare', 'otp', 'status', 'created_on', 'updated_on', 'create_user_id', 'update_user_id'], 'required'],
            [['user_id', 'driver_id', 'pickup_pincode', 'pickup_longitude', 'drop_pincode', 'vehical_type', 'coupon_code', 'coupon_applied_id', 'otp', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['ride_fare', 'coupon_discount', 'total_fare'], 'number'],
            [['created_on', 'updated_on'], 'safe'],
            [['pickup_address', 'pickup_latitude', 'drop_address', 'drop_latitude', 'drop_longitude'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ride_request';
    }



    public function getStateOptions()
    {
        return [
            self::STATUS_NEW_REQUEST => 'New Request',

            self::STATUS_RIDE_BOOKED => 'Ride Booked',
            self::STATUS_CANCEL_BY_USER => 'Cancel By User',
            self::STATUS_ACCEPTED_BY_DRIVER => 'Accepted By Driver',
            self::STATUS_ON_THE_WAY => 'On The Way',
            self::STATUS_ARRIVED => 'Arrived',
            self::STATUS_CANCEL_BY_DRIVER => 'Cancel By Driver',
            self::STATUS_START_RIDE => 'Start Ride',
            self::STATUS_RIDE_COMPLETED => 'Completed',
            self::STATUS_RIDE_COMPLETED_PAID => 'Completed And Paid',

        ];
    }
    public function getStateOptionsBadges()
    {
        if ($this->status == self::STATUS_NEW_REQUEST) {
            return '<span class="badge bg-primary">New Request</span>';
        } elseif ($this->status == self::STATUS_RIDE_BOOKED) {
            return '<span class="badge bg-primary">Ride booked</span>';
        } elseif ($this->status == self::STATUS_CANCEL_BY_USER) {
            return '<span class="badge bg-primary">Cancel By User</span>';
        } elseif ($this->status == self::STATUS_ACCEPTED_BY_DRIVER) {
            return '<span class="badge bg-primary">Accepted By Driver</span>';
        } elseif ($this->status == self::STATUS_ON_THE_WAY) {
            return '<span class="badge bg-primary">On The Way</span>';
        } elseif ($this->status == self::STATUS_ARRIVED) {
            return '<span class="badge bg-primary">Arrived</span>';
        } elseif ($this->status == self::STATUS_CANCEL_BY_DRIVER) {
            return '<span class="badge bg-primary">Cancel By Driver</span>';
        } elseif ($this->status == self::STATUS_START_RIDE) {
            return '<span class="badge bg-primary">Ride Started</span>';
        } elseif ($this->status == self::STATUS_RIDE_COMPLETED) {
            return '<span class="badge bg-primary">Ride Completed</span>';
        } elseif ($this->status == self::STATUS_RIDE_COMPLETED_PAID) {
            return '<span class="badge bg-primary">Completed and Paid</span>';
        }
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


    public function getPaymentMethod()
    {
        return [

            self::PAYMENT_METHOD_CASH => 'Cash',
            self::PAYMENT_METHOD_PAY_ON_DROP => 'Pay On Drop',
            self::PAYMENT_METHOD_WALLET => 'Wallet',

        ];
    }

    public function getPaymentMethodBadges()
    {
        if ($this->payment_method == self::PAYMENT_METHOD_CASH) {
            return '<span class="badge bg-info">Cash</span>';
        } elseif ($this->payment_method == self::PAYMENT_METHOD_PAY_ON_DROP) {
            return '<span class="badge bg-info">Pay On Drop</span>';
        } elseif ($this->payment_method == self::PAYMENT_METHOD_WALLET) {
            return '<span class="badge bg-info">Wallet</span>';
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'driver_id' => Yii::t('app', 'Driver ID'),
            'pickup_address' => Yii::t('app', 'Pickup Address'),
            'pickup_pincode' => Yii::t('app', 'Pickup Pincode'),
            'pickup_latitude' => Yii::t('app', 'Pickup Latitude'),
            'pickup_longitude' => Yii::t('app', 'Pickup Longitude'),
            'drop_address' => Yii::t('app', 'Drop Address'),
            'drop_pincode' => Yii::t('app', 'Drop Pincode'),
            'drop_latitude' => Yii::t('app', 'Drop Latitude'),
            'drop_longitude' => Yii::t('app', 'Drop Longitude'),
            'vehical_type' => Yii::t('app', 'Vehicle Type'),
            'ride_fare' => Yii::t('app', 'Ride Fare'),
            'coupon_code' => Yii::t('app', 'Coupon Code'),
            'coupon_discount' => Yii::t('app', 'Coupon Discount'),
            'coupon_applied_id' => Yii::t('app', 'Coupon Applied ID'),
            'total_fare' => Yii::t('app', 'Total Fare'),
            'otp' => Yii::t('app', 'Otp'),
            'status' => Yii::t('app', 'Status'),
            'created_on' => Yii::t('app', 'Created On'),
            'updated_on' => Yii::t('app', 'Updated On'),
            'create_user_id' => Yii::t('app', 'Create User ID'),
            'update_user_id' => Yii::t('app', 'Update User ID'),
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
        return $this->hasOne(\app\models\User::className(), ['id' => 'user_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDriver()
    {
        return $this->hasOne(\app\models\User::className(), ['id' => 'driver_id']);
    }

    public function getDriverDetail()
    {
        return $this->hasOne(DriverDetails::className(), ['user_id' => 'driver_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVehicalType()
    {
        return $this->hasOne(\app\modules\admin\models\Vehicals::className(), ['id' => 'vehical_type']);
    }

    public function getRideCommision()
    {
        return $this->hasOne(RideCommision::className(), ['city_id' => 'city_id']);
    }
    public function getCity()
    {
        return $this->hasOne(City::className(), ['id' => 'city_id']);
    }

    public function applyCoupon($couponCode, $rideId)
    {
        if (!empty($rideId)) {
            $rideRequest = RideRequest::find()->Where(['id' => $rideId])->one();

            $globalCoupon = Coupon::find()
                ->Where(['status' => Coupon::STATUS_ACTIVE, 'code' => $couponCode])
                ->one();
            if (empty($globalCoupon)) {
                $data['error'] = "Coupon does not exist";
                return $data;
            } else {
                //check for coupon Global or Store wise
                if ($globalCoupon['is_global'] == 1 || $globalCoupon['is_global'] == 0) {
                    $coupon = $globalCoupon;
                }
            }
            if ($coupon['min_ride_amount'] <= $rideRequest['estimated_ride_fare']) {
                if (!empty($coupon) && !empty($rideRequest)) {
                    // Check User already used or not
                    $check_apply1 = CouponsApplied::find()->where(['ride_id' => $rideId])
                        ->one();

                    if (empty($check_apply1)) {
                        // Check User already used or not
                        $check_user_count = CouponsApplied::find()->where([
                            'coupon_id' => $coupon['id'],
                            'user_id' => $rideRequest['user_id'],
                        ])->count();

                        $check_coupon_count = CouponsApplied::find()->where(
                            [
                                'coupon_id' => $coupon['id'],
                            ]
                        )->count();

                        //  echo  $check_user_count->createCommand()->getRawSql();exit;
                        //  var_dump( (int)$check_coupon_count < (int)$coupon->max_use_of_coupon);exit;
                        $today = Date('Y-m-d');
                        if ((int) $check_user_count < (int) $coupon->max_use && (int) $check_coupon_count < (int) $coupon->max_use_of_coupon) {
                            //Apply Discount to Cart
                            //$discount = ($coupon->discount / 100) * $cart->amount;
                            //       if($today >= $coupon['end_date'] && $today <= $coupon['start_date'] ){
                            $check_apply = new CouponsApplied();
                            $check_apply->ride_id = $rideRequest['id'];
                            $check_apply->coupon_id = $coupon['id'];
                            $check_apply->status = CouponsApplied::STATUS_ACTIVE;

                            if ($check_apply->save(false)) {
                                //Calculate Coupon Discount

                                if ($coupon['type_id'] == Coupon::PERCENTAGE) {
                                    $amt = ($coupon['discount'] / 100) * $rideRequest->estimated_ride_fare;
                                    if ($amt >= $coupon['max_discount']) {
                                        // $voucher_amount = $coupon['max_discount'];
                                        $voucher_amount = ($coupon['max_discount'] / 100) * $rideRequest->estimated_ride_fare;
                                    } else {
                                        $voucher_amount = $amt;
                                    }
                                } else {
                                    $voucher_amount =  $coupon['max_discount'];
                                }


                                //Update Cart
                                $rideRequest->coupon_applied_id = $check_apply->id;
                                $rideRequest->coupon_code = $couponCode;
                                $rideRequest->coupon_discount = $voucher_amount;
                                $rideRequest->save(false);

                                //$data['discount'] = $coupon->max_discount;
                                $data['coupon_apply_id'] = $check_apply->id;
                                $data['coupon_discount'] = $voucher_amount;
                                $data['coupon_details'] = $coupon;
                            }
                            // }else{
                            //     $data['status'] = self::API_NOK;
                            //     $data['details'] = array("Coupon Expired");
                            // }
                        } else {
                            $data['error'] = "Reached maximum usage of coupon";
                        }
                    } else {
                        $data['error'] = 'Coupon Already applied or this coupon does not work';
                    }
                } else {
                    $data['error'] = 'This coupon does not work for this store';
                }
            } else {
                $data['error'] = 'For applying coupon your cart value should be ₹' . $coupon['min_ride_amount'];
            }
        } else {
            $data['details'] = 'NO session found';
        }

        return $data;
    }

    public function getVehicles($pickup_pincode, $drop_pincode)
    {
        // var_dump($pickup_pincode);exit;
        $pickupPincode = Pincode::find()->where(['name' => $pickup_pincode])->andWhere(['status' => Pincode::STATUS_ACTIVE])->all();
        $dropPincode = Pincode::find()->where(['name' => $drop_pincode])->andWhere(['status' => Pincode::STATUS_ACTIVE])->all();


        if (!empty($pickupPincode) && !empty($dropPincode)) {


            $vehicalId = [];

            foreach ($pickupPincode as $pp) {
                $pvehicalId = $pp->vehical_id;
                foreach ($dropPincode as $dp) {

                    $dvehicalId = $dp->vehical_id;
                    if ($pvehicalId == $dvehicalId) {
                        $vehicalId[] = $dvehicalId;
                    }
                }
            }
            if ($pp->city_id == $dp->city_id) {
                if (!empty($vehicalId)) {
                    $details = $vehicalId;
                } else {
                    $details = "No Vehicles  Found";
                }
            } else {
                $details = "Can't travel to other city";
            }
            // $details = [];

            // var_dump($vehicalId);
            // exit;
        } else {
            $details = "Not servicable in this area";
        }

        return $details;
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
                'value' => new \yii\db\Expression('NOW()'),
            ],
            'blameable' => [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'create_user_id',
                'updatedByAttribute' => 'update_user_id',
            ],
        ];
    }


    // Update Status
    public function updateStatus($id, $status, $message)
    {
        // Adding Ride statuses//
        // var_dump($message);exit;
        $rideStatus = new RideStatuses();
        $rideStatus->ride_id =  $id;
        $rideStatus->status =  $status;
        $rideStatus->message =  $message;
        // var_dump($rideStatus->message);exit;
        $rideStatus->save(false);
    }



    /**
     * @inheritdoc
     * @return \app\modules\admin\models\RideRequestQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\admin\models\RideRequestQuery(get_called_class());
    }
    public function asJson()
    {
        $data = [];
        $data['id'] =  $this->id;

        $data['user']['id'] =  $this->user_id;
        $data['user']['name'] =  $this->customer['name'];
        $data['user']['profile_image'] =  $this->customer['image'] ?? '';
        $data['user']['contact_no'] =  $this->customer['phone'];
        if (!empty($this->driver)) {
            // The id of the driver (user), not of the ride; the ride id is $data['id'].
            $data['driver']['id'] =  $this->driver_id;
            $data['driver']['latitude'] = ($this->driver->lat !== null && $this->driver->lat !== '') ? (float) $this->driver->lat : '';
            $data['driver']['longitude'] = ($this->driver->lng !== null && $this->driver->lng !== '') ? (float) $this->driver->lng : '';
            $data['driver_id'] = (int) $this->driver_id;
            $data['driver']['name'] =  isset($this->driver->first_name) ? $this->driver->first_name : $this->driver->username;
            $data['driver']['profile_image'] =  isset($this->driver->profile_image) ? $this->driver->profile_image : $this->driver->profile_image;
            $data['driver']['contact_no'] =  isset($this->driver->contact_no) ? $this->driver->contact_no : $this->driver->contact_no;
            // $data['driver']['vehical'] =  isset($this->vehicalType->title) ? $this->driver->title : "";
            $data['driver']['vehical_number'] =  ($this->driverDetail->vehical_number ?? '');
        } else {
            $data['driver']['id'] =  "";
            $data['driver']['latitude'] = '';
            $data['driver']['longitude'] = '';
            $data['driver_id'] = 0;
            $data['driver']['name'] =  "";
            $data['driver']['profile_image'] =  "";
            $data['driver']['contact_no'] =  "";
            $data['driver']['vehical'] =  "";
            $data['driver']['vehical_number'] =  "";
        }

        $data['pickup_address'] =  $this->pickup_address;

        $data['pickup_pincode'] =  $this->pickup_pincode;

        $data['pickup_latitude'] =  $this->pickup_latitude;

        $data['pickup_longitude'] =  $this->pickup_longitude;

        $data['drop_address'] =  $this->drop_address;

        $data['drop_pincode'] =  $this->drop_pincode;

        $data['drop_latitude'] =  $this->drop_latitude;

        $data['drop_longitude'] =  $this->drop_longitude;

        $data['vehical_type'] =  $this->vehical_type;

        $data['ride_fare'] =  $this->estimated_ride_fare;

        $data['coupon_code'] =  $this->coupon_code;

        $data['coupon_discount'] =  $this->coupon_discount;

        $data['coupon_applied_id'] =  $this->coupon_applied_id;

        $data['total_fare'] =  $this->total_fare;

        $data['otp'] =  $this->otp;
        $data['payment_method'] =  $this->payment_method;

        $data['status'] =  $this->status;

        $data['created_on'] =  $this->created_on;

        $data['updated_on'] =  $this->updated_on;

        $data['create_user_id'] =  $this->create_user_id;

        $data['update_user_id'] =  $this->update_user_id;

        return $data;
    }

    // Taxi driver ride list

    /** @deprecated use asTaxiRideListJson() */
    public function asSkipperRideListJson()
    {
        return $this->asTaxiRideListJson();
    }

    public function asTaxiRideListJson()
    {
        $data = [];
        $data['id'] =  $this->id;

        $data['user']['id'] =  $this->user_id;
        $data['user']['username'] =  $this->customer['name'];
        $data['user']['contact_no'] =  $this->customer['phone'];


        $data['driver_id'] =  $this->driver_id;

        $data['pickup_address'] =  $this->pickup_address;

        $data['pickup_pincode'] =  $this->pickup_pincode;

        $data['pickup_latitude'] =  $this->pickup_latitude;

        $data['pickup_longitude'] =  $this->pickup_longitude;

        $data['drop_address'] =  $this->drop_address;

        $data['drop_pincode'] =  $this->drop_pincode;

        $data['drop_latitude'] =  $this->drop_latitude;

        $data['drop_longitude'] =  $this->drop_longitude;


        $data['total_fare'] =  $this->total_fare;


        $data['status'] =  $this->status;

        $data['created_on'] =  $this->created_on;

        $data['updated_on'] =  $this->updated_on;


        return $data;
    }


    /** @deprecated use myRidesTaxiJson() */
    public function MyRidesSkipperJson()
    {
        return $this->myRidesTaxiJson();
    }

    public function myRidesTaxiJson()
    {
        $data = [];

        $data['id'] =  $this->id;



        $data['pickup_address'] =  $this->pickup_address;

        $data['pickup_pincode'] =  $this->pickup_pincode;

        $data['drop_address'] =  $this->drop_address;

        $data['drop_pincode'] =  $this->drop_pincode;

        $data['payment_method'] =  $this->payment_method;

        $data['payment_status'] =  $this->payment_status;

        $data['final_price'] =  $this->final_price;

        $checkRejectLog = RejectLog::find()->where(['ride_id' => $this->id])->one();
        if(!empty($checkRejectLog))
        {
            $data['status'] =  RideRequest::STATUS_CANCEL_BY_DRIVER;
        }
        else
        {
            $data['status'] =  $this->status;
        }
        

        $data['updated_on'] =  $this->updated_on;

        $data['time'] =  date('h:i a', strtotime($this->updated_on));


        return $data;
    }

    // 

    public function MyRideUserJson()
    {
        $data = [];

        $data['id'] =  $this->id;

        $data['driver_id'] =  $this->driver_id;

        $data['vehicle']['name'] =  $this->driverDetail->vehical->title ?? '';
        $data['vehicle']['image'] =  $this->driverDetail->vehical->image ?? '';

        $data['pickup_address'] =  $this->pickup_address;

        $data['pickup_pincode'] =  $this->pickup_pincode;

        $data['drop_address'] =  $this->drop_address;

        $data['drop_pincode'] =  $this->drop_pincode;

        $data['payment_method'] =  $this->payment_method;

        $data['payment_status'] =  $this->payment_status;

        $data['final_price'] =  $this->final_price;

        $data['status'] =  $this->status;

        $data['updated_on'] =  $this->updated_on;

        $data['time'] =  date('h:i a', strtotime($this->updated_on));


        return $data;
    }
    // Ride request detail json for the taxi driver
    public function asRideRequestDetailJson()
    {
        $data = [];
        $data['id'] =  $this->id;

        $data['user']['id'] =  $this->user_id;
        $data['user']['username'] =  $this->customer['name'];


        $data['driver_id'] =  $this->driver_id;

        $data['pickup_address'] =  $this->pickup_address;

        $data['pickup_pincode'] =  $this->pickup_pincode;



        $data['drop_address'] =  $this->drop_address;

        $data['drop_pincode'] =  $this->drop_pincode;

        $data['final_distance'] =  $this->final_distance;

        $data['final_time'] =  $this->final_time;

        $data['coupon_code'] =  $this->coupon_code;

        $data['coupon_discount'] =  $this->coupon_discount;

        $data['coupon_applied_id'] =  $this->coupon_applied_id;

        $data['total_fare'] =  $this->total_fare;


        $data['status'] =  $this->status;

        $data['created_on'] =  $this->created_on;

        $data['updated_on'] =  $this->updated_on;

        $data['date-time'] =  date('h:i a d-M-Y', strtotime($this->updated_on));

        $rideEanings = RideEarnings::find()->where(['ride_id' => $data['id'] =  $this->id])->andWhere(['driver_id' => $this->driver_id])->one();
        if (!empty($rideEanings)) {
            $data['current_ride_earning'] = round($rideEanings->driver_earning, 2);
            $data['admin_charged'] = round($rideEanings->admin_earning, 2);
        } else {
            $data['current_ride_earning'] = 0;
            $data['admin_charged'] = 0;
        }
        $data['tax'] = 0;
        $data['platform_charge'] = 0;
        $data['transaction_fee'] = 0;
        $taxiRating = TaxiRating::find()->where(['ride_id' => $this->id])->one();

        if (!empty($taxiRating)) {
            $data['rating']['profile_image'] = $taxiRating->customer['image'] ?? '';

            if (!empty($taxiRating)) {
                $data['rating']['username'] =    $taxiRating->customer['name'];
                $data['rating']['profile_image'] =    $taxiRating->customer['image'] ?? '';
                $data['rating']['rating'] =    $taxiRating->rating;
            } else {
                $data['rating']['username'] =   "";
                $data['rating']['profile_image'] =    "";
                $data['rating']['rating'] =   0;
            }
        } else {
            $data['rating']['username'] =   "";
            $data['rating']['profile_image'] =    "";
            $data['rating']['rating'] =   0;
        }

        // $data['update_user_id'] =  $this->update_user_id;

        return $data;
    }

    // RIde Request Detail json for User
    public function asRideRequestDetailJsonUser()
    {
        $data = [];
        $data['id'] =  $this->id;

        $data['user']['id'] =  $this->user_id;
        $data['user']['username'] =  $this->customer['name'];

        $taxiRating = TaxiRating::find()->where(['ride_id' => $this->id])->one();
        $data['driver']['driver_id'] =  $this->driver_id;
        $data['driver']['name'] =  isset($this->driver->first_name) ? $this->driver->first_name : $this->driver->username;
        $data['driver']['profile_image'] =  isset($this->driver->profile_image) ? $this->driver->profile_image : "";
        $data['driver']['rating'] =    isset($taxiRating->rating) ? $taxiRating->rating : "";
        $data['vehicle']['name'] =  $this->driverDetail->vehical->title;
        $data['vehicle']['image'] =  $this->driverDetail->vehical->image;

        $data['pickup_address'] =  $this->pickup_address;

        $data['pickup_pincode'] =  $this->pickup_pincode;



        $data['drop_address'] =  $this->drop_address;

        $data['drop_pincode'] =  $this->drop_pincode;

        $data['final_distance'] =  $this->final_distance;

        $data['final_time'] =  $this->final_time;

        $data['coupon_code'] =  $this->coupon_code;

        $data['coupon_discount'] =  $this->coupon_discount;

        $data['coupon_applied_id'] =  $this->coupon_applied_id;

        $data['total_fare'] =  $this->total_fare;


        $data['status'] =  $this->status;

        $data['created_on'] =  $this->created_on;

        $data['updated_on'] =  $this->updated_on;

        $data['date-time'] =  date('h:i a d-M-Y', strtotime($this->updated_on));

        $rideEanings = RideEarnings::find()->where(['ride_id' => $data['id'] =  $this->id])->andWhere(['driver_id' => $this->driver_id])->one();
        if (!empty($rideEanings)) {
            $data['current_ride_earning'] = round($rideEanings->driver_earning, 2);
            $data['admin_charged'] = round($rideEanings->admin_earning, 2);
        } else {
            $data['current_ride_earning'] = 0;
            $data['admin_charged'] = 0;
        }


        $data['tax'] = 0;
        $data['platform_charge'] = 0;
        $data['transaction_fee'] = 0;
        $data['driver_rating'] = 3.5;
        // $data['update_user_id'] =  $this->update_user_id;

        return $data;
    }


    public function earningDetailsJson()
    {
        $data = [];
        $data['id'] =  $this->id;

        $price = RideCommision::find()->where(['city_id' => $this->city_id, 'vehicle_id' => \app\modules\geo\services\ServiceModules::ofVehicle($this->vehical_type), 'status' => RideCommision::STATUS_ACTIVE])->one();

        $rideEarning = RideEarnings::find()->where(['ride_id' => $this->id])->one();
        if (!empty($price)) {
            $data['base_fare'] = $price->base_fare;


            $data['distance_time_price'] = $this->final_price;
            $data['transaction_fee'] = $rideEarning->admin_earning;
            $data['total_fare'] = $rideEarning->driver_earning;
        } else {
            $data['base_fare'] = 0;
            $data['distance_time_price'] = 0;
            $data['transaction_fee'] = 0;
            $data['total_fare'] = 0;
        }
        return $data;
    }

    /**
     * Keeps the driver offers (taxi_driver_request) in step with the ride: once a driver accepts, the other
     * offers are withdrawn; a cancelled ride withdraws all pending offers.
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if (!$insert && array_key_exists('status', $changedAttributes)) {
            $this->syncOffers();
        }
    }

    /**
     * Brings the driver offers of this ride in line with its current status.
     */
    public function syncOffers()
    {
        $offer = \app\modules\admin\models\TaxiDriverRequest::class;
        $pending = ['ride_id' => $this->id, 'status' => $offer::RIDE_ASSIGNED];

        if ((int) $this->status === self::STATUS_ACCEPTED_BY_DRIVER && $this->driver_id) {
            $offer::updateAll(['status' => $offer::RIDE_ACCEPT], ['ride_id' => $this->id, 'driver_id' => $this->driver_id]);
            $offer::updateAll(['status' => $offer::RIDE_CANCELLED], $pending);
        } elseif (in_array((int) $this->status, [self::STATUS_CANCEL_BY_USER, self::STATUS_CANCEL_BY_DRIVER], true)) {
            $offer::updateAll(['status' => $offer::RIDE_CANCELLED], $pending);
        }
    }
}
