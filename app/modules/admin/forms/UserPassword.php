<?php

namespace app\modules\admin\forms;
use app\modules\admin\models\City;
use app\models\User;
use app\validators\PasswordStrengthValidator;
use Yii;

class UserPassword extends User
{
	/**
	 * @var string
	 */
	public $password;
	
	/**
	 * @var string
	 */
	public $passwordRepeat;
	
	/**
	 * @var string
	 */
	public $roles;
    const SCENARIO_UPDATE = 'update';
	
	/**
	 * @inheritdoc
	 * @return array
	 */
	public function rules()
	{
		return [
			[['first_name', 'last_name', 'username', 'password','passwordRepeat'], 'required'],
			['password', PasswordStrengthValidator::class],
            [['username'], 'safe', 'on' => ['update']],
			['passwordRepeat', 'compare', 'compareAttribute' => 'password'],
		];
	}

    /**
 * @inheritdoc
 */
// public function init()
// {
//     parent::init();
//     $this->modelClass = UserPassword::class; // Change this line
// }

public function scenarios()
{
    $scenarios = parent::scenarios();
    $scenarios[self::SCENARIO_UPDATE] = ['first_name', 'last_name', 'username', 'password', 'passwordRepeat'];
    return $scenarios;
}
	
	/**
	 * @inheritdoc
	 * @return bool
	 */
	public function beforeSave($insert)
	{
		if ($this->password) {
			$this->setPassword($this->password);
		}
		
		return parent::beforeSave($insert);
	}

	/**
	 * @inheritDoc
	 */
	public function afterSave($insert, $changedAttributes)
	{
		parent::afterSave($insert, $changedAttributes);

		//$this->updateUserRoles($this->roles);
	}

	/**
	 * @inheritdoc
	 */
	// public function afterFind()
	// {
	// 	$roles = array_keys(Yii::$app->authManager->getRolesByUser($this->id));
	// 	$roles = array_combine($roles, $roles);
	// 	$this->roles = $roles;
		
	// 	parent::afterFind();
	// }
	
	/**
	 * @param array|string $roles
	 */
	protected function updateUserRoles($roles)
	{
		if (empty($roles)) {
			return;
		}
		
		Yii::$app->authManager->revokeAll($this->id);
		
		foreach ($roles as $role) {
			$this->assignRole($role);
		}
	}
}