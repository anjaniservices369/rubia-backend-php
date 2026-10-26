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
        // Written out in full rather than merged with parent::rules(): array_replace_recursive matches
        // list entries by numeric index, so a shorter 'required' attribute list here would only mask the
        // front of the parent's and leave 'relationship' required underneath it.
        return [
            // name/relationship are optional: the customer app's emergency-contact form only collects phone_number.
            [['user_id', 'phone_number'], 'required'],
            [['user_id', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['created_on', 'updated_on'], 'safe'],
            [['name', 'relationship'], 'default', 'value' => ''],
            [['name', 'relationship'], 'string', 'max' => 255],
            [['phone_number'], 'string', 'max' => 12],
            ['user_id', 'exist', 'targetClass' => '\app\models\User', 'targetAttribute' => 'id'],
            [['user_id', 'name', 'phone_number', 'relationship'], 'safe'],
        ];
    }
	

}
