<?php


namespace app\modules\admin\models\base;

use app\modules\geo\services\DrivingDistance;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

/**
 * This is the base model class for table "ride_completion_log".
 *
 * @property integer $id
 * @property integer $ride_id
 * @property integer $driver_id
 * @property integer $user_id
 * @property integer $vehicle_id
 * @property double $estimated_price
 * @property double $estimated_distance
 * @property double $estimated_time
 * @property double $final_price
 * @property double $final_distance
 * @property double $final_time
 * @property string $pickup_time
 * @property string $pickup_lat
 * @property string $pickup_lng
 * @property string $drop_time
 * @property string $final_drop_lat
 * @property string $final_drop_lng
 * @property string $couponn_code
 * @property integer $coupon_applied_id
 * @property double $coupon_discount
 * @property double $gst_percentage
 * @property double $gst
 * @property double $service_charge_percentage
 * @property double $service_charge
 * @property integer $payment_method
 * @property integer $payment_status
 * @property integer $status
 * @property string $created_on
 * @property string $updated_on
 * @property integer $create_user_id
 * @property integer $update_user_id
 *
 * @property \app\modules\admin\models\User $user
 * @property \app\modules\admin\models\DriverDetails $driver
 * @property \app\modules\admin\models\Vehicals $vehicle
 */
class RideCompletionLog extends \yii\db\ActiveRecord
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
            'vehicle',
            'ride'
        ];
    }

    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_DELETE = 2;

    const IS_FEATURED = 1;
    const IS_NOT_FEATURED = 0;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['ride_id', 'driver_id', 'user_id', 'vehicle_id', 'estimated_price', 'estimated_distance', 'estimated_time', 'final_price', 'final_distance', 'final_time', 'pickup_time', 'pickup_lat', 'pickup_lng', 'drop_time', 'final_drop_lat', 'final_drop_lng', 'couponn_code', 'coupon_applied_id', 'coupon_discount', 'gst_percentage', 'gst', 'service_charge_percentage', 'service_charge', 'payment_method', 'payment_status', 'status', 'created_on', 'updated_on', 'create_user_id', 'update_user_id'], 'required'],
            [['ride_id', 'driver_id', 'user_id', 'vehicle_id', 'coupon_applied_id', 'payment_method', 'payment_status', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['estimated_price', 'estimated_distance', 'estimated_time', 'final_price', 'final_distance', 'final_time', 'coupon_discount', 'gst_percentage', 'gst', 'service_charge_percentage', 'service_charge'], 'number'],
            [['pickup_time', 'drop_time', 'created_on', 'updated_on'], 'safe'],
            [['pickup_lat', 'pickup_lng', 'final_drop_lat', 'final_drop_lng', 'couponn_code'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ride_completion_log';
    }

    public function getStateOptions()
    {
        return [
            self::STATUS_ACTIVE => 'Active',

            self::STATUS_INACTIVE => 'In Active',
            self::STATUS_DELETE => 'Deleted',

        ];
    }
    public function getStateOptionsBadges()
    {

        if ($this->status == self::STATUS_ACTIVE) {
            return '<span class="badge text-bg-success">Active</span>';
        } elseif ($this->status == self::STATUS_INACTIVE) {
            return '<span class="badge text-bg-warning">In Active</span>';
        } elseif ($this->status == self::STATUS_DELETE) {
            return '<span class="badge text-bg-danger">Deleted</span>';
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

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'ride_id' => Yii::t('app', 'Ride ID'),
            'driver_id' => Yii::t('app', 'Driver ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'vehicle_id' => Yii::t('app', 'Vehicle ID'),
            'estimated_price' => Yii::t('app', 'Estimated Price'),
            'estimated_distance' => Yii::t('app', 'Estimated Distance'),
            'estimated_time' => Yii::t('app', 'Estimated Time'),
            'final_price' => Yii::t('app', 'Final Price'),
            'final_distance' => Yii::t('app', 'Final Distance'),
            'final_time' => Yii::t('app', 'Final Time'),
            'pickup_time' => Yii::t('app', 'Pickup Time'),
            'pickup_lat' => Yii::t('app', 'Pickup Lat'),
            'pickup_lng' => Yii::t('app', 'Pickup Lng'),
            'drop_time' => Yii::t('app', 'Drop Time'),
            'final_drop_lat' => Yii::t('app', 'Final Drop Lat'),
            'final_drop_lng' => Yii::t('app', 'Final Drop Lng'),
            'couponn_code' => Yii::t('app', 'Couponn Code'),
            'coupon_applied_id' => Yii::t('app', 'Coupon Applied ID'),
            'coupon_discount' => Yii::t('app', 'Coupon Discount'),
            'gst_percentage' => Yii::t('app', 'Gst Percentage'),
            'gst' => Yii::t('app', 'Gst'),
            'service_charge_percentage' => Yii::t('app', 'Service Charge Percentage'),
            'service_charge' => Yii::t('app', 'Service Charge'),
            'payment_method' => Yii::t('app', 'Payment Method'),
            'payment_status' => Yii::t('app', 'Payment Status'),
            'status' => Yii::t('app', 'Status'),
            'created_on' => Yii::t('app', 'Created On'),
            'updated_on' => Yii::t('app', 'Updated On'),
            'create_user_id' => Yii::t('app', 'Create User ID'),
            'update_user_id' => Yii::t('app', 'Update User ID'),
        ];
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
    public function getDriverDetail()
    {
        return $this->hasOne(\app\modules\admin\models\DriverDetails::className(), ['user_id' => 'driver_id']);
    }

    public function getDriver()
    {
        return $this->hasOne(\app\models\User::className(), ['id' => 'driver_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVehicle()
    {
        return $this->hasOne(\app\modules\admin\models\Vehicals::className(), ['id' => 'vehicle_id']);
    }
    public function getRide()
    {
        return $this->hasOne(\app\modules\admin\models\RideRequest::className(), ['id' => 'ride_id']);
    }


    public function saveCompletionLog($ride_id, $final_lat = '', $final_lng = '', $final_time = '', $pickup_timne = '', $final_price = '', $final_dist = '')
    {
        $check = RideCompletionLog::find()->where(['ride_id' => $ride_id])->one();
        $rideRequest = RideRequest::find()->where(['id' => $ride_id])->one();

        if (empty($check)) {

            $rideCompletionLog = new RideCompletionLog();



            $rideCompletionLog->ride_id = $ride_id;
            $rideCompletionLog->driver_id = $rideRequest->driver_id;
            $rideCompletionLog->user_id = $rideRequest->user_id;
            $rideCompletionLog->vehicle_id = $rideRequest->vehical_type;
            $rideCompletionLog->estimated_price = $rideRequest->estimated_ride_fare;
            $rideCompletionLog->estimated_distance = $rideRequest->estimated_distance;
            $rideCompletionLog->estimated_time = $rideRequest->estimated_time;
            $rideCompletionLog->final_price = $final_price;
            $rideCompletionLog->final_distance = $final_dist;
            $rideCompletionLog->final_time = $rideRequest->final_time;
            $rideCompletionLog->pickup_time = $pickup_timne;
            $rideCompletionLog->pickup_lat = $rideRequest->pickup_latitude;
            $rideCompletionLog->pickup_lng = $rideRequest->pickup_longitude;
            $rideCompletionLog->drop_time = $final_time;
            $rideCompletionLog->final_drop_lat = $final_lat;
            $rideCompletionLog->final_drop_lng = $final_lng;
            $rideCompletionLog->couponn_code = $rideRequest->coupon_code;
            $rideCompletionLog->coupon_applied_id = $rideRequest->coupon_applied_id;
            $rideCompletionLog->coupon_discount = $rideRequest->coupon_applied_id;
            $rideCompletionLog->gst_percentage = "";
            $rideCompletionLog->gst = $rideRequest->cgst + $rideRequest->sgst;
            $rideCompletionLog->service_charge_percentage = "";
            $rideCompletionLog->service_charge = "";
            $rideCompletionLog->payment_method = $rideRequest->payment_method;
            $rideCompletionLog->payment_status = $rideRequest->payment_status;
            $rideCompletionLog->status = RideCompletionLog::STATUS_ACTIVE;
        } else {

            $rideCompletionLog = RideCompletionLog::find()->where(['ride_id' => $ride_id])->one();
            $rideCompletionLog->ride_id = $ride_id;
            $rideCompletionLog->driver_id = $rideRequest->driver_id;
            $rideCompletionLog->user_id = $rideRequest->user_id;
            $rideCompletionLog->vehicle_id = $rideRequest->vehical_type;
            $rideCompletionLog->estimated_price = $rideRequest->estimated_ride_fare;
            $rideCompletionLog->estimated_distance = $rideRequest->estimated_distance;
            $rideCompletionLog->estimated_time = $rideRequest->estimated_time;
            $rideCompletionLog->final_price = $final_price;
            $rideCompletionLog->final_distance = $final_dist;
            $rideCompletionLog->final_time = $rideRequest->final_time;
            $rideCompletionLog->pickup_time = $pickup_timne;
            $rideCompletionLog->pickup_lat = $rideRequest->pickup_latitude;
            $rideCompletionLog->pickup_lng = $rideRequest->pickup_longitude;
            $rideCompletionLog->drop_time = date('Y-m-d H:i:s');
            $rideCompletionLog->final_drop_lat = $final_lat;
            $rideCompletionLog->final_drop_lng = $final_lng;
            $rideCompletionLog->couponn_code = $rideRequest->coupon_code;
            $rideCompletionLog->coupon_applied_id = $rideRequest->coupon_applied_id;
            $rideCompletionLog->coupon_discount = $rideRequest->coupon_applied_id;
            $rideCompletionLog->gst_percentage = "";
            $rideCompletionLog->gst = $rideRequest->cgst + $rideRequest->sgst;
            $rideCompletionLog->service_charge_percentage = "";
            $rideCompletionLog->service_charge = "";
            $rideCompletionLog->payment_method = $rideRequest->payment_method;
            $rideCompletionLog->payment_status = $rideRequest->payment_status;
            $rideCompletionLog->status = RideCompletionLog::STATUS_ACTIVE;
        }
        $rideCompletionLog->save(false);
        return 0;
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



    /**
     * @inheritdoc
     * @return \app\modules\admin\models\RideCompletionLogQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\admin\models\RideCompletionLogQuery(get_called_class());
    }
    public function asJson()
    {
        // var_dump(date('Y-m-d H:i:s'));exit;
        $data = [];
        $data['id'] =  $this->id;

        $data['ride_id'] =  $this->ride_id;

        $data['driver']['id'] =  $this->driver_id;
        $data['driver']['name'] =  $this->driver->first_name ?? "";
        $data['driver']['contact_no'] =  $this->driver->contact_no ?? "";

        $data['user']['id'] =  $this->user_id;
        $data['user']['name'] =  \app\modules\customerapi\services\CustomerClient::profileCached($this->user_id)['name'];
        $data['user']['contact_no'] =  \app\modules\customerapi\services\CustomerClient::profileCached($this->user_id)['phone'];

        $data['vehicle_id'] =  !empty($this->vehicle->title)?$this->vehicle->title:'';

        $data['estimated_price'] =  $this->estimated_price;

        $data['estimated_distance'] =  $this->estimated_distance;

        $data['estimated_time'] =  $this->estimated_time;

        $data['final_price'] =  $this->final_price;

        $data['final_distance'] =  $this->final_distance;

        $data['final_time'] =  $this->final_time;

        $data['pickup_time'] =  $this->pickup_time;

        $data['pickup_lat'] =  $this->pickup_lat;

        $data['pickup_lng'] =  $this->pickup_lng;

        $data['drop_time'] =  date('Y-m-d H:i:s');

        $data['final_drop_lat'] =  $this->final_drop_lat;

        $data['final_drop_lng'] =  $this->final_drop_lng;

        $data['couponn_code'] =  $this->couponn_code;

        $data['coupon_applied_id'] =  $this->coupon_applied_id;

        $data['coupon_discount'] =  $this->coupon_discount;

        $data['gst_percentage'] =  $this->gst_percentage;

        $data['gst'] =  $this->gst;

        $data['service_charge_percentage'] =  $this->service_charge_percentage;

        $data['service_charge'] =  $this->service_charge;

        $data['payment_method'] =  $this->payment_method;

        $data['payment_status'] =  $this->payment_status;

        $data['status'] =  $this->status;

        $data['created_on'] =  $this->created_on;

        $data['updated_on'] =  $this->drop_time;

        $data['create_user_id'] =  $this->create_user_id;

        $data['update_user_id'] =  $this->update_user_id;

        return $data;
    }
}
