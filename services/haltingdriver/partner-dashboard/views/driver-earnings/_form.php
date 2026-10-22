<?php

use yii\helpers\Html;
use kartik\form\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\DriverEarnings */
/* @var $form yii\widgets\ActiveForm */

?>

<div class="driver-earnings-form">

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

    <?= $form->field($model, 'booking_id')->widget(\kartik\widgets\Select2::classname(), [
        'data' => \yii\helpers\ArrayHelper::map(\app\modules\admin\models\Booking::find()->orderBy('id')->asArray()->all(), 'id', 'id'),
        'options' => ['placeholder' => Yii::t('app', 'Choose Booking')],
        'pluginOptions' => [
            'allowClear' => true
        ],
    ]); ?>

    <?= $form->field($model, 'driver_id')->widget(\kartik\widgets\Select2::classname(), [
        'data' => \yii\helpers\ArrayHelper::map(\app\modules\admin\models\User::find()->orderBy('id')->asArray()->all(), 'id', 'username'),
        'options' => ['placeholder' => Yii::t('app', 'Choose User')],
        'pluginOptions' => [
            'allowClear' => true
        ],
    ]); ?>

    <?= $form->field($model, 'total_distance_km')->textInput(['maxlength' => true, 'placeholder' => 'Total Distance Km']) ?>

    <?= $form->field($model, 'total_amount')->textInput(['placeholder' => 'Total Amount']) ?>

    <?= $form->field($model, 'admin_per')->textInput(['placeholder' => 'Admin Per']) ?>

    <?= $form->field($model, 'admin_earning')->textInput(['placeholder' => 'Admin Earning']) ?>

    <?= $form->field($model, 'driver_earning')->textInput(['placeholder' => 'Driver Earning']) ?>

    <?= $form->field($model, 'payment_method')->textInput(['placeholder' => 'Payment Method']) ?>

    <?= $form->field($model, 'status')->dropDownList(['APPROVED' => 'Active', 'PENDING' => 'Deleted']) ?>

    <?= $form->field($model, 'created_on')->textInput(['type' => 'datetime-local']) ?>

<?= $form->field($model, 'updated_on')->textInput(['type' => 'datetime-local']) ?>


<?php if($model->isNewRecord){ ?><?php } ?>    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>
  
    <?php ActiveForm::end(); ?>

</div>
