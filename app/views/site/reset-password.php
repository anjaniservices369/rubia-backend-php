<?php
/**
 * @var \yii\web\View $this
 * @var \app\forms\PasswordUpdateForm $model
 */

use yii\bootstrap4\ActiveForm;
use yii\helpers\Html;

$this->title = 'Reset Password';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="site-reset-password col-md-6 mx-auto">
	<h1><?= Html::encode($this->title) ?></h1>

	<?php $form = ActiveForm::begin([
		'id' => 'reset-password-form',
	]); ?>

	<?= $form->field($model, 'newPassword')->passwordInput(['autofocus' => true]) ?>
	<?= $form->field($model, 'newPasswordRepeat')->passwordInput() ?>

	<div class="form-group">
		<?= Html::submitButton('Set new password', ['class' => 'btn btn-primary']) ?>
	</div>

	<?php ActiveForm::end(); ?>
</div>
