<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\search\DriverEarningsSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="form-driver-earnings-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

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

    <?php /* echo $form->field($model, 'admin_per')->textInput(['placeholder' => 'Admin Per']) */ ?>

    <?php /* echo $form->field($model, 'admin_earning')->textInput(['placeholder' => 'Admin Earning']) */ ?>

    <?php /* echo $form->field($model, 'driver_earning')->textInput(['placeholder' => 'Driver Earning']) */ ?>

    <?php /* echo $form->field($model, 'payment_method')->textInput(['placeholder' => 'Payment Method']) */ ?>

    <?php /* echo $form->field($model, 'status')->dropDownList($model->getStateOptions()) */ ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
