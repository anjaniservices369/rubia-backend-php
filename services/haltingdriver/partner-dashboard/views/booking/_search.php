<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\search\BookingSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="form-booking-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id', ['template' => '{input}'])->textInput(['style' => 'display:none']); ?>

    <?= $form->field($model, 'user_id')->widget(\kartik\widgets\Select2::classname(), [
        'data' => \yii\helpers\ArrayHelper::map(\app\modules\admin\models\User::find()->orderBy('id')->asArray()->all(), 'id', 'username'),
        'options' => ['placeholder' => 'Choose User'],
        'pluginOptions' => [
            'allowClear' => true
        ],
    ]); ?>

    <?= $form->field($model, 'rider_type')->textInput(['placeholder' => 'Rider Type']) ?>

    <?= $form->field($model, 'trip_type')->textInput(['placeholder' => 'Trip Type']) ?>

    <?= $form->field($model, 'pickup_latitude')->textInput(['maxlength' => true, 'placeholder' => 'Pickup Latitude']) ?>

    <?php /* echo $form->field($model, 'pickup_longitude')->textInput(['maxlength' => true, 'placeholder' => 'Pickup Longitude']) */ ?>

    <?php /* echo $form->field($model, 'drop_latitude')->textInput(['maxlength' => true, 'placeholder' => 'Drop Latitude']) */ ?>

    <?php /* echo $form->field($model, 'drop_longitude')->textInput(['maxlength' => true, 'placeholder' => 'Drop Longitude']) */ ?>

    <?php /* echo $form->field($model, 'car_type')->textInput(['placeholder' => 'Car Type']) */ ?>

    <?php /* echo $form->field($model, 'car_model')->textInput(['maxlength' => true, 'placeholder' => 'Car Model']) */ ?>

    <?php /* echo $form->field($model, 'pickup_date')->widget(\kartik\datecontrol\DateControl::classname(), [
        'type' => \kartik\datecontrol\DateControl::FORMAT_DATE,
        'saveFormat' => 'php:Y-m-d',
        'ajaxConversion' => true,
        'options' => [
            'pluginOptions' => [
                'placeholder' => 'Choose Pickup Date',
                'autoclose' => true
            ]
        ],
    ]); */ ?>

    <?php /* echo $form->field($model, 'pickup_time')->widget(\kartik\datecontrol\DateControl::className(), [
        'type' => \kartik\datecontrol\DateControl::FORMAT_TIME,
        'saveFormat' => 'php:H:i:s',
        'ajaxConversion' => true,
        'options' => [
            'pluginOptions' => [
                'placeholder' => 'Choose Pickup Time',
                'autoclose' => true
            ]
        ]
    ]); */ ?>

    <?php /* echo $form->field($model, 'estimate_price')->textInput(['maxlength' => true, 'placeholder' => 'Estimate Price']) */ ?>

    <?php /* echo $form->field($model, 'usage_hours')->textInput(['placeholder' => 'Usage Hours']) */ ?>

    <?php /* echo $form->field($model, 'coupon_code')->textInput(['maxlength' => true, 'placeholder' => 'Coupon Code']) */ ?>

    <?php /* echo $form->field($model, 'payment_method')->textInput(['placeholder' => 'Payment Method']) */ ?>

    <?php /* echo $form->field($model, 'status')->dropDownList($model->getStateOptions()) */ ?>

    <?php /* echo $form->field($model, 'created_on')->textInput(['maxlength' => true, 'placeholder' => 'Created On']) */ ?>

    <?php /* echo $form->field($model, 'updated_on')->textInput(['maxlength' => true, 'placeholder' => 'Updated On']) */ ?>

    <?php /* echo $form->field($model, 'create_user_id')->textInput(['placeholder' => 'Create User']) */ ?>

    <?php /* echo $form->field($model, 'update_user_id')->textInput(['placeholder' => 'Update User']) */ ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
