<?php

use yii\helpers\Html;
use kartik\form\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\DriverSubscriptionLogs */
/* @var $form yii\widgets\ActiveForm */

?>

<div class="driver-subscription-logs-form">

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

    <?= $form->field($model, 'driver_id')->widget(\kartik\widgets\Select2::classname(), [
        'data' => \yii\helpers\ArrayHelper::map(\app\modules\admin\models\User::find()->orderBy('id')->asArray()->all(), 'id', 'username'),
        'options' => ['placeholder' => Yii::t('app', 'Choose User')],
        'pluginOptions' => [
            'allowClear' => true
        ],
    ]); ?>

    <?= $form->field($model, 'plan_id')->widget(\kartik\widgets\Select2::classname(), [
        'data' => \yii\helpers\ArrayHelper::map(\app\modules\admin\models\Plans::find()->orderBy('id')->asArray()->all(), 'id', 'title'),
        'options' => ['placeholder' => Yii::t('app', 'Choose Plans')],
        'pluginOptions' => [
            'allowClear' => true
        ],
    ]); ?>

    <?= $form->field($model, 'plan_price')->textInput(['placeholder' => 'Plan Price']) ?>

    <?= $form->field($model, 'validity_in_days')->textInput(['placeholder' => 'Validity In Days']) ?>

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

    <?= $form->field($model, 'status')->dropDownList($model->getStateOptions()) ?>

<?php if($model->isNewRecord){ ?><?php } ?>    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>
  
    <?php ActiveForm::end(); ?>

</div>
