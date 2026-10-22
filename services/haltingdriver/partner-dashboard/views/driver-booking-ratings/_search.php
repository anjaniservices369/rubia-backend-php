<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\search\DriverBookingRatingsSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="form-driver-booking-ratings-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id', ['template' => '{input}'])->textInput(['style' => 'display:none']); ?>

    <?= $form->field($model, 'driver_id')->widget(\kartik\widgets\Select2::classname(), [
        'data' => \yii\helpers\ArrayHelper::map(\app\modules\admin\models\User::find()->orderBy('id')->asArray()->all(), 'id', 'username'),
        'options' => ['placeholder' => Yii::t('app', 'Choose User')],
        'pluginOptions' => [
            'allowClear' => true
        ],
    ]); ?>

    <?= $form->field($model, 'booking_id')->widget(\kartik\widgets\Select2::classname(), [
        'data' => \yii\helpers\ArrayHelper::map(\app\modules\admin\models\Booking::find()->orderBy('id')->asArray()->all(), 'id', 'id'),
        'options' => ['placeholder' => Yii::t('app', 'Choose Booking')],
        'pluginOptions' => [
            'allowClear' => true
        ],
    ]); ?>

    <?= $form->field($model, 'rating')->textInput(['placeholder' => 'Rating']) ?>

    <?= $form->field($model, 'review')->textarea(['rows' => 6]) ?>

    <?php /* echo $form->field($model, 'status')->dropDownList($model->getStateOptions()) */ ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
