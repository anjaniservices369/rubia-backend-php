<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\search\RideCommisionSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="form-ride-commision-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id', ['template' => '{input}'])->textInput(['style' => 'display:none']); ?>

    <?= $form->field($model, 'city_id')->widget(\kartik\widgets\Select2::classname(), [
        'data' => \yii\helpers\ArrayHelper::map(\app\modules\admin\models\City::find()->orderBy('id')->asArray()->all(), 'id', 'name'),
        'options' => ['placeholder' => Yii::t('app', 'Choose City')],
        'pluginOptions' => [
            'allowClear' => true
        ],
    ]); ?>

    <?= $form->field($model, 'vehicle_id')->widget(\kartik\widgets\Select2::classname(), [
        'data' => \yii\helpers\ArrayHelper::map(\app\modules\admin\models\Vehicals::find()->orderBy('id')->asArray()->all(), 'id', 'title'),
        'options' => ['placeholder' => Yii::t('app', 'Choose Vehicles ')],
        'pluginOptions' => [
            'allowClear' => true
        ],
    ]); ?>

    <?= $form->field($model, 'base_fare')->textInput(['placeholder' => 'Base Fare']) ?>

    <?= $form->field($model, 'min_distance')->textInput(['placeholder' => 'Min Distance']) ?>

    <?php /* echo $form->field($model, 'fare_per_distance')->textInput(['placeholder' => 'Fare Per Distance']) */ ?>

    <?php /* echo $form->field($model, 'waiting_time_limit')->textInput(['placeholder' => 'Waiting Time Limit']) */ ?>

    <?php /* echo $form->field($model, 'charges_per_minute')->textInput(['placeholder' => 'Charges Per Minute']) */ ?>

    <?php /* echo $form->field($model, 'fare_per_minute')->textInput(['placeholder' => 'Fare Per Minute']) */ ?>

    <?php /* echo $form->field($model, 'no_of_person')->textInput(['placeholder' => 'No Of Person']) */ ?>

    <?php /* echo $form->field($model, 'commision')->textInput(['placeholder' => 'Commision']) */ ?>

    <?php /* echo $form->field($model, 'status')->dropDownList($model->getStateOptions()) */ ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
