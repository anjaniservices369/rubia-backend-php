<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[Pincode]].
 *
 * @see Pincode
 */
class PincodeQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return Pincode[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Pincode|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
