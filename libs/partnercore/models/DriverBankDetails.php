<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\DriverBankDetails as BaseDriverBankDetails;

/**
 * This is the model class for table "driver_bank_details".
 */
class DriverBankDetails extends BaseDriverBankDetails
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_replace_recursive(parent::rules(),
	    [
            [['driver_id', 'account_holder_name', 'account_number', 'ifsc', 'bank_name', 'upi'], 'required'],
            [['driver_id', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['created_on', 'updated_on'], 'safe'],
            [['account_holder_name', 'account_number', 'bank_name', 'upi'], 'string', 'max' => 255],
            [['ifsc'], 'string', 'max' => 15]
        ]);
    }
	

}
