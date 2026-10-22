<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\Coupon as BaseCoupon;

/**
 * This is the model class for table "coupon".
 */
class Coupon extends BaseCoupon
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_replace_recursive(parent::rules(),
	    [
            [['name', 'description', 'code', 'discount', 'max_discount', 'min_ride_amount', 'max_use', 'max_use_of_coupon', 'start_date', 'end_date', 'is_global'], 'required'],
            [['description'], 'string'],
            [['discount', 'max_discount', 'min_ride_amount', 'max_use', 'max_use_of_coupon', 'is_global', 'status', 'type_id', 'create_user_id', 'update_user_id'], 'integer'],
            [['start_date', 'end_date', 'created_on', 'updated_on'], 'safe'],
            [['name', 'code'], 'string', 'max' => 255]
        ]);
    }
	

}
