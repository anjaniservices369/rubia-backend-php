<?php

namespace app\modules\admin\forms;
use app\modules\admin\models\City;
use app\models\User;
use app\validators\PasswordStrengthValidator;
use Yii;

class UserForm extends User
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
	
	/**
	 * @inheritdoc
	 * @return array
	 */
	public function rules()
	{
		return [
			[['first_name', 'last_name', 'email',  'user_role', 'status', 'city_id'], 'required'],
			[['date_of_birth'], 'string'],
			[['city_id'], 'required'],
			[['city_id'], 'integer'],
			[['city_id'], 'exist', 'targetClass' => City::class, 'targetAttribute' => 'id', 'skipOnError' => true],
			['passwordRepeat', 'compare', 'compareAttribute' => 'password'],
			['password', PasswordStrengthValidator::class, 'skipOnEmpty' => true, 'on' => ['update']],
			[['password','passwordRepeat', ], 'safe', 'on' => ['update']],
		];
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