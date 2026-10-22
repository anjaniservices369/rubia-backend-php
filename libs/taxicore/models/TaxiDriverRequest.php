<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\TaxiDriverRequest as BaseTaxiDriverRequest;

/**
 * This is the model class for table "taxi_driver_request".
 */
class TaxiDriverRequest extends BaseTaxiDriverRequest
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_replace_recursive(parent::rules(),
	    [
            [['ride_id', 'driver_id', 'update_user_id'], 'required'],
            [['ride_id', 'driver_id', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['created_on', 'updated_on'], 'safe']
        ]);
    }
	

}
