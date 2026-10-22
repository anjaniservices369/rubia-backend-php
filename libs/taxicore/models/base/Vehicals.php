<?php


namespace app\modules\admin\models\base;

use app\modules\geo\services\DrivingDistance;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

/**
 * This is the base model class for table "vehicals".
 *
 * @property integer $id
 * @property string $title
 * @property string $image
 * @property double $base_fare
 * @property double $min_distance
 * @property double $fare_per_distance
 * @property double $waiting_time_limit
 * @property double $charges_per_minute
 * @property double $cancellation_fee
 * @property double $status
 * @property string $created_on
 * @property string $updated_on
 * @property integer $create_user_id
 * @property integer $update_user_id
 *
 * @property \app\modules\admin\models\Pincode[] $pincodes
 */
class Vehicals extends \yii\db\ActiveRecord
{
    use \mootensai\relation\RelationTrait;


    /**
     * This function helps \mootensai\relation\RelationTrait runs faster
     * @return array relation names of this model
     */
    public function relationNames()
    {
        return [
            'pincodes'
        ];
    }

    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_DELETE = 2;

    const IS_FEATURED = 1;
    const IS_NOT_FEATURED = 0;

    const FIXED = 1;
    const PERCENTAGE = 2;

    const BIKE = 4;
    const AUTO = 3;
    const TAXI = 2;
    const DRIVER = 1;
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title',], 'required'],
            [['base_fare', 'min_distance', 'fare_per_distance', 'waiting_time_limit', 'charges_per_minute', 'cancellation_fee', 'status'], 'number'],
            [['created_on', 'updated_on', 'create_user_id', 'update_user_id', 'admin_commison_type', 'admin_commison', 'base_fare', 'min_distance', 'fare_per_distance', 'waiting_time_limit', 'charges_per_minute', 'cancellation_fee', 'status', 'no_of_person', 'fare_per_minute'], 'safe'],
            [['create_user_id', 'update_user_id'], 'integer'],
            [['title'], 'string', 'max' => 255],

            [['image'], 'file'],
            [['image'], 'required', 'on' => 'create']
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'vehicals';
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

    public function getCommissionOptions()
    {
        return [

            self::FIXED => 'Fixed',
            self::PERCENTAGE => 'Percentage',

        ];
    }

    public function getCommissionOptionsBadges()
    {
        if ($this->admin_commison_type == self::FIXED) {
            return '<span class="badge text-bg-success">Fixed</span>';
        } elseif ($this->admin_commison_type == self::PERCENTAGE) {
            return '<span class="badge text-bg-danger">Percentage</span>';
        }
    }

    public static function getVehicleTypeOptions()
    {
        return [

            self::DRIVER => 'Driver',
            self::BIKE => 'Bike',
            self::AUTO => 'Auto',
            self::TAXI => 'Taxi',

        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'title' => Yii::t('app', 'Title'),
            'image' => Yii::t('app', 'Image'),
            'base_fare' => Yii::t('app', 'Base Fare'),
            'admin_commison_type' => Yii::t('app', 'Admin Commission Type'),
            'admin_commison' => Yii::t('app', 'Admin Commission'),
            'min_distance' => Yii::t('app', 'Min Distance'),
            'fare_per_distance' => Yii::t('app', 'Fare Per Distance'),
            'waiting_time_limit' => Yii::t('app', 'Waiting Time Limit'),
            'charges_per_minute' => Yii::t('app', 'Charges Per Minute'),
            'cancellation_fee' => Yii::t('app', 'Cancellation Fee'),
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
    public function getPincodes()
    {
        return $this->hasMany(\app\modules\admin\models\Pincode::className(), ['vehical_id' => 'id']);
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
     * @return \app\modules\admin\models\VehicalsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\admin\models\VehicalsQuery(get_called_class());
    }
    public function asJson($pickupLat = '', $pickupLng = '', $dropLat = '', $dropLng = '', $coupon_code = '', $user_id = '', $city_id = '')
    {
        $data = [];

        $settings = new WebSetting();
        $cgst = $settings->getSettingBykey('cgst');
        $sgst = $settings->getSettingBykey('sgst');

        $data['id'] =  $this->id;

        $data['title'] =  $this->title;

        $data['image'] =  $this->image;

        $data['status'] =  $this->status;

        $rideCommision = RideCommision::find()->where(['city_id' => $city_id, 'vehicle_id' => $this->vehicle_type, 'status' => RideCommision::STATUS_ACTIVE])->one();

        $data['no_of_person'] =  $rideCommision->no_of_person ?? '0';


        $data['created_on'] =  $this->created_on;

        $data['updated_on'] =  $this->updated_on;

        $data['create_user_id'] =  $this->create_user_id;

        $data['update_user_id'] =  $this->update_user_id;

        $drivingDistance = new DrivingDistance();
        $total_fare = 0;
        $data['driving_distance'] = $drivingDistance->getDrivingDistanceGoogleMap($pickupLat, $pickupLng, $dropLat, $dropLng);
        $drivingdistance = $drivingDistance->getDrivingDistanceGoogleMap($pickupLat, $pickupLng, $dropLat, $dropLng);
        // var_dump($drivingdistance);exit;
        $rideCommision = RideCommision::find()->where(['city_id' => $city_id, 'vehicle_id' => $this->vehicle_type, 'status' => RideCommision::STATUS_ACTIVE])->one();
        if (!empty($rideCommision)) {
            if (!empty($drivingdistance)) {
                $distInKm = $drivingdistance['distvalue'] / 1000;

                $distanceKm = $distInKm;

                // $dist = explode(' ', $distanceKm);
                //  var_dump($dist[0]);exit;
                $time = $drivingdistance['time'];
                $timeExp = explode(' ', $time);
                //  var_dump($time);exit;
                if ((float) $distanceKm > (float) $rideCommision->min_distance) {
                    $distance = (float) $distanceKm - $rideCommision->min_distance;
                } else {
                    $distance = 0;
                }

                // Time Cost
                $time_cost = (float)$timeExp[0] * $rideCommision->fare_per_minute;

                $distance_cost = (float) $rideCommision->fare_per_distance * $distance;
                // var_dump( $this->base_fare);exit;

                $total_fare += (float) $rideCommision->base_fare + (float) $distance_cost + $time_cost;
                // var_dump($total_fare);exit;

                $cgstAmount = ((float)$cgst * $total_fare) / 100;
                $sgstAmount = ((float)$sgst * $total_fare) / 100;
                $total_fare = $total_fare + $cgstAmount + $sgstAmount;

                $data['distance'] = $distanceKm;
                $data['distance_cost'] = round($distance_cost, 2);
                $data['time_cost'] = round($time_cost, 2);
                $data['gst']['cgst_perce'] = $cgst;
                $data['gst']['cgst_amount'] = $cgstAmount;
                $data['gst']['sgst_perce'] = $sgst;
                $data['gst']['sgst_amount'] = $sgstAmount;
                if (!empty($coupon_code)) {
                    $totFare = (new Coupon())->applyCoupon($coupon_code, $total_fare, $user_id);
                    if ($totFare["status"] == "NOK") {
                        $data['coupon_code'] =  NULL;
                        $data['error'] =  $totFare['error'];
                        $data['total_fare'] = ceil($total_fare);
                    } else {
                        $data['coupon_code'] =  $coupon_code;
                        $data['error'] =  NULL;

                        $data['total_fare'] =  ceil($totFare["estimated_fare"]);
                    }
                } else {
                    $data['coupon_code'] =  NULL;
                    $data['total_fare'] = ceil($total_fare);
                }
            }
        } else {
        }



        return $data;
    }


    // Vehicles list only

    public function asVehicleListJson()
    {
        $data = [];
        $data['id'] =  $this->id;

        $data['title'] =  $this->title;

        $data['image'] =  $this->image;

        $data['base_fare'] =  $this->base_fare;

        $data['min_distance'] =  $this->min_distance;

        $data['fare_per_distance'] =  $this->fare_per_distance;

        $data['waiting_time_limit'] =  $this->waiting_time_limit;

        $data['charges_per_minute'] =  $this->charges_per_minute;

        $data['cancellation_fee'] =  $this->cancellation_fee;
        // $data['no_of_person'] =  $this->no_of_person;

        $data['status'] =  $this->status;

        $data['created_on'] =  $this->created_on;

        $data['updated_on'] =  $this->updated_on;

        $data['create_user_id'] =  $this->create_user_id;

        $data['update_user_id'] =  $this->update_user_id;




        return $data;
    }
}
