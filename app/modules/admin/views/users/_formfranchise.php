<?php

use app\models\User;
use app\modules\admin\models\base\Vehicals;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use app\modules\admin\models\City;

/**
 * @var $this yii\web\View
 * @var $model \app\modules\admin\forms\UserForm
 * @var $form yii\widgets\ActiveForm
 */
?>

<div class="user-form card">
<div class="card-body">
	<?php $form = ActiveForm::begin([
		'layout' => 'horizontal',
		'enableAjaxValidation' => true,
	]); ?>

	
		<?= $form->field($model, 'first_name')->textInput(['maxlength' => true]) ?>
		
		<?= $form->field($model, 'last_name')->textInput(['maxlength' => true]) ?>
		
		<?=$form->field($model, 'username', ['enableAjaxValidation' => true]); ?>
		
		<?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>
		
		<?= $form->field($model, 'password')->passwordInput() ?>
		
		<?= $form->field($model, 'passwordRepeat')->passwordInput() ?>

		<?= $form->field($model, 'user_role')->dropDownList([User::ROLE_FRANCHISE=>"City Franchise"]) ?>
		
		<?= $form->field($model, 'status')->dropDownList(User::getStatusesList()) ?>

		<?= $form->field($model, 'city_id')->widget(\kartik\select2\Select2::classname(), [
        'data' => \yii\helpers\ArrayHelper::map(City::find()->where(['status' =>1])->orderBy('id')->asArray()->all(), 'id', 'name'),
        'options' => ['placeholder' => Yii::t('app', 'Choose City')],
        'pluginOptions' => [
            'allowClear' => true
        ],
    ]); ?>
		
		
		
	
	<div class="card-footer text-end">
		<?= Html::submitButton($model->isNewRecord ? 'Save' : 'Update', ['class' => 'btn btn-primary']) ?>
	</div>

	<?php ActiveForm::end(); ?>
	</div>
</div>
