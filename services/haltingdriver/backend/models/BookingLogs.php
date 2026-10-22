<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\BookingLogs as BaseBookingLogs;

/**
 * This is the model class for table "booking_logs".
 */
class BookingLogs extends BaseBookingLogs
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_replace_recursive(parent::rules(),
	    [
            [['id', 'booking_id', 'description'], 'required'],
            [['id', 'booking_id', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['description'], 'string'],
            [['created_on', 'updated_on'], 'safe']
        ]);
    }
	

}
