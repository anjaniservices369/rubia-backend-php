<?php

use yii\helpers\Html;
use kartik\form\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\DriverReferralEarnings */
/* @var $form yii\widgets\ActiveForm */

?>

<div class="driver-referral-earnings-form">

<?php $form = ActiveForm::begin([
    'type' => ActiveForm::TYPE_VERTICAL,
    'tooltipStyleFeedback' => true,
    'fieldConfig' => ['options' => ['class' => 'form-group col-xs-6 col-sm-6 col-md-6 col-lg-12']],
    'formConfig' => ['showErrors' => true],
]); ?>

  
    <?= $form->errorSummary($model); ?>

    
  
 

    <?= $form->field($model, 'driver_id')->widget(\kartik\widgets\Select2::classname(), [
        'data' => \yii\helpers\ArrayHelper::map(\app\modules\admin\models\User::find()->orderBy('id')->asArray()->all(), 'id', 'username'),
        'options' => ['placeholder' => Yii::t('app', 'Choose User')],
        'pluginOptions' => [
            'allowClear' => true
        ],
    ]); ?>

    <?= $form->field($model, 'earnings')->textInput(['maxlength' => true, 'placeholder' => 'Earnings']) ?>

    <?= $form->field($model, 'status')->dropDownList($model->getStateOptions()) ?>

<?php if($model->isNewRecord){ ?><?php } ?>    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>
  
    <?php ActiveForm::end(); ?>

</div>
