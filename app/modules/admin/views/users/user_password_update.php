<?php

use app\models\User;

use yii\helpers\Html;
use kartik\form\ActiveForm;
/**
 * @var $this yii\web\View
 * @var $model \app\modules\admin\forms\UserForm
 * @var $form yii\widgets\ActiveForm
 */
?>

<div class="user-form card">
<div class="card-body">
<?php $form = ActiveForm::begin(['method' => 'post', 'enableClientValidation' => true]); ?>


	
<?= $form->field($model, 'first_name')->textInput(['maxlength' => true, 'id' => 'first_name']) ?>
<?= $form->field($model, 'last_name')->textInput(['maxlength' => true, 'id' => 'last_name']) ?>
		
<?= $form->field($model, 'username')->textInput(['maxlength' => true, 'id' => 'username']) ?>

		
		
		<?= $form->field($model, 'password')->passwordInput() ?>
		
		<?= $form->field($model, 'passwordRepeat')->passwordInput() ?>

		
	
	<div class="card-footer text-end">
		<?= Html::submitButton($model->isNewRecord ? 'Save' : 'Update', ['class' => 'btn btn-primary']) ?>
	</div>

	
	<?php ActiveForm::end(); ?>
	</div>
</div>