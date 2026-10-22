<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\search\WebSettingSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="form-web-setting-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'setting_id')->textInput(['placeholder' => 'Setting']) ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'placeholder' => 'Name']) ?>

    <?= $form->field($model, 'setting_key')->textInput(['maxlength' => true, 'placeholder' => 'Setting Key']) ?>

    <?= $form->field($model, 'value')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'type_id')->textInput(['placeholder' => 'Type']) ?>

    <?php /* echo $form->field($model, 'status')->textInput(['placeholder' => 'Status']) */ ?>

    <?php /* echo $form->field($model, 'created_date', ['template' => '{input}'])->textInput(['style' => 'display:none']); */ ?>

    <?php /* echo $form->field($model, 'updated_date', ['template' => '{input}'])->textInput(['style' => 'display:none']); */ ?>

    <?php /* echo $form->field($model, 'create_user_id', ['template' => '{input}'])->textInput(['style' => 'display:none']); */ ?>

    <?php /* echo $form->field($model, 'updated_user_id', ['template' => '{input}'])->textInput(['style' => 'display:none']); */ ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
