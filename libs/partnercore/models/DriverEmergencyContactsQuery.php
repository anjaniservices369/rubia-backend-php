<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[DriverEmergencyContacts]].
 *
 * @see DriverEmergencyContacts
 */
class DriverEmergencyContactsQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    /**
     * @inheritdoc
     * @return DriverEmergencyContacts[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return DriverEmergencyContacts|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
