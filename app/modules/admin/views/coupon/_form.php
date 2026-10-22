<?php

use yii\helpers\Html;
use kartik\form\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\Coupon */
/* @var $form yii\widgets\ActiveForm */

?>

<div class="coupon-form">

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

    <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'placeholder' => 'Name']) ?>

    <?= $form->field($model, 'description')->widget(\mihaildev\ckeditor\CKEditor::className(),[
                'editorOptions' => [
                    'preset' => 'full',
                    'inline' => false, 
                ],
            ]) ?>

    <?= $form->field($model, 'code')->textInput(['maxlength' => true, 'placeholder' => 'Code']) ?>

    <?= $form->field($model, 'discount')->textInput(['placeholder' => 'Discount']) ?>

    <?= $form->field($model, 'max_discount')->textInput(['placeholder' => 'Max Discount']) ?>

    <?= $form->field($model, 'min_ride_amount')->textInput(['placeholder' => 'Min Ride Amount']) ?>

    <?= $form->field($model, 'max_use')->textInput(['placeholder' => 'Max Use']) ?>

    <?= $form->field($model, 'max_use_of_coupon')->textInput(['placeholder' => 'Max Use Of Coupon']) ?>

    <?= $form->field($model, 'start_date')->widget(\kartik\datecontrol\DateControl::classname(), [
        'type' => \kartik\datecontrol\DateControl::FORMAT_DATE,
        'saveFormat' => 'php:Y-m-d',
        'ajaxConversion' => true,
        'options' => [
            'pluginOptions' => [
                'placeholder' => Yii::t('app', 'Choose Start Date'),
                'autoclose' => true
            ]
        ],
    ]); ?>

    <?= $form->field($model, 'end_date')->widget(\kartik\datecontrol\DateControl::classname(), [
        'type' => \kartik\datecontrol\DateControl::FORMAT_DATE,
        'saveFormat' => 'php:Y-m-d',
        'ajaxConversion' => true,
        'options' => [
            'pluginOptions' => [
                'placeholder' => Yii::t('app', 'Choose End Date'),
                'autoclose' => true
            ]
        ],
    ]); ?>

    <?= $form->field($model, 'is_global')->dropDownList($model->getGlobalOptions()) ?>

    <?= $form->field($model, 'status')->dropDownList($model->getStateOptions()) ?>

    <?= $form->field($model, 'type_id')->textInput(['placeholder' => 'Type']) ?>

    <?= $form->field($model, 'module')->dropDownList(\app\modules\geo\services\ServiceModules::titles(), ['prompt' => 'Which service is this coupon for?'])->label('Service') ?>

    <?= $form->field($model, 'city_id')->widget(\kartik\widgets\Select2::classname(), [
            'data' => \yii\helpers\ArrayHelper::map(\app\modules\admin\models\City::find()->orderBy('id')->asArray()->all(), 'id', 'name'),
            'options' => ['placeholder' => Yii::t('app', 'Choose City')],
            'pluginOptions' => [
                'allowClear' => true
            ],
        ]);  ?> 
<?php if($model->isNewRecord){ ?><?php } ?>    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>
  
    <?php ActiveForm::end(); ?>

</div>
