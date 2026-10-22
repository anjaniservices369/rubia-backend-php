<?php

use app\models\User;
use yii\helpers\Html;
use kartik\form\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\TaxiPayout */
/* @var $form yii\widgets\ActiveForm */

?>

<div class="taxi-payout-form">

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
        <div class='col-lg-6 '> <?= $form->field($model, 'id', ['template' => '{input}'])->textInput(['style' => 'display:none']); ?>

        </div>
        <div class='col-lg-6'> <?= $form->field($model, 'id', ['template' => '{input}'])->textInput(['style' => 'display:none']);  ?> </div>

        <div class='col-lg-6'> <?= $form->field($model, 'driver_id')->widget(\kartik\widgets\Select2::classname(), [
                                    'data' => \yii\helpers\ArrayHelper::map(User::find()->where(['user_role' => User::ROLE_TAXI])->orderBy('id')->asArray()->all(), 'id', 'contact_no'),
                                    'options' => ['placeholder' => Yii::t('app', 'Choose Driver')],
                                    'pluginOptions' => [
                                        'allowClear' => true
                                    ],
                                ]);  ?> </div>

        <div class='col-lg-6'> <?= $form->field($model, 'amount')->textInput(['placeholder' => 'Amount'])  ?> </div>

        <div class='col-lg-6'> <?= $form->field($model, 'payment_type')->dropDownList($model->getPaymentTypeOptions()) ?> </div>

        <div class='col-lg-6'> <?= $form->field($model, 'method_reason')->textInput(['maxlength' => true, 'placeholder' => 'Method Reason'])  ?> </div>


        <div class='col-lg-6'> <?= $form->field($model, 'refrence_id')->textInput(['placeholder' => 'Refrence'])  ?> </div>

        <div class='col-lg-6'> <?= $form->field($model, 'status')->dropDownList($model->getStateOptions())  ?> </div>

    </div> <?php if ($model->isNewRecord) { ?><?php } ?> <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>