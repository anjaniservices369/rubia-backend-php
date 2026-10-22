<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\search\CouponSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="form-coupon-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id', ['template' => '{input}'])->textInput(['style' => 'display:none']); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'placeholder' => 'Name']) ?>

    <?= $form->field($model, 'description')->widget(\mihaildev\ckeditor\CKEditor::className(),[
                'editorOptions' => [
                    'preset' => 'full',
                    'inline' => false, 
                ],
            ]) ?>

    <?= $form->field($model, 'code')->textInput(['maxlength' => true, 'placeholder' => 'Code']) ?>

    <?= $form->field($model, 'discount')->textInput(['placeholder' => 'Discount']) ?>

    <?php /* echo $form->field($model, 'max_discount')->textInput(['placeholder' => 'Max Discount']) */ ?>

    <?php /* echo $form->field($model, 'min_ride_amount')->textInput(['placeholder' => 'Min Ride Amount']) */ ?>

    <?php /* echo $form->field($model, 'max_use')->textInput(['placeholder' => 'Max Use']) */ ?>

    <?php /* echo $form->field($model, 'max_use_of_coupon')->textInput(['placeholder' => 'Max Use Of Coupon']) */ ?>

    <?php /* echo $form->field($model, 'start_date')->widget(\kartik\datecontrol\DateControl::classname(), [
        'type' => \kartik\datecontrol\DateControl::FORMAT_DATE,
        'saveFormat' => 'php:Y-m-d',
        'ajaxConversion' => true,
        'options' => [
            'pluginOptions' => [
                'placeholder' => Yii::t('app', 'Choose Start Date'),
                'autoclose' => true
            ]
        ],
    ]); */ ?>

    <?php /* echo $form->field($model, 'end_date')->widget(\kartik\datecontrol\DateControl::classname(), [
        'type' => \kartik\datecontrol\DateControl::FORMAT_DATE,
        'saveFormat' => 'php:Y-m-d',
        'ajaxConversion' => true,
        'options' => [
            'pluginOptions' => [
                'placeholder' => Yii::t('app', 'Choose End Date'),
                'autoclose' => true
            ]
        ],
    ]); */ ?>

    <?php /* echo $form->field($model, 'is_global')->textInput(['placeholder' => 'Is Global']) */ ?>

    <?php /* echo $form->field($model, 'status')->dropDownList($model->getStateOptions()) */ ?>

    <?php /* echo $form->field($model, 'type_id')->textInput(['placeholder' => 'Type']) */ ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
