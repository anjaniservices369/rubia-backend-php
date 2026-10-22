<?php

namespace app\forms;

use app\components\IpAttemptThrottle;
use app\models\User;
use Yii;
use yii\base\Model;
use yii\helpers\Url;

class PasswordRequestForm extends Model
{
	public $email;

	// A reset email triggers a real SMTP send and lands in someone's inbox, so this is tighter than the
	// login throttle: a handful of requests per window is normal, dozens is someone hammering the form.
	const MAX_REQUESTS = 5;
	const THROTTLE_WINDOW = 900; // 15 minutes

	/**
	 * @return array the validation rules.
	 */
	public function rules()
	{
		return [
			['email', 'required'],
			['email', 'email'],
		];
	}

	/**
	 * Sends a password-reset email if (and only if) $email belongs to an active account that is allowed to
	 * sign in with a password at all. Always returns true on a syntactically valid email, whether or not an
	 * email was actually sent: the caller shows the same message either way, so nobody can use this form to
	 * find out which addresses are registered, which are riders/drivers (no password login), or whether the
	 * IP has been throttled.
	 *
	 * @return bool
	 */
	public function request()
	{
		if (!$this->validate()) {
			return false;
		}

		if (IpAttemptThrottle::isThrottled('pwreset', self::MAX_REQUESTS)) {
			return true;
		}
		IpAttemptThrottle::registerAttempt('pwreset', self::THROTTLE_WINDOW);

		$user = User::findByUsername($this->email);

		// Riders and drivers have no password to reset (see LoginForm::DASHBOARD_BLOCKED_ROLES) -- treat
		// them the same as "no such account" rather than emailing a reset link that would be useless anyway.
		if (!$user || in_array($user->user_role, LoginForm::DASHBOARD_BLOCKED_ROLES, true)) {
			return true;
		}

		$user->generatePasswordResetToken();
		if (!$user->save(false)) {
			return true;
		}

		try {
			Yii::$app->mailer->compose()
				->setTo($user->email)
				->setFrom(Yii::$app->params['adminEmail'])
				->setSubject('Reset your ' . Yii::$app->name . ' password')
				->setTextBody($this->getMessageBody($user->password_reset_token))
				->send();
		} catch (\Throwable $e) {
			// A delivery failure (bad address, SMTP outage) must not turn into a 500, and must not change
			// what the visitor sees -- otherwise a failed send for a real account would be distinguishable
			// from the "no such account" case, which is exactly what this form must never leak.
			Yii::error('Password reset email failed for user ' . $user->id . ': ' . $e->getMessage(), 'mail');
		}

		return true;
	}

	/**
	 * Render a message with reset token
	 *
	 * @param string $resetToken
	 *
	 * @return string
	 */
	protected function getMessageBody($resetToken)
	{
		return 'To reset your password, follow this link (valid for 1 hour): ' . Url::to([
				'/site/reset-password',
				'token' => $resetToken,
			], true);
	}
}
