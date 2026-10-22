<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\UserEmergencyContacts as BaseUserEmergencyContacts;

/**
 * This is the model class for table "user_emergency_contacts".
 */
class UserEmergencyContacts extends BaseUserEmergencyContacts
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_replace_recursive(parent::rules(),
	    [
            [['user_id', 'name', 'phone_number', 'relationship'], 'required'],
            [['user_id', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['created_on', 'updated_on'], 'safe'],
            [['name', 'relationship'], 'string', 'max' => 255],
            [['phone_number'], 'string', 'max' => 12]
        ]);
    }
	

}
