<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[EmergencyContact]].
 *
 * @see EmergencyContact
 */
class EmergencyContactQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return EmergencyContact[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return EmergencyContact|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
