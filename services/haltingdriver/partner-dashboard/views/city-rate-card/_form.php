<?php

use yii\helpers\Html;
use kartik\form\ActiveForm;



/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\CityRateCard */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="city-rate-card-form">

    <?php $form = ActiveForm::begin([
        'id' => 'login-form-inline',
        'type' => ActiveForm::TYPE_VERTICAL,
        'tooltipStyleFeedback' => true, // shows tooltip styled validation error feedback
        'fieldConfig' => [
            'options' => [
                'class' => 'form-group col-xs-6 col-sm-6 col-md-6 col-lg-12',
            ],
        ], // spacing field groups
        'formConfig' => ['showErrors' => true],
        // set style for proper tooltips error display
    ]); ?>
  
    <?= $form->errorSummary($model) ?>
  


    <?= $form
        ->field($model, 'id', ['template' => '{input}'])
        ->textInput(['style' => 'display:none']) ?>

 

<?= $form
    ->field($model, 'city_id')
    ->textInput([
        'placeholder' => 'city',
        'value' => $city_id,
        'readonly' => true,
        'style' => 'display:none',
    ])
    ->label(false) ?>


    <div class="row">
        <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
   

<?= $form
    ->field($model, 'trip_type')
    ->hiddenInput(['value' => $trip_type])
    ->label(false) ?>



    <?= $form
        ->field($model, 'base_hour')
        ->textInput(['placeholder' => 'Base Hour']) ?>

<?= $form->field($model, 'base_km')->textInput(['placeholder' => 'Base Km']) ?>
<?= $form
    ->field($model, 'extra_min_charge')
    ->textInput(['placeholder' => 'Extra Min Charge']) ?>

<?= $form
    ->field($model, 'night_charge')
    ->textInput(['placeholder' => 'Night Charge']) ?>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">

  
<?= $form
    ->field($model, 'ride_type')
    ->hiddenInput(['value' => $ride_type])
    ->label(false) ?>


    <?= $form
        ->field($model, 'base_fare')
        ->textInput(['placeholder' => 'Base Fare']) ?>

<?= $form
    ->field($model, 'extra_km_charge')
    ->textInput(['placeholder' => 'Extra Km Charge']) ?>
<?= $form
    ->field($model, 'out_station_charge')
    ->textInput(['placeholder' => 'Out Station Charge']) ?>

<?= $form
    ->field($model, 'facilities')
    ->textInput(['placeholder' => 'Facilities']) ?>
            </div>
    </div>
    

    <?= $form
        ->field($model, 'status')
        ->dropDownList($model->getStateOptions()) ?>

<?php if ($model->isNewRecord) {
} ?>    <div class="form-group">
        <?= Html::submitButton(
            $model->isNewRecord
                ? Yii::t('app', 'Create')
                : Yii::t('app', 'Update'),
            [
                'class' => $model->isNewRecord
                    ? 'btn btn-success'
                    : 'btn btn-primary',
            ]
        ) ?>
    </div>
  
    <?php ActiveForm::end(); ?>

</div>
<script>
    // JavaScript/jQuery code to hide/show the base_km and extra_km_charge fields based on trip type and ride type selection
    $(document).ready(function () {
        var tripTypeDropdown = $('#cityratecard-trip_type');
        var rideTypeRadios = $('#cityratecard-ride_type');
        var baseKmField = $('#cityratecard-base_km').closest('.form-group');
        var extraKmChargeField = $('#cityratecard-extra_km_charge').closest('.form-group');

        // Hide/show the base_km and extra_km_charge fields on page load
        toggleFields();

        // Hide/show the base_km and extra_km_charge fields on trip type or ride type change
        tripTypeDropdown.change(toggleFields);
        rideTypeRadios.change(toggleFields);

        function toggleFields() {
            var tripType = tripTypeDropdown.val();
            var rideType = rideTypeRadios.val();
           
            if (tripType === '<?= \app\modules\admin\models\CityRateCard::TRIP_TYPE_INTERCITY ?>'
                && rideType === '<?= \app\modules\admin\models\CityRateCard::RIDER_TYPE_ROUNDTRIP ?>') {
                baseKmField.hide();
                extraKmChargeField.hide();
            } else {
                baseKmField.show();
                extraKmChargeField.show();
            }
        }
    });
</script>