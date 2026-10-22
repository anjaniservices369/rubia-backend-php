<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\CouponsApplied as BaseCouponsApplied;

/**
 * This is the model class for table "coupons_applied".
 */
class CouponsApplied extends BaseCouponsApplied
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_replace_recursive(parent::rules(),
	    [
            [['ride_id', 'coupon_id', 'status', 'type_id', 'create_user_id', 'update_user_id'], 'integer'],
            [['created_on', 'updated_on'], 'safe'],
            [['update_user_id'], 'required']
        ]);
    }
	

}
