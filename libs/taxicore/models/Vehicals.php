<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\Vehicals as BaseVehicals;

/**
 * This is the model class for table "vehicals".
 */
class Vehicals extends BaseVehicals
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_replace_recursive(parent::rules(),
	    [
            [['title', ], 'required'],
            [['base_fare', 'min_distance', 'fare_per_distance', 'waiting_time_limit', 'charges_per_minute', 'cancellation_fee', 'status'], 'number'],
            [['created_on', 'updated_on', 'create_user_id', 'update_user_id', 'admin_commison_type', 'admin_commison', 'base_fare', 'min_distance', 'fare_per_distance', 'waiting_time_limit', 'charges_per_minute', 'cancellation_fee', 'status', 'no_of_person', 'fare_per_minute'], 'safe'],
            [['create_user_id', 'update_user_id'], 'integer'],
            [['title'], 'string', 'max' => 255],

            [['image'], 'file'],
            [['image'], 'required', 'on' => 'create']
        ]);
    }
	

}
