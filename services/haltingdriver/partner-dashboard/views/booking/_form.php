<?php

use yii\helpers\Html;
use kartik\form\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\Booking */
/* @var $form yii\widgets\ActiveForm */

?>

<div class="booking-form">

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

    <?= $form->field($model, 'user_id')->widget(\kartik\widgets\Select2::classname(), [
        'data' => \yii\helpers\ArrayHelper::map(\app\modules\admin\models\User::find()->orderBy('id')->asArray()->all(), 'id', 'username'),
        'options' => ['placeholder' => 'Choose User'],
        'pluginOptions' => [
            'allowClear' => true
        ],
    ]); ?>

<?= $form->field($model, 'rider_type')->dropDownList($model->geRideType(), ['prompt' => 'Select Rider Type']) ?>

<?= $form->field($model, 'trip_type')->dropDownList($model->getTripType(), ['prompt' => 'Select Trip Type']) ?>


    <?= $form->field($model, 'pickup_latitude')->textInput(['maxlength' => true, 'placeholder' => 'Pickup Latitude']) ?>

    <?= $form->field($model, 'pickup_longitude')->textInput(['maxlength' => true, 'placeholder' => 'Pickup Longitude']) ?>

    <?= $form->field($model, 'drop_latitude')->textInput(['maxlength' => true, 'placeholder' => 'Drop Latitude']) ?>

    <?= $form->field($model, 'drop_longitude')->textInput(['maxlength' => true, 'placeholder' => 'Drop Longitude']) ?>

    <?= $form->field($model, 'car_type')->dropDownList($model->getCarTypes(), ['prompt' => 'Select Car Type']) ?>


    <?= $form->field($model, 'car_model')->textInput(['maxlength' => true, 'placeholder' => 'Car Model']) ?>

    <?= $form->field($model, 'pickup_date')->widget(\kartik\datecontrol\DateControl::classname(), [
        'type' => \kartik\datecontrol\DateControl::FORMAT_DATE,
        'saveFormat' => 'php:Y-m-d',
        'ajaxConversion' => true,
        'options' => [
            'pluginOptions' => [
                'placeholder' => 'Choose Pickup Date',
                'autoclose' => true
            ]
        ],
    ]); ?>

    <?= $form->field($model, 'pickup_time')->widget(\kartik\datecontrol\DateControl::className(), [
        'type' => \kartik\datecontrol\DateControl::FORMAT_TIME,
        'saveFormat' => 'php:H:i:s',
        'ajaxConversion' => true,
        'options' => [
            'pluginOptions' => [
                'placeholder' => 'Choose Pickup Time',
                'autoclose' => true
            ]
        ]
    ]); ?>

    <?= $form->field($model, 'estimate_price')->textInput(['maxlength' => true, 'placeholder' => 'Estimate Price']) ?>

    <?= $form->field($model, 'usage_hours')->textInput(['placeholder' => 'Usage Hours']) ?>

    <?= $form->field($model, 'coupon_code')->textInput(['maxlength' => true, 'placeholder' => 'Coupon Code']) ?>

    <?= $form->field($model, 'payment_method')->textInput(['placeholder' => 'Payment Method']) ?>

    <?= $form->field($model, 'status')->dropDownList($model->getStateOptions()) ?>

    <?= $form->field($model, 'created_on')->textInput(['maxlength' => true, 'placeholder' => 'Created On']) ?>

    <?= $form->field($model, 'updated_on')->textInput(['maxlength' => true, 'placeholder' => 'Updated On']) ?>

    <?= $form->field($model, 'create_user_id')->textInput(['placeholder' => 'Create User']) ?>

    <?= $form->field($model, 'update_user_id')->textInput(['placeholder' => 'Update User']) ?>

<?php if($model->isNewRecord){ ?><?php } ?>    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>
  
    <?php ActiveForm::end(); ?>

</div>
