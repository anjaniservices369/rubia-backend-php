<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\TaxiPayout as BaseTaxiPayout;

/**
 * This is the model class for table "taxi_payout".
 */
class TaxiPayout extends BaseTaxiPayout
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_replace_recursive(parent::rules(),
	    [
            [['driver_id', 'amount', 'payment_type', 'method_reason',  'status'], 'required'],
            [['driver_id', 'payment_type', 'type_id', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['amount'], 'number'],
            [['method_reason', 'created_on', 'updated_on'], 'string', 'max' => 255]
        ]);
    }
	

    /**
     * Payout / withdraw / cash-debit rows carry the service module of the driver they belong to, so each module
     * has its own ledger. The module is the one of the driver's registered vehicle type.
     */
    public function beforeSave($insert)
    {
        if (empty($this->module) && !empty($this->driver_id)) {
            $vehical = (new \yii\db\Query())->select('vehical_id')->from('driver_details')->where(['user_id' => $this->driver_id])->scalar();
            $this->module = $vehical ? \app\modules\geo\services\ServiceModules::ofVehicle($vehical) : null;
        }

        return parent::beforeSave($insert);
    }
}
