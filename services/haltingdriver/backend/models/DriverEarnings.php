<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\DriverEarnings as BaseDriverEarnings;

/**
 * This is the model class for table "driver_earnings".
 */
class DriverEarnings extends BaseDriverEarnings
{
   

    /**
     * Driver-service earnings are module 1 (the taxi modules use RideEarnings).
     */
    public function beforeSave($insert)
    {
        if (empty($this->module)) {
            $this->module = \app\modules\geo\services\ServiceModules::DRIVER;
        }

        return parent::beforeSave($insert);
    }

    /**
     * The invoice of the driver service for this booking is issued when its earnings are recorded.
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if ($insert && !empty($this->booking_id)) {
            \app\modules\billing\services\InvoiceService::forBooking($this->booking_id);
        }
    }
}
