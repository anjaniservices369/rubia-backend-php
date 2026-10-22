<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\search\DriverDetailsSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="form-driver-details-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id', ['template' => '{input}'])->textInput(['style' => 'display:none']); ?>

    <?= $form->field($model, 'user_id')->widget(\kartik\widgets\Select2::classname(), [
        'data' => \yii\helpers\ArrayHelper::map(\app\models\User::find()->orderBy('id')->asArray()->all(), 'id', 'username'),
        'options' => ['placeholder' => Yii::t('app', 'Choose User')],
        'pluginOptions' => [
            'allowClear' => true
        ],
    ]); ?>

    <?= $form->field($model, 'commission_percent')->textInput(['placeholder' => 'Commission Percent']) ?>

    <?= $form->field($model, 'city_id')->widget(\kartik\widgets\Select2::classname(), [
        'data' => \yii\helpers\ArrayHelper::map(\app\modules\admin\models\City::find()->orderBy('id')->asArray()->all(), 'id', 'name'),
        'options' => ['placeholder' => Yii::t('app', 'Choose City')],
        'pluginOptions' => [
            'allowClear' => true
        ],
    ]); ?>

    <?= $form->field($model, 'address')->textInput(['maxlength' => true, 'placeholder' => 'Address']) ?>
    

    <?php /* echo $form->field($model, 'license_no')->textInput(['maxlength' => true, 'placeholder' => 'License No']) */ ?>

    <?php /* echo $form->field($model, 'proof_of_license')->textInput(['maxlength' => true, 'placeholder' => 'Proof Of License']) */ ?>

    <?php /* echo $form->field($model, 'proof_of_license_back')->textInput(['maxlength' => true, 'placeholder' => 'Proof Of License Back']) */ ?>

    <?php /* echo $form->field($model, 'vehical_id')->widget(\kartik\widgets\Select2::classname(), [
        'data' => \yii\helpers\ArrayHelper::map(\app\modules\admin\models\Vehicals::find()->orderBy('id')->asArray()->all(), 'id', 'title'),
        'options' => ['placeholder' => Yii::t('app', 'Choose Vehicals')],
        'pluginOptions' => [
            'allowClear' => true
        ],
    ]); */ ?>

    <?php /* echo $form->field($model, 'rc_number')->textInput(['maxlength' => true, 'placeholder' => 'Rc Number']) */ ?>

    <?php /* echo $form->field($model, 'vehical_number')->textInput(['maxlength' => true, 'placeholder' => 'Vehical Number']) */ ?>

    <?php /* echo $form->field($model, 'rc_proof')->textInput(['maxlength' => true, 'placeholder' => 'Rc Proof']) */ ?>

    <?php /* echo $form->field($model, 'rc_proof_back')->textInput(['maxlength' => true, 'placeholder' => 'Rc Proof Back']) */ ?>

    <?php /* echo $form->field($model, 'adhaar_number')->textInput(['maxlength' => true, 'placeholder' => 'Adhaar Number']) */ ?>

    <?php /* echo $form->field($model, 'adhaar_front')->textInput(['maxlength' => true, 'placeholder' => 'Adhaar Front']) */ ?>

    <?php /* echo $form->field($model, 'adhaar_back')->textInput(['maxlength' => true, 'placeholder' => 'Adhaar Back']) */ ?>

    <?php /* echo $form->field($model, 'pan_number')->textInput(['maxlength' => true, 'placeholder' => 'Pan Number']) */ ?>

    <?php /* echo $form->field($model, 'pan_front')->textInput(['maxlength' => true, 'placeholder' => 'Pan Front']) */ ?>

    <?php /* echo $form->field($model, 'pan_back')->textInput(['maxlength' => true, 'placeholder' => 'Pan Back']) */ ?>

    <?php /* echo $form->field($model, 'is_verified')->textInput(['placeholder' => 'Is Verified']) */ ?>

    <?php /* echo $form->field($model, 'status')->dropDownList($model->getStateOptions()) */ ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
