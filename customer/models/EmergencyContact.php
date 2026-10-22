<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\EmergencyContact as BaseEmergencyContact;

/**
 * This is the model class for table "emergency_contact".
 */
class EmergencyContact extends BaseEmergencyContact
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_replace_recursive(parent::rules(),
	    [
            [['id', 'user_id', 'contact_name', 'contact_number', 'status', 'created_on', 'updated_on', 'create_user_id', 'update_user_id'], 'required'],
            [['id', 'user_id', 'status', 'created_on', 'updated_on'], 'integer'],
            [['create_user_id', 'update_user_id'], 'safe'],
            [['contact_name', 'contact_number'], 'string', 'max' => 255]
        ]);
    }
	

}
