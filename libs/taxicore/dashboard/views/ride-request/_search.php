<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\search\RideRequestSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="form-ride-request-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id', ['template' => '{input}'])->textInput(['style' => 'display:none']); ?>

    <?= $form->field($model, 'user_id')->textInput(['placeholder' => 'User']) ?>

    <?= $form->field($model, 'driver_id')->textInput(['placeholder' => 'Driver']) ?>

    <?= $form->field($model, 'pickup_address')->textInput(['maxlength' => true, 'placeholder' => 'Pickup Address']) ?>

    <?= $form->field($model, 'pickup_pincode')->textInput(['placeholder' => 'Pickup Pincode']) ?>

    <?php /* echo $form->field($model, 'pickup_latitude')->textInput(['maxlength' => true, 'placeholder' => 'Pickup Latitude']) */ ?>

    <?php /* echo $form->field($model, 'pickup_longitude')->textInput(['maxlength' => true, 'placeholder' => 'Pickup Longitude']) */ ?>

    <?php /* echo $form->field($model, 'drop_address')->textInput(['maxlength' => true, 'placeholder' => 'Drop Address']) */ ?>

    <?php /* echo $form->field($model, 'drop_pincode')->textInput(['placeholder' => 'Drop Pincode']) */ ?>

    <?php /* echo $form->field($model, 'drop_latitude')->textInput(['maxlength' => true, 'placeholder' => 'Drop Latitude']) */ ?>

    <?php /* echo $form->field($model, 'drop_longitude')->textInput(['maxlength' => true, 'placeholder' => 'Drop Longitude']) */ ?>

    <?php /* echo $form->field($model, 'vehical_type')->textInput(['placeholder' => 'Vehical Type']) */ ?>

    <?php /* echo $form->field($model, 'estimated_ride_fare')->textInput(['placeholder' => 'Estimated Ride Fare']) */ ?>

    <?php /* echo $form->field($model, 'estimated_distance')->textInput(['placeholder' => 'Estimated Distance']) */ ?>

    <?php /* echo $form->field($model, 'estimated_time')->textInput(['placeholder' => 'Estimated Time']) */ ?>

    <?php /* echo $form->field($model, 'final_distance')->textInput(['placeholder' => 'Final Distance']) */ ?>

    <?php /* echo $form->field($model, 'final_time')->textInput(['placeholder' => 'Final Time']) */ ?>

    <?php /* echo $form->field($model, 'final_price')->textInput(['placeholder' => 'Final Price']) */ ?>

    <?php /* echo $form->field($model, 'coupon_code')->textInput(['placeholder' => 'Coupon Code']) */ ?>

    <?php /* echo $form->field($model, 'coupon_discount')->textInput(['placeholder' => 'Coupon Discount']) */ ?>

    <?php /* echo $form->field($model, 'coupon_applied_id')->textInput(['placeholder' => 'Coupon Applied']) */ ?>

    <?php /* echo $form->field($model, 'total_fare')->textInput(['placeholder' => 'Total Fare']) */ ?>

    <?php /* echo $form->field($model, 'otp')->textInput(['placeholder' => 'Otp']) */ ?>

    <?php /* echo $form->field($model, 'ride_start_time')->widget(\kartik\datecontrol\DateControl::classname(), [
        'type' => \kartik\datecontrol\DateControl::FORMAT_DATETIME,
        'saveFormat' => 'php:Y-m-d H:i:s',
        'ajaxConversion' => true,
        'options' => [
            'pluginOptions' => [
                'placeholder' => Yii::t('app', 'Choose Ride Start Time'),
                'autoclose' => true,
            ]
        ],
    ]); */ ?>

    <?php /* echo $form->field($model, 'ride_end_time')->widget(\kartik\datecontrol\DateControl::classname(), [
        'type' => \kartik\datecontrol\DateControl::FORMAT_DATETIME,
        'saveFormat' => 'php:Y-m-d H:i:s',
        'ajaxConversion' => true,
        'options' => [
            'pluginOptions' => [
                'placeholder' => Yii::t('app', 'Choose Ride End Time'),
                'autoclose' => true,
            ]
        ],
    ]); */ ?>

    <?php /* echo $form->field($model, 'status')->dropDownList($model->getStateOptions()) */ ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
