<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\search\NotificationSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="form-notification-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'title')->textInput(['maxlength' => true, 'placeholder' => 'Title']) ?>

    <?= $form->field($model, 'order_id')->textInput(['placeholder' => 'Order']) ?>

    <?= $form->field($model, 'user_id')->textInput(['placeholder' => 'User']) ?>

    <?= $form->field($model, 'module')->textInput(['maxlength' => true, 'placeholder' => 'Module']) ?>

    <?= $form->field($model, 'icon')->textInput(['maxlength' => true, 'placeholder' => 'Icon']) ?>

    <?php /* echo $form->field($model, 'mark_read')->textInput(['placeholder' => 'Mark Read']) */ ?>

    <?php /* echo $form->field($model, 'model_type')->textInput(['maxlength' => true, 'placeholder' => 'Model Type']) */ ?>

    <?php /* echo $form->field($model, 'check_on_ajax')->textInput(['placeholder' => 'Check On Ajax']) */ ?>

    <?php /* echo $form->field($model, 'status')->textInput(['placeholder' => 'Status']) */ ?>

    <?php /* echo $form->field($model, 'created_on')->widget(\kartik\datecontrol\DateControl::classname(), [
        'type' => \kartik\datecontrol\DateControl::FORMAT_DATETIME,
        'saveFormat' => 'php:Y-m-d H:i:s',
        'ajaxConversion' => true,
        'options' => [
            'pluginOptions' => [
                'placeholder' => Yii::t('app', 'Choose Created On'),
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
                'placeholder' => Yii::t('app', 'Choose Updated On'),
                'autoclose' => true,
            ]
        ],
    ]); */ ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
