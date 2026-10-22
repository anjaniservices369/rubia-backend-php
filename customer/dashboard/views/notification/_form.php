<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\Notification */
/* @var $form yii\widgets\ActiveForm */

?>

<div class="notification-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->errorSummary($model); ?>

    <?= $form->field($model, 'title')->textInput(['maxlength' => true, 'placeholder' => 'Title']) ?>

    <?= $form->field($model, 'order_id')->textInput(['placeholder' => 'Order']) ?>

    <?= $form->field($model, 'user_id')->textInput(['placeholder' => 'User']) ?>

    <?= $form->field($model, 'module')->textInput(['maxlength' => true, 'placeholder' => 'Module']) ?>

    <?= $form->field($model, 'icon')->textInput(['maxlength' => true, 'placeholder' => 'Icon']) ?>

    <?= $form->field($model, 'mark_read')->textInput(['placeholder' => 'Mark Read']) ?>

    <?= $form->field($model, 'model_type')->textInput(['maxlength' => true, 'placeholder' => 'Model Type']) ?>

    <?= $form->field($model, 'check_on_ajax')->textInput(['placeholder' => 'Check On Ajax']) ?>

    <?= $form->field($model, 'status')->textInput(['placeholder' => 'Status']) ?>

    <?= $form->field($model, 'created_on')->widget(\kartik\datecontrol\DateControl::classname(), [
        'type' => \kartik\datecontrol\DateControl::FORMAT_DATETIME,
        'saveFormat' => 'php:Y-m-d H:i:s',
        'ajaxConversion' => true,
        'options' => [
            'pluginOptions' => [
                'placeholder' => Yii::t('app', 'Choose Created On'),
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
                'placeholder' => Yii::t('app', 'Choose Updated On'),
                'autoclose' => true,
            ]
        ],
    ]); ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        <?= Html::a(Yii::t('app', 'Cancel'), Yii::$app->request->referrer , ['class'=> 'btn btn-danger']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
