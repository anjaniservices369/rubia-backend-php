<?php

namespace app\validators;

use yii\validators\Validator;

/**
 * Minimum password policy applied everywhere a password is created or changed: registration, the forgot-
 * password reset, an admin setting another account's password, an admin editing their own profile, and the
 * console command that bootstraps a super admin. Before this validator, each of those forms either checked
 * nothing beyond "not empty" or (in one case) just a bare 6-character minimum; centralizing it here means
 * there is one policy to change instead of six places that can quietly drift apart.
 */
class PasswordStrengthValidator extends Validator
{
    /**
     * @var int
     */
    public $minLength = 8;

    public function init()
    {
        parent::init();
        if ($this->message === null) {
            $this->message = 'Password must be at least {min} characters long and include at least one letter and one number.';
        }
    }

    /**
     * @inheritdoc
     */
    protected function validateValue($value)
    {
        if (!is_string($value) || mb_strlen($value) < $this->minLength) {
            return [$this->message, ['min' => $this->minLength]];
        }

        if (!preg_match('/[A-Za-z]/', $value) || !preg_match('/\d/', $value)) {
            return [$this->message, ['min' => $this->minLength]];
        }

        return null;
    }
}
