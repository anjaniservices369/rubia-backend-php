<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\DriverReferralEarnings as BaseDriverReferralEarnings;

/**
 * This is the model class for table "driver_referral_earnings".
 */
class DriverReferralEarnings extends BaseDriverReferralEarnings
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_replace_recursive(parent::rules(),
	    [
            [['id', 'driver_id', 'earnings'], 'required'],
            [['id', 'driver_id', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['earnings'], 'number'],
            [['created_on', 'updated_on'], 'safe']
        ]);
    }
	

    /**
     * The reward belongs to the service of the referring driver's account.
     */
    public function beforeSave($insert)
    {
        if (empty($this->module) && !empty($this->driver_id)) {
            $role = (new \yii\db\Query())->select('user_role')->from('user')->where(['id' => $this->driver_id])->scalar();
            if ($role === 'Driver') {
                $this->module = \app\modules\geo\services\ServiceModules::DRIVER;
            } elseif ($role === 'taxi') {
                $v = (new \yii\db\Query())->select('vehical_id')->from('driver_details')->where(['user_id' => $this->driver_id])->scalar();
                $this->module = $v ? \app\modules\geo\services\ServiceModules::ofVehicle($v) : null;
            }
        }

        return parent::beforeSave($insert);
    }
}
