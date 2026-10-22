<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\search\CityRateCardSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="form-city-rate-card-search">

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

    <?= $form->field($model, 'trip_type')->textInput(['placeholder' => 'Trip Type']) ?>

    <?= $form->field($model, 'ride_type')->textInput(['placeholder' => 'Ride Type']) ?>

    <?= $form->field($model, 'base_hour')->textInput(['placeholder' => 'Base Hour']) ?>

    <?php /* echo $form->field($model, 'base_km')->textInput(['placeholder' => 'Base Km']) */ ?>

    <?php /* echo $form->field($model, 'base_fare')->textInput(['placeholder' => 'Base Fare']) */ ?>

    <?php /* echo $form->field($model, 'extra_km_charge')->textInput(['placeholder' => 'Extra Km Charge']) */ ?>

    <?php /* echo $form->field($model, 'extra_min_charge')->textInput(['placeholder' => 'Extra Min Charge']) */ ?>

    <?php /* echo $form->field($model, 'night_charge')->textInput(['placeholder' => 'Night Charge']) */ ?>

    <?php /* echo $form->field($model, 'out_station_charge')->textInput(['placeholder' => 'Out Station Charge']) */ ?>

    <?php /* echo $form->field($model, 'facilities')->textInput(['placeholder' => 'Facilities']) */ ?>

    <?php /* echo $form->field($model, 'status')->dropDownList($model->getStateOptions()) */ ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
