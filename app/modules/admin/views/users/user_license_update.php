<?php

use app\modules\admin\forms\PanDetailsForm;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\User */
/* @var $drivingLicenseModel app\modules\admin\models\DrivingLicense */

$this->title = Yii::t('app', 'Update Driving License') . ' ' . Html::encode($model->username);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['user-license-update', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update Driving License');
?>

<div class="user-license-update">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-sm-9">
                    <h2><?= $this->title ?></h2>
                </div>
            </div>
            <div class="form-body">
                <?php $form = ActiveForm::begin(['method' => 'post', 'enableClientValidation' => true]); ?>
                <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken); ?>

                <?= $form->field($drivingLicenseModel, 'driving_license_number')->textInput(['maxlength' => true]) ?>
                <?= $form->field($drivingLicenseModel, 'driving_license_front')->fileInput() ?>
                <?= $form->field($drivingLicenseModel, 'driving_license_back')->fileInput() ?>
                <?= $form->field($drivingLicenseModel, 'issued_date')->textInput(['type' => 'date']) ?>
                <?= $form->field($drivingLicenseModel, 'expire_date')->textInput(['type' => 'date']) ?>

                <div class="form-group">
                    <?= Html::submitButton('Update', ['class' => 'btn btn-primary']) ?>
                </div>

                <?php ActiveForm::end(); ?>

            </div>
        </div>
    </div>
</div>
