<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\RideEarnings as BaseRideEarnings;

/**
 * This is the model class for table "ride_earnings".
 */
class RideEarnings extends BaseRideEarnings
{

    /**
     * Every earnings row says which service module (bike, auto, taxi) it belongs to, so each module is billed on
     * its own: the module of the ride's vehicle type.
     */
    public function beforeSave($insert)
    {
        if (empty($this->module) && !empty($this->ride_id)) {
            $vehicle = (new \yii\db\Query())->select('vehical_type')->from('ride_request')->where(['id' => $this->ride_id])->scalar();
            $this->module = $vehicle ? \app\modules\geo\services\ServiceModules::ofVehicle($vehicle) : null;
        }

        return parent::beforeSave($insert);
    }

    /**
     * The invoice of the taxi / auto / bike service for a ride is issued when its earnings are approved (paid).
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if ((int) $this->status === self::STATUS_APPROVED && !empty($this->ride_id)) {
            \app\modules\billing\services\InvoiceService::forRide($this->ride_id);
        }
    }
}
