<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[UserEmergencyContacts]].
 *
 * @see UserEmergencyContacts
 */
class UserEmergencyContactsQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return UserEmergencyContacts[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return UserEmergencyContacts|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
