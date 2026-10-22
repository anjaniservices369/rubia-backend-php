<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[BannerCategorys]].
 *
 * @see BannerCategorys
 */
class BannerCategorysQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return BannerCategorys[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return BannerCategorys|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
