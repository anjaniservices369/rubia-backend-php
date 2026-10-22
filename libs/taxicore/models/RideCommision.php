<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\RideCommision as BaseRideCommision;

/**
 * This is the model class for table "ride_commision".
 */
class RideCommision extends BaseRideCommision
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_replace_recursive(parent::rules(),
	    [
            [['city_id', 'vehicle_id', 'base_fare', 'min_distance', 'fare_per_distance', 'waiting_time_limit', 'charges_per_minute', 'fare_per_minute', 'no_of_person', 'commision', 'status', ], 'required'],
            [['city_id', 'vehicle_id', 'base_fare', 'commision', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['created_on', 'updated_on', 'create_user_id', 'update_user_id'], 'safe'],
            [['min_distance', 'fare_per_distance', 'waiting_time_limit', 'charges_per_minute', 'fare_per_minute', 'no_of_person'], 'number'],
            [['created_on', 'updated_on'], 'string', 'max' => 255]
        ]);
    }
	

}
