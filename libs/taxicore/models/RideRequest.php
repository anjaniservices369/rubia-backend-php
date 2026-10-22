<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\RideRequest as BaseRideRequest;

/**
 * This is the model class for table "ride_request".
 */
class RideRequest extends BaseRideRequest
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_replace_recursive(parent::rules(),
	    [
            [['user_id', 'driver_id', 'pickup_address', 'pickup_pincode', 'pickup_latitude', 'pickup_longitude', 'drop_address', 'drop_pincode', 'drop_latitude', 'drop_longitude', 'vehical_type', 'ride_fare', 'coupon_code', 'coupon_discount', 'coupon_applied_id', 'total_fare', 'otp', 'status', 'created_on', 'updated_on', 'create_user_id', 'update_user_id'], 'required'],
            [['user_id', 'driver_id', 'pickup_pincode', 'pickup_longitude', 'drop_pincode', 'vehical_type', 'coupon_code', 'coupon_applied_id', 'otp', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['ride_fare', 'coupon_discount', 'total_fare'], 'number'],
            [['created_on', 'updated_on'], 'safe'],
            [['pickup_address', 'pickup_latitude', 'drop_address', 'drop_latitude', 'drop_longitude'], 'string', 'max' => 255]
        ]);
    }
}
