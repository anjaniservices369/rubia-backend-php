<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\Pincode as BasePincode;

/**
 * This is the model class for table "pincode".
 */
class Pincode extends BasePincode
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_replace_recursive(parent::rules(),
	    [
            [['city_id', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['name', 'vehical_id', ], 'required'],
            [['price'], 'number'],
            [['created_on', 'updated_on','price','vehical_id', ], 'safe'],
            [['name'], 'string', 'max' => 255]
        ]);
    }
	

}
