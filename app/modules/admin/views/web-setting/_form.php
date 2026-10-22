<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\WebSetting */
/* @var $form yii\widgets\ActiveForm */

?>

<div class="web-setting-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->errorSummary($model); ?>

    <?php $form->field($model, 'setting_id')->textInput(['placeholder' => 'Setting']) ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'placeholder' => 'Name']) ?>

    <?= $form->field($model, 'setting_key')->textInput(['maxlength' => true, 'placeholder' => 'Setting Key']) ?>

    <?= $form->field($model, 'value')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'type_id')->dropDownList($model->getTypeOption()) ?>

    <?= $form->field($model, 'status')->dropDownList($model->getStateOption()) ?>

    <?= $form->field($model, 'created_date', ['template' => '{input}'])->textInput(['style' => 'display:none']); ?>

    <?= $form->field($model, 'updated_date', ['template' => '{input}'])->textInput(['style' => 'display:none']); ?>

    <?= $form->field($model, 'create_user_id', ['template' => '{input}'])->textInput(['style' => 'display:none']); ?>

    <?= $form->field($model, 'updated_user_id', ['template' => '{input}'])->textInput(['style' => 'display:none']); ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        <?= Html::a(Yii::t('app', 'Cancel'), Yii::$app->request->referrer , ['class'=> 'btn btn-danger']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
