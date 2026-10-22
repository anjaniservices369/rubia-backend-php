<?php
namespace app\modules\admin\forms;

use yii\base\Model;
use yii\web\UploadedFile;

class AdharDetailsForm extends Model
{
    public $aadhar_number;
    public $aadhar_card_front;
    public $aadhar_card_back;
    
    // Add these properties to handle file uploads
    public $frontFile;
    public $backFile;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['aadhar_number'], 'string', 'max' => 255],
            [['frontFile', 'backFile'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg'],
            // Add additional validation rules if needed
        ];
    }
}
