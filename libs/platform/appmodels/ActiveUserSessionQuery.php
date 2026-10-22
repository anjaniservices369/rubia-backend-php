<?php

namespace app\models;

use yii\db\ActiveQuery;
use yii\db\Query;

/**
 * Query for auth sessions that only sees the sessions of active accounts.
 *
 * A blocked, inactive or deleted account therefore looks logged out to every lookup of its auth_code (login
 * check, API calls, push tokens) at once. The condition is added when the SQL is built, so it survives callers
 * that replace the filter with ->where().
 */
class ActiveUserSessionQuery extends ActiveQuery
{
    private $accountsChecked = false;

    public function prepare($builder)
    {
        if (!$this->accountsChecked) {
            $this->accountsChecked = true;
            $this->andWhere([
                'in',
                '{{%auth_session}}.create_user_id',
                (new Query())->select('id')->from('{{%user}}')->where(['status' => User::STATUS_ACTIVE]),
            ]);
        }

        return parent::prepare($builder);
    }
}
