<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\search\FavouriteLocationSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="form-favourite-location-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id', ['template' => '{input}'])->textInput(['style' => 'display:none']); ?>

    <?= $form->field($model, 'user_id')->textInput(['placeholder' => 'User']) ?>

    <?= $form->field($model, 'latitude')->textInput(['maxlength' => true, 'placeholder' => 'Latitude']) ?>

    <?= $form->field($model, 'logitude')->textInput(['maxlength' => true, 'placeholder' => 'Logitude']) ?>

    <?= $form->field($model, 'address_type')->textInput(['maxlength' => true, 'placeholder' => 'Address Type']) ?>

    <?php /* echo $form->field($model, 'address')->textInput(['maxlength' => true, 'placeholder' => 'Address']) */ ?>

    <?php /* echo $form->field($model, 'pincode')->textInput(['placeholder' => 'Pincode']) */ ?>

    <?php /* echo $form->field($model, 'status')->dropDownList($model->getStateOptions()) */ ?>

    <?php /* echo $form->field($model, 'created_on')->widget(\kartik\datecontrol\DateControl::classname(), [
        'type' => \kartik\datecontrol\DateControl::FORMAT_DATETIME,
        'saveFormat' => 'php:Y-m-d H:i:s',
        'ajaxConversion' => true,
        'options' => [
            'pluginOptions' => [
                'placeholder' => 'Choose Created On',
                'autoclose' => true,
            ]
        ],
    ]); */ ?>

    <?php /* echo $form->field($model, 'updated_on')->widget(\kartik\datecontrol\DateControl::classname(), [
        'type' => \kartik\datecontrol\DateControl::FORMAT_DATETIME,
        'saveFormat' => 'php:Y-m-d H:i:s',
        'ajaxConversion' => true,
        'options' => [
            'pluginOptions' => [
                'placeholder' => 'Choose Updated On',
                'autoclose' => true,
            ]
        ],
    ]); */ ?>

    <?php /* echo $form->field($model, 'update_user_id')->textInput(['placeholder' => 'Update User']) */ ?>

    <?php /* echo $form->field($model, 'create_user_id')->textInput(['placeholder' => 'Create User']) */ ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
