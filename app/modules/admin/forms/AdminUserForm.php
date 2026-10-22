<?php

namespace app\modules\admin\forms;

use app\modules\admin\models\City;
use app\models\User;
use app\validators\PasswordStrengthValidator;
use Yii;

class AdminUserForm extends User
{
    /**
     * @var string
     */
    public $password;

    /**
     * @var string
     */
    public $passwordRepeat;
    public $first_name; // Make sure these properties are declared
    public $last_name;

    /**
     * @inheritdoc
     * @return array
     */
    public function rules()
	{
		 return [
            [['username'], 'required', 'on' => ['create']],
            [['username'], 'safe', 'on' => ['update']], // Adjust the scenario name as needed
            [['first_name', 'last_name', 'email'], 'required'],
            // Password is optional here: this form is only ever used to edit an existing admin/sub-admin's
            // profile (DashboardController::actionUpdateProfile), so leaving it blank just means "keep the
            // current password" rather than forcing a change on every unrelated edit (e.g. a name change).
            ['password', PasswordStrengthValidator::class, 'skipOnEmpty' => true],
            ['passwordRepeat', 'compare', 'compareAttribute' => 'password', 'message' => 'Passwords do not match.', 'skipOnEmpty' => true],
            ['user_role', 'safe'],
    ];
	}

    /**
     * @inheritdoc
     * @return array
     */
    public function attributeLabels()
{
    $labels = parent::attributeLabels(); // Use the attribute labels from the parent class

    // Add or override labels for the current class
    $labels['password'] = 'Password';
    $labels['passwordRepeat'] = 'Repeat Password';

    return $labels;
}

    /**
     * @inheritdoc
     * @return bool
     */

    //  public function attributeLabels()
    //  {
    //      $labels = parent::attributeLabels(); // Use the attribute labels from the parent class
 
    //      // Add or override labels for the current class
    //      $labels['password'] = 'Password';
    //      $labels['passwordRepeat'] = 'Repeat Password';
 
    //      return $labels;
    //  }

    public function beforeAction($action)
{
    try {
        return parent::beforeAction($action);
    } catch (ForbiddenHttpException $e) {
        // Handle the ForbiddenHttpException here
        Yii::error('ForbiddenHttpException: ' . $e->getMessage());
        // Redirect or show a custom error page
        return $this->redirect(['/site/error']);
    }
}

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

    // Add any additional logic specific to the admin form
    $this->updateUserRoles($this->roles);
}

protected function updateUserRoles($newRoles)
{
    // Logic to update user roles
}

}