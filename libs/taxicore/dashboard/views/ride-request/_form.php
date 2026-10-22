<?php

use yii\helpers\Html;
use kartik\form\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\RideRequest */
/* @var $form yii\widgets\ActiveForm */

?>

<div class="ride-request-form">

    <?php $form = ActiveForm::begin([
    'id' => 'login-form-inline',
    'type' => ActiveForm::TYPE_VERTICAL,
    'tooltipStyleFeedback' => true, // shows tooltip styled validation error feedback
    'fieldConfig' => ['options' => ['class' => 'form-group col-xs-6 col-sm-6 col-md-6 col-lg-12']], // spacing field groups
    'formConfig' => ['showErrors' => true],
    // set style for proper tooltips error display
    ]); ?>

    <?= $form->errorSummary($model); ?>
    <div class="row">
         <div class='col-lg-6 '>   <?= $form->field($model, 'id', ['template' => '{input}'])->textInput(['style' => 'display:none']); ?>

 </div> <div class='col-lg-6'>    <?= $form->field($model, 'id', ['template' => '{input}'])->textInput(['style' => 'display:none']);  ?> </div>

 <div class='col-lg-6'>    <?= $form->field($model, 'user_id')->textInput(['placeholder' => 'User'])  ?> </div>

 <div class='col-lg-6'>    <?= $form->field($model, 'driver_id')->textInput(['placeholder' => 'Driver'])  ?> </div>

 <div class='col-lg-6'>    <?= $form->field($model, 'pickup_address')->textInput(['maxlength' => true, 'placeholder' => 'Pickup Address'])  ?> </div>

 <div class='col-lg-6'>    <?= $form->field($model, 'pickup_pincode')->textInput(['placeholder' => 'Pickup Pincode'])  ?> </div>

 <div class='col-lg-6'>    <?= $form->field($model, 'pickup_latitude')->textInput(['maxlength' => true, 'placeholder' => 'Pickup Latitude'])  ?> </div>

 <div class='col-lg-6'>    <?= $form->field($model, 'pickup_longitude')->textInput(['maxlength' => true, 'placeholder' => 'Pickup Longitude'])  ?> </div>

 <div class='col-lg-6'>    <?= $form->field($model, 'drop_address')->textInput(['maxlength' => true, 'placeholder' => 'Drop Address'])  ?> </div>

 <div class='col-lg-6'>    <?= $form->field($model, 'drop_pincode')->textInput(['placeholder' => 'Drop Pincode'])  ?> </div>

 <div class='col-lg-6'>    <?= $form->field($model, 'drop_latitude')->textInput(['maxlength' => true, 'placeholder' => 'Drop Latitude'])  ?> </div>

 <div class='col-lg-6'>    <?= $form->field($model, 'drop_longitude')->textInput(['maxlength' => true, 'placeholder' => 'Drop Longitude'])  ?> </div>

 <div class='col-lg-6'>    <?= $form->field($model, 'vehical_type')->textInput(['placeholder' => 'Vehical Type'])  ?> </div>

 <div class='col-lg-6'>    <?= $form->field($model, 'estimated_ride_fare')->textInput(['placeholder' => 'Estimated Ride Fare'])  ?> </div>

 <div class='col-lg-6'>    <?= $form->field($model, 'estimated_distance')->textInput(['placeholder' => 'Estimated Distance'])  ?> </div>

 <div class='col-lg-6'>    <?= $form->field($model, 'estimated_time')->textInput(['placeholder' => 'Estimated Time'])  ?> </div>

 <div class='col-lg-6'>    <?= $form->field($model, 'final_distance')->textInput(['placeholder' => 'Final Distance'])  ?> </div>

 <div class='col-lg-6'>    <?= $form->field($model, 'final_time')->textInput(['placeholder' => 'Final Time'])  ?> </div>

 <div class='col-lg-6'>    <?= $form->field($model, 'final_price')->textInput(['placeholder' => 'Final Price'])  ?> </div>

 <div class='col-lg-6'>    <?= $form->field($model, 'coupon_code')->textInput(['placeholder' => 'Coupon Code'])  ?> </div>

 <div class='col-lg-6'>    <?= $form->field($model, 'coupon_discount')->textInput(['placeholder' => 'Coupon Discount'])  ?> </div>

 <div class='col-lg-6'>    <?= $form->field($model, 'coupon_applied_id')->textInput(['placeholder' => 'Coupon Applied'])  ?> </div>

 <div class='col-lg-6'>    <?= $form->field($model, 'total_fare')->textInput(['placeholder' => 'Total Fare'])  ?> </div>

 <div class='col-lg-6'>    <?= $form->field($model, 'otp')->textInput(['placeholder' => 'Otp'])  ?> </div>

 <div class='col-lg-6'>    <?= $form->field($model, 'ride_start_time')->widget(\kartik\datecontrol\DateControl::classname(), [
        'type' => \kartik\datecontrol\DateControl::FORMAT_DATETIME,
        'saveFormat' => 'php:Y-m-d H:i:s',
        'ajaxConversion' => true,
        'options' => [
            'pluginOptions' => [
                'placeholder' => Yii::t('app', 'Choose Ride Start Time'),
                'autoclose' => true,
            ]
        ],
    ]);  ?> </div>

 <div class='col-lg-6'>    <?= $form->field($model, 'ride_end_time')->widget(\kartik\datecontrol\DateControl::classname(), [
        'type' => \kartik\datecontrol\DateControl::FORMAT_DATETIME,
        'saveFormat' => 'php:Y-m-d H:i:s',
        'ajaxConversion' => true,
        'options' => [
            'pluginOptions' => [
                'placeholder' => Yii::t('app', 'Choose Ride End Time'),
                'autoclose' => true,
            ]
        ],
    ]);  ?> </div>

 <div class='col-lg-6'>    <?= $form->field($model, 'status')->dropDownList($model->getStateOptions())  ?> </div>

 </div> <?php if($model->isNewRecord){ ?><?php } ?>    <div class="form-group">
                            <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
                    </div>

    <?php ActiveForm::end(); ?>

</div>