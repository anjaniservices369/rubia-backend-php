<?php
/**
 * @var \yii\web\View $this
 * @var \app\forms\PasswordRequestForm $model
 */

use yii\bootstrap4\ActiveForm;
use yii\helpers\Html;

$this->title = 'Forgot Password';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="site-forgot-password col-md-6 mx-auto">
	<h1><?= Html::encode($this->title) ?></h1>
	<p>Enter your email and we will send you a link to reset your password.</p>

	<?php $form = ActiveForm::begin([
		'id' => 'forgot-password-form',
	]); ?>

	<?= $form->field($model, 'email')->textInput(['autofocus' => true]) ?>

	<div class="form-group">
		<?= Html::submitButton('Send reset link', ['class' => 'btn btn-primary']) ?>
	</div>

	<?php ActiveForm::end(); ?>

	<p><?= Html::a('Back to sign in', ['site/login']) ?></p>
</div>
