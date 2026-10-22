<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[WebSetting]].
 *
 * @see WebSetting
 */
class WebSettingQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }*/

    /**
     * @inheritdoc
     * @return WebSetting[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return WebSetting|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
