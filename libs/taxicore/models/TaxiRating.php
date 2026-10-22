<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\TaxiRating as BaseTaxiRating;

/**
 * This is the model class for table "taxi_rating".
 */
class TaxiRating extends BaseTaxiRating
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_replace_recursive(parent::rules(),
	    [
            [['taxi_driver_id', 'user_id', 'ride_id', 'rating', 'status', 'created_on', 'updated_on', 'create_user_id', 'update_user_id'], 'required'],
            [['taxi_driver_id', 'user_id', 'ride_id', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['rating'], 'number'],
            [['created_on', 'updated_on'], 'safe']
        ]);
    }
	

}
