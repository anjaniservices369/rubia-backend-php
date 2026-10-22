<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\Auth as BaseAuth;

/**
 * This is the model class for table "auth".
 */
class Auth extends BaseAuth
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_replace_recursive(parent::rules(),
	    [
            [['user_id', 'source', 'source_id'], 'required'],
            [['user_id'], 'integer'],
            [['source', 'source_id'], 'string', 'max' => 255]
        ]);
    }
	
}
