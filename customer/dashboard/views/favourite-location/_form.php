<?php

use yii\helpers\Html;
use kartik\form\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\FavouriteLocation */
/* @var $form yii\widgets\ActiveForm */

?>

<div class="favourite-location-form">

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

    <?= $form->field($model, 'user_id')->textInput(['placeholder' => 'User']) ?>

    <?= $form->field($model, 'latitude')->textInput(['maxlength' => true, 'placeholder' => 'Latitude']) ?>

    <?= $form->field($model, 'logitude')->textInput(['maxlength' => true, 'placeholder' => 'Logitude']) ?>

    <?= $form->field($model, 'address_type')->textInput(['maxlength' => true, 'placeholder' => 'Address Type']) ?>

    <?= $form->field($model, 'address')->textInput(['maxlength' => true, 'placeholder' => 'Address']) ?>

    <?= $form->field($model, 'pincode')->textInput(['placeholder' => 'Pincode']) ?>

    <?= $form->field($model, 'status')->dropDownList($model->getStateOptions()) ?>

    <?= $form->field($model, 'created_on')->widget(\kartik\datecontrol\DateControl::classname(), [
        'type' => \kartik\datecontrol\DateControl::FORMAT_DATETIME,
        'saveFormat' => 'php:Y-m-d H:i:s',
        'ajaxConversion' => true,
        'options' => [
            'pluginOptions' => [
                'placeholder' => 'Choose Created On',
                'autoclose' => true,
            ]
        ],
    ]); ?>

    <?= $form->field($model, 'updated_on')->widget(\kartik\datecontrol\DateControl::classname(), [
        'type' => \kartik\datecontrol\DateControl::FORMAT_DATETIME,
        'saveFormat' => 'php:Y-m-d H:i:s',
        'ajaxConversion' => true,
        'options' => [
            'pluginOptions' => [
                'placeholder' => 'Choose Updated On',
                'autoclose' => true,
            ]
        ],
    ]); ?>

    <?= $form->field($model, 'update_user_id')->textInput(['placeholder' => 'Update User']) ?>

    <?= $form->field($model, 'create_user_id')->textInput(['placeholder' => 'Create User']) ?>

<?php if($model->isNewRecord){ ?><?php } ?>    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>
  
    <?php ActiveForm::end(); ?>

</div>
