<?php

use yii\helpers\Html;
use kartik\form\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\Banner */
/* @var $form yii\widgets\ActiveForm */

?>

<div class="banner-form">

    <?php $form = ActiveForm::begin([
        'id' => 'login-form-inline', 
        'type' => ActiveForm::TYPE_VERTICAL,
        'tooltipStyleFeedback' => true, // shows tooltip styled validation error feedback
        'fieldConfig' => ['options' => ['class' => 'form-group col-xs-6 col-sm-6 col-md-6 col-lg-12']], // spacing field groups
        'formConfig' => ['showErrors' => true],
        // set style for proper tooltips error display
    ]); ?>
  
    <?= $form->errorSummary($model); ?>
  
    <?= $form->field($model, 'id', ['template' => '{input}'])->textInput(['style' => 'display:none']); ?>

    <?= $form->field($model, 'banner_category_id')->widget(\kartik\widgets\Select2::classname(), [
        'data' => \yii\helpers\ArrayHelper::map(\app\modules\admin\models\BannerCategorys::find()->orderBy('id')->asArray()->all(), 'id', 'title'),
        'options' => ['placeholder' => Yii::t('app', 'Choose Banner categorys')],
        'pluginOptions' => [
            'allowClear' => true
        ],
    ]); ?>

    <?= $form->field($model, 'title')->textInput(['maxlength' => true, 'placeholder' => 'Title']) ?>

    <?= $form->field($model, 'image')->textInput(['maxlength' => true, 'placeholder' => 'Image']) ?>

    <?= $form->field($model, 'description')->widget(\mihaildev\ckeditor\CKEditor::className(),[
                'editorOptions' => [
                    'preset' => 'full',
                    'inline' => false, 
                ],
            ]) ?>

    <?= $form->field($model, 'type_id')->textInput(['placeholder' => 'Type']) ?>

    <?= $form->field($model, 'sort_order')->textInput(['placeholder' => 'Sort Order']) ?>

    <?= $form->field($model, 'status')->dropDownList($model->getStateOptions()) ?>

<?php if($model->isNewRecord){ ?><?php } ?>    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>
  
    <?php ActiveForm::end(); ?>

</div>
