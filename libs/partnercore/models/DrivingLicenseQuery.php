<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[DrivingLicense]].
 *
 * @see DrivingLicense
 */
class DrivingLicenseQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return DrivingLicense[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return DrivingLicense|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
