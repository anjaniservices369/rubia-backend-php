<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\RideStatuses as BaseRideStatuses;

/**
 * This is the model class for table "ride_statuses".
 */
class RideStatuses extends BaseRideStatuses
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_replace_recursive(parent::rules(),
	    [
            [['ride_id', 'message', 'status', 'created_on', 'updated_on', 'create_user_id', 'update_user_id'], 'required'],
            [['ride_id', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['message', 'created_on', 'updated_on'], 'string', 'max' => 255]
        ]);
    }
	

}
