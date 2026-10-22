<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\UserReferralEarnings as BaseUserReferralEarnings;

/**
 * This is the model class for table "user_referral_earnings".
 */
class UserReferralEarnings extends BaseUserReferralEarnings
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_replace_recursive(parent::rules(),
	    [
            [['user_id', 'earnings'], 'required'],
            [['user_id', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['earnings'], 'number'],
            [['created_on', 'updated_on'], 'safe']
        ]);
    }
	

    /**
     * A customer's referral reward belongs to the service of the request that earned it.
     */
    public function beforeSave($insert)
    {
        if (empty($this->module)) {
            $this->module = \app\modules\geo\services\ServiceModules::requested(\app\modules\geo\services\ServiceModules::DRIVER);
        }

        return parent::beforeSave($insert);
    }
}
