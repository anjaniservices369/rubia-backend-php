<?php
/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\Vehicals */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
?>
<div class="vehicals-form">
    <?php $form = ActiveForm::begin(); ?>
    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'vehicle_type')->textInput() ?>
    <?= $form->field($model, 'no_of_person')->textInput() ?>
    <?= $form->field($model, 'base_fare')->textInput() ?>
    <?= $form->field($model, 'min_distance')->textInput() ?>
    <?= $form->field($model, 'fare_per_distance')->textInput() ?>
    <?= $form->field($model, 'waiting_time_limit')->textInput() ?>
    <?= $form->field($model, 'charges_per_minute')->textInput() ?>
    <?= $form->field($model, 'fare_per_minute')->textInput() ?>
    <?= $form->field($model, 'cancellation_fee')->textInput() ?>
    <?= $form->field($model, 'admin_commison_type')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'admin_commison')->textInput() ?>
    <?= $form->field($model, 'status')->dropDownList([1 => 'Active', 2 => 'Inactive']) ?>
    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>
    <?php ActiveForm::end(); ?>
</div>