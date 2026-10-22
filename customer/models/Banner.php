<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\Banner as BaseBanner;

/**
 * This is the model class for table "banner".
 */
class Banner extends BaseBanner
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_replace_recursive(parent::rules(),
	    [
            [['banner_category_id', 'title'], 'required'],
            [['banner_category_id', 'type_id', 'sort_order', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['description'], 'string'],
            [['created_on', 'updated_on'], 'safe'],
            [['title', 'image'], 'string', 'max' => 255]
        ]);
    }
	

}
