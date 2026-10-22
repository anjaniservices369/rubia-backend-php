<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\DriverDetails as BaseDriverDetails;

/**
 * This is the model class for table "driver_details".
 */
class DriverDetails extends BaseDriverDetails
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_replace_recursive(parent::rules(),
	    [
            [['user_id',  'address', 'license_no',  'vehical_id', 'rc_number', 'vehical_number',  'adhaar_number',  'is_verified', 'status', ], 'required'],
            [['user_id', 'city_id', 'vehical_id', 'is_verified', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['commission_percent'], 'number'],
            [['created_on', 'updated_on','created_on', 'updated_on', 'update_user_id', 'pan_number', 'pan_front', 'pan_back',], 'safe'],
            [['address', 'license_no', 'proof_of_license', 'proof_of_license_back', 'rc_number', 'vehical_number', 'rc_proof', 'rc_proof_back', 'adhaar_number', 'adhaar_front', 'adhaar_back', 'pan_number', 'pan_front', 'pan_back'], 'string', 'max' => 255],
            [['proof_of_license','proof_of_license_back','rc_proof', 'rc_proof_back','adhaar_front','adhaar_back', 'pan_front'], 'file'],
            [['proof_of_license','proof_of_license_back','rc_proof', 'rc_proof_back','adhaar_front','adhaar_back', 'pan_front'], 'required', 'on'=> 'create']
        ]);
    }
	

}
