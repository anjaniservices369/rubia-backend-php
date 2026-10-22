<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\Page as BasePage;

/**
 * This is the model class for table "page".
 */
class Page extends BasePage
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_replace_recursive(parent::rules(),
	    [
            [['title', 'slug'], 'required'],
            [['description'], 'string'],
            [['state_id', 'type_id', 'create_user_id', 'updated_user_id'], 'integer'],
            [['created_date', 'updated_date'], 'safe'],
            [['title'], 'string', 'max' => 255],
            [['slug'], 'string', 'max' => 225]
        ]);
    }
	
}
