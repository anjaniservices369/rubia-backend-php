<?php
namespace app\modules\admin\models\base;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

 
class States extends \yii\db\ActiveRecord
{

    public static function tableName()
    {
        return 'states';
    }

}
