<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\search\DriverCancelDuplicateSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="driver-cancel-duplicate-search">
    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <!-- Remove the line below if 'id' is not a valid property -->
    <?php // echo $form->field($model, 'id')->textInput(['style' => 'display:none']); ?>

    <!-- Your other form fields here -->

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
