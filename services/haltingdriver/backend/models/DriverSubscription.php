<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\DriverSubscription as BaseDriverSubscription;

/**
 * This is the model class for table "driver_subscription".
 */
class DriverSubscription extends BaseDriverSubscription
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_replace_recursive(parent::rules(),
	    [
            [['driver_id', 'plan_id', 'start_date', 'end_date'], 'required'],
            [['driver_id', 'plan_id', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['start_date', 'end_date', 'created_on', 'updated_on'], 'safe']
        ]);
    }
	

}
