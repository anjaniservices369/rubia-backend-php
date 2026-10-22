<?php

namespace app\forms;

use app\models\AdminAuditLog;
use app\models\User;
use app\validators\PasswordStrengthValidator;
use yii\base\Model;

class PasswordUpdateForm extends Model
{
	public $resetToken;
	public $newPassword;
	public $newPasswordRepeat;


	/**
	 * @return array the validation rules.
	 */
	public function rules()
	{
		return [
			[['newPassword', 'newPasswordRepeat'], 'required'],
			['newPassword', PasswordStrengthValidator::class],
			['newPasswordRepeat', 'compare', 'compareAttribute' => 'newPassword'],
		];
	}
	
	/**
	 * Updates user's password if $resetToken is valid
	 *
	 * @return bool
	 */
	public function update()
	{
		$user = User::findByPasswordResetToken($this->resetToken);

		if (empty($user)) {
			$this->addError('newPassword', 'This reset link is invalid or has expired. Please request a new one.');
			return false;
		}
		
		if ($this->validate()) {
			$user->setPassword($this->newPassword);
			$user->removePasswordResetToken();
			
			if (!$user->save()) {
				$this->addErrors($user->errors);

				return false;
			}

			if ($user->user_role === User::ROLE_ADMIN) {
				AdminAuditLog::record(AdminAuditLog::ACTION_PASSWORD_RESET_COMPLETED, $user->username);
			}

			return true;
		}
		
		return false;
	}
}