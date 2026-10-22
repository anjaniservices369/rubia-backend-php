<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\DriverAadharCard as BaseDriverAadharCard;
use yii\web\UploadedFile;
/**
 * This is the model class for table "driver_aadhar_card".
 */
class DriverAadharCard extends BaseDriverAadharCard
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['driver_id', 'aadhar_card_front', 'aadhar_card_back', 'aadhar_number'], 'required'],
            [['driver_id', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['aadhar_card_front', 'aadhar_card_back'], 'file', 'skipOnEmpty' => false, 'extensions' => 'png, jpg, jpeg'],
            [['created_on', 'updated_on'], 'safe'],
            [['aadhar_number'], 'string', 'max' => 12],
        ]);
    }
    
   
	
}
