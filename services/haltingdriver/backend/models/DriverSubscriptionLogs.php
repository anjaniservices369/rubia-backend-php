<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\DriverSubscriptionLogs as BaseDriverSubscriptionLogs;

/**
 * This is the model class for table "driver_subscription_logs".
 */
class DriverSubscriptionLogs extends BaseDriverSubscriptionLogs
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_replace_recursive(parent::rules(),
	    [
            [['driver_id', 'plan_id', 'plan_price', 'validity_in_days', 'start_date', 'end_date'], 'required'],
            [['driver_id', 'plan_id', 'validity_in_days', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['plan_price'], 'number'],
            [['start_date', 'end_date', 'created_on', 'updated_on'], 'safe']
        ]);
    }
	

}
