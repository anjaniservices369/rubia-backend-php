<?php

use app\modules\admin\models\base\DriverBankDetails;
use app\modules\admin\forms\BankDetailsForm;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Users */

$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Driver Details'), 'url' => ['user-bank-update', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'View Bank Details');
?>
<div class="user-bank-update">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-sm-9">
                    <h2><?= Yii::t('app', 'Driver Details').' '. Html::encode($model->username) ?></h2>
                </div>
                <div class="col-sm-3" style="margin-top: 15px">
                    <?= Html::a(Yii::t('app', 'Update Bank Details'), ['user-bank-update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
                </div>
            </div>
            <div class="form-body">
            <?php $form = ActiveForm::begin(['method' => 'post', 'enableClientValidation' => true]); ?>

            <?= $form->field($bankDetailsForm, 'account_holder_name')->textInput(['maxlength' => true]) ?>
            <?= $form->field($bankDetailsForm, 'account_number')->textInput(['maxlength' => true]) ?>
            <?= $form->field($bankDetailsForm, 'ifsc')->textInput(['maxlength' => true]) ?>
            <?= $form->field($bankDetailsForm, 'bank_name')->textInput(['maxlength' => true]) ?>
            <?= $form->field($bankDetailsForm, 'branch_name')->textInput(['maxlength' => true]) ?>

             <?= $form->field($bankDetailsForm, 'upi')->textInput(['maxlength' => true]) ?>

            <div class="form-group">
                <?= Html::submitButton('Update', ['class' => 'btn btn-primary']) ?>
            </div>

            <?php ActiveForm::end(); ?>

                
            </div>
        </div>
    </div>
</div>