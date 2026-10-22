<?php
namespace app\modules\admin\forms;

use yii\base\Model;
use yii\web\UploadedFile;

class PanDetailsForm extends Model
{
    public $pan_number;
    public $pan_card;
    
    // Add these properties to handle file uploads
    public $frontFile;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['pan_number'], 'string', 'max' => 255],
            [['frontFile'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg'],
            // Add additional validation rules if needed
        ];
    }
}
