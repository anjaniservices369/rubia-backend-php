<?php

namespace app\forms;

use app\components\IpAttemptThrottle;
use app\models\User;
use justcoded\yii2\rbac\models\Item as RbacItem;
use Yii;
use yii\base\Model;

/**
 * LoginForm is the model behind the login form.
 *
 * @property User|null $user This property is read-only.
 *
 */
class LoginForm extends Model
{
	public $username;
	public $password;
	public $rememberMe = true;
	public $device_token;
    public $device_type;
	private $_user = false;

	// Failed sign-ins from one IP are throttled rather than locking a named account: locking by username
	// alone would let anyone with an account's email (the two super admins are no secret) deny that admin
	// access just by typing wrong passwords from somewhere else.
	const MAX_FAILED_ATTEMPTS = 10;
	const THROTTLE_WINDOW = 900; // 15 minutes

	// Rider and driver accounts are end users of the mobile apps; they have no business opening a dashboard
	// session at all, regardless of password correctness.
	const DASHBOARD_BLOCKED_ROLES = [User::ROLE_USER, User::ROLE_DRIVER, User::ROLE_TAXI];


	/**
	 * @return array the validation rules.
	 */
	public function rules()
	{
		return [
			// username and password are both required
			[['username', 'password'], 'required'],
			// rememberMe must be a boolean value
			['rememberMe', 'boolean'],
			// password is validated by validatePassword()
			['password', 'validatePassword'],
			[ ['device_token','device_type'],'safe'],
			// admin/franchise sign-in is email-only; other roles (drivers, customers) use phone-based usernames
			['username', 'email', 'on' => 'admin'],
		];
	}

	/**
	 * Validates the password.
	 * This method serves as the inline validation for password.
	 *
	 * @param string $attribute the attribute currently being validated
	 * @param array  $params the additional name-value pairs given in the rule
	 */
	public function validatePassword($attribute, $params)
	{
		if ($this->hasErrors()) {
			return;
		}

		if ($this->isIpThrottled()) {
			$this->addError($attribute, 'Too many failed sign-in attempts. Please try again later.');
			return;
		}

		$user = $this->getUser();

		if (! $user || ! $user->validatePassword($this->password)) {
			$this->registerFailedAttempt();
			$this->addError($attribute, 'Incorrect username or password.');
			return;
		}

		// user_role = 'admin' in the database is not, by itself, proof this is a trusted operator account
		// (anyone able to insert/update a user row can set it). Admin sign-in additionally requires the
		// account's email to be on the fixed super-admin allowlist in params.php.
		if ($user->user_role === User::ROLE_ADMIN && ! $this->isAllowedSuperAdmin($user)) {
			$this->registerFailedAttempt();
			$this->addError($attribute, 'Incorrect username or password.');
			return;
		}

		// This form is the dashboard's sign-in. Riders and drivers are end users of the mobile apps, not
		// dashboard operators, and must not be able to open a dashboard session even with a correct password.
		if (in_array($user->user_role, self::DASHBOARD_BLOCKED_ROLES, true)) {
			$this->registerFailedAttempt();
			$this->addError($attribute, 'Incorrect username or password.');
			return;
		}

		$this->clearFailedAttempts();
	}

	private function isIpThrottled()
	{
		return IpAttemptThrottle::isThrottled('login', self::MAX_FAILED_ATTEMPTS);
	}

	private function registerFailedAttempt()
	{
		IpAttemptThrottle::registerAttempt('login', self::THROTTLE_WINDOW);
	}

	private function clearFailedAttempts()
	{
		IpAttemptThrottle::clear('login');
	}

	/**
	 * Whether $user is one of the fixed, trusted super-admin accounts (params.php: superAdminEmails).
	 * Only matters for accounts with user_role = ROLE_ADMIN; other roles are unaffected.
	 */
	private function isAllowedSuperAdmin(User $user)
	{
		$allowed = array_map('strtolower', Yii::$app->params['superAdminEmails'] ?? []);
		$candidates = array_filter([$user->email, $user->username]);

		foreach ($candidates as $candidate) {
			if (in_array(strtolower($candidate), $allowed, true)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Logs in a user using the provided username and password.
	 *
	 * @return bool whether the user is logged in successfully
	 */
	public function login()
	{
		if ($this->validate()) {
			$user = $this->getUser();
			return Yii::$app->user->login($user, $this->sessionDuration($user));
		}

		return false;
	}

	/**
	 * Admin/sub-admin sessions are capped at 8 hours regardless of "remember me": these accounts can manage
	 * other accounts and change money-affecting settings, so a stolen long-lived cookie is worse here than
	 * for an ordinary rider/driver session, which keeps the existing 30-day/session-only behavior.
	 */
	private function sessionDuration(User $user)
	{
		if (in_array($user->user_role, [User::ROLE_ADMIN, User::ROLE_SUBADMIN], true)) {
			return 3600 * 8;
		}

		return $this->rememberMe ? 3600 * 24 * 30 : 0;
	}

	/**
	 * Assign default role 'Authenticated' to any logged in user
	 *
	 * @return bool
	 */
	public function assignAuthenticatedRole()
	{
		return $this->getUser()->assignRole(RbacItem::ROLE_AUTHENTICATED);
	}

	/**
	 * Finds user by [[username]]
	 *
	 * @return User|null
	 */
	public function getUser()
	{
		if ($this->_user === false) {
			$this->_user = User::findByUsername($this->username);
		}

		return $this->_user;
	}
}
