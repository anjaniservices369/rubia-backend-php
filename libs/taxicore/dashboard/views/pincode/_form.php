<?php

use app\modules\admin\models\City;
use yii\helpers\Html;
use kartik\form\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\Pincode */
/* @var $form yii\widgets\ActiveForm */

?>

<div class="pincode-form">

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

        <div class='col-lg-6'> <?= $form->field($model, 'city_id')->widget(\kartik\widgets\Select2::classname(), [
                                    'data' => \yii\helpers\ArrayHelper::map(\app\modules\admin\models\City::find()->where(['status'=>City::STATUS_ACTIVE])->orderBy('id')->asArray()->all(), 'id', 'name'),
                                    'options' => ['placeholder' => Yii::t('app', 'Choose City')],
                                    'pluginOptions' => [
                                        'allowClear' => true,

                                    ],
                                ]);  ?> </div>

        <div class='col-lg-6'> <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'placeholder' => 'Pincode'])  ?> </div>

        
        <div class='col-lg-6'> <?= $form->field($model, 'vehical_id')->dropDownList(\app\modules\admin\models\Vehicals::getVehicleTypeOptions())  ?> </div>


        <div class='col-lg-6'> <?= $form->field($model, 'status')->dropDownList($model->getStateOptions())  ?> </div>

    </div> <?php if ($model->isNewRecord) { ?><?php } ?> <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>