<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\RejectLog as BaseRejectLog;

/**
 * This is the model class for table "reject_log".
 */
class RejectLog extends BaseRejectLog
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_replace_recursive(parent::rules(),
	    [
            [['taxi_driver_id', 'ride_id', 'status', 'created_on', 'updated_on', 'create_user_id', 'update_user_id'], 'required'],
            [['taxi_driver_id', 'ride_id', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['created_on', 'updated_on'], 'safe']
        ]);
    }
	

}
