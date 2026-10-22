<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\DrivingLicense as BaseDrivingLicense;

/**
 * This is the model class for table "driving_license".
 */
class DrivingLicense extends BaseDrivingLicense
{
    public function rules()
    {
        return [
            [['driver_id', 'driving_license_front', 'driving_license_back', 'driving_license_number', 'issued_date', 'expire_date'], 'required'],
            [['driver_id', 'status', 'update_user_id', 'create_user_id'], 'integer'],
            [['issued_date', 'expire_date', 'updated_on', 'created_on'], 'safe'],
            [['driving_license_front','driving_license_back'], 'file', 'skipOnEmpty' => false, 'extensions' => 'png, jpg, jpeg'],
            [[ 'driving_license_number'], 'string', 'max' => 255]
            
        ];
    }

    

}
