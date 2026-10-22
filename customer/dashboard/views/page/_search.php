<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\search\PageSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="form-page-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id', ['template' => '{input}'])->textInput(['style' => 'display:none']); ?>

    <?= $form->field($model, 'title')->textInput(['maxlength' => true, 'placeholder' => 'Title']) ?>

    <?= $form->field($model, 'slug')->textInput(['maxlength' => true, 'placeholder' => 'Slug']) ?>

    <?= $form->field($model, 'description')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'state_id')->textInput(['placeholder' => 'State']) ?>

    <?php /* echo $form->field($model, 'type_id')->textInput(['placeholder' => 'Type']) */ ?>

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

    <?php /* echo $form->field($model, 'create_user_id')->textInput(['placeholder' => 'Create User']) */ ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
