<?php
namespace app\modules\admin\forms;

use yii\base\Model;
use yii\web\UploadedFile;

class LicenseDetailsForm extends Model
{
    public $driving_license_number;
    public $issued_date;
    public $expire_date;
    public $frontFile;
    public $backFile;

    public function rules()
    {
        return [
            [['driving_license_number', 'issued_date', 'expire_date'], 'required'],
            [['driving_license_number'], 'string', 'max' => 255],
            [['issued_date', 'expire_date'], 'date', 'format' => 'php:Y-m-d'],
            [['frontFile', 'backFile'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg'],
        ];
    }

    public function loadAttributes($drivingLicenseModel)
    {
        $this->driving_license_number = $drivingLicenseModel->driving_license_number;
        $this->issued_date = $drivingLicenseModel->issued_date;
        $this->expire_date = $drivingLicenseModel->expire_date;
    }
}
