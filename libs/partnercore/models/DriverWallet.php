<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\DriverWallet as BaseDriverWallet;

/**
 * This is the model class for table "driver_wallet".
 */
class DriverWallet extends BaseDriverWallet
{
  

    /**
     * Stamps the entry with the service of the driver it belongs to: driver-service accounts are module 1, taxi
     * accounts take the module of their registered vehicle type (bike / auto / taxi).
     */
    public function beforeSave($insert)
    {
        if (empty($this->module) && !empty($this->driver_id)) {
            $role = (new \yii\db\Query())->select('user_role')->from('user')->where(['id' => $this->driver_id])->scalar();
            if ($role === 'Driver') {
                $this->module = \app\modules\geo\services\ServiceModules::DRIVER;
            } elseif ($role === 'taxi') {
                $vehical = (new \yii\db\Query())->select('vehical_id')->from('driver_details')->where(['user_id' => $this->driver_id])->scalar();
                $this->module = $vehical ? \app\modules\geo\services\ServiceModules::ofVehicle($vehical) : null;
            }
        }

        return parent::beforeSave($insert);
    }
}
