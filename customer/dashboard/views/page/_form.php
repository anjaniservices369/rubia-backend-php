<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\Page */
/* @var $form yii\widgets\ActiveForm */

?>

<div class="page-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->errorSummary($model); ?>

    <?= $form->field($model, 'id', ['template' => '{input}'])->textInput(['style' => 'display:none']); ?>

    <?= $form->field($model, 'app_key')->dropDownList($model->appOptions())->hint(Yii::t('app', 'Each app has its own Terms, Privacy and Refund text. If several active pages have the same app and type, the newest one is shown.')) ?>

    <?= $form->field($model, 'title')->textInput(['maxlength' => true, 'placeholder' => 'Title']) ?>

    <?=$form->field($model, 'description')->widget(\mihaildev\ckeditor\CKEditor::className(), ['options' => ['rows' => 6], 'editorOptions' => ['preset' => 'full']])?>

    <?= $form->field($model, 'state_id')->dropDownList($model->getStateOptions()) ?>
    
    <?= $form->field($model, 'type_id')->dropDownList($model->gettypeOption()) ?>

   
    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
