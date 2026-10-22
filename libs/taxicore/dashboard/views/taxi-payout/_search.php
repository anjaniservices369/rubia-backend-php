<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\search\TaxiPayoutSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="form-taxi-payout-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id', ['template' => '{input}'])->textInput(['style' => 'display:none']); ?>

    <?= $form->field($model, 'driver_id')->textInput(['placeholder' => 'Driver']) ?>

    <?= $form->field($model, 'amount')->textInput(['placeholder' => 'Amount']) ?>

    <?= $form->field($model, 'payment_type')->textInput(['placeholder' => 'Payment Type']) ?>

    <?= $form->field($model, 'method_reason')->textInput(['maxlength' => true, 'placeholder' => 'Method Reason']) ?>

    <?php /* echo $form->field($model, 'type_id')->textInput(['placeholder' => 'Type']) */ ?>

    <?php /* echo $form->field($model, 'refrence_id')->textInput(['placeholder' => 'Refrence']) */ ?>

    <?php /* echo $form->field($model, 'status')->dropDownList($model->getStateOptions()) */ ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
