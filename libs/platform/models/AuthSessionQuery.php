<?php

namespace app\modules\admin\models;

/**
 * This is the ActiveQuery class for [[AuthSession]].
 *
 * @see AuthSession
 */
class AuthSessionQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }*/

    /**
     * @inheritdoc
     * @return AuthSession[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return AuthSession|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
