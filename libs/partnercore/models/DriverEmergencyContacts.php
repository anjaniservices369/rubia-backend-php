<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\DriverEmergencyContacts as BaseDriverEmergencyContacts;

/**
 * This is the model class for table "driver_emergency_contacts".
 */
class DriverEmergencyContacts extends BaseDriverEmergencyContacts
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_replace_recursive(parent::rules(),
	    [
            [['driver_id', 'name', 'phone_number', 'relationship'], 'required'],
            [['driver_id', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['created_on', 'updated_on'], 'safe'],
            [['name', 'relationship'], 'string', 'max' => 255],
            [['aadhar_number', 'phone_number'], 'string', 'max' => 12]
        ]);
    }
	

}
