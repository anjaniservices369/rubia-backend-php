<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\FavouriteLocation as BaseFavouriteLocation;

/**
 * This is the model class for table "favourite_location".
 */
class FavouriteLocation extends BaseFavouriteLocation
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_replace_recursive(parent::rules(),
	    [
            [['user_id', 'pincode', 'status', 'update_user_id', 'create_user_id'], 'integer'],
            [['created_on', 'updated_on'], 'safe'],
            [['latitude', 'logitude', 'address_type', 'address'], 'string', 'max' => 255]
        ]);
    }
	

}
