<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\DriverPanCards as BaseDriverPanCards;

/**
 * This is the model class for table "driver_pan_cards".
 */
class DriverPanCards extends BaseDriverPanCards
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_replace_recursive(parent::rules(),
	    [
            [['driver_id', 'pan_number', 'pan_card'], 'required'],
            [['driver_id', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['pan_card'], 'file', 'skipOnEmpty' => false, 'extensions' => 'png, jpg, jpeg'],
            [['created_on', 'updated_on'], 'safe'],
            [['pan_number'], 'string', 'max' => 10]
        ]);
    }
	

}
