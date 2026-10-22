<?php
use app\modules\admin\forms\AadharDetailsForm;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\User */
/* @var $aadharDetailsForm app\modules\admin\forms\AadharDetailsForm */

$this->title = Yii::t('app', 'Update Aadhar Card') . ' ' . Html::encode($model->username);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['user-aadhar-update', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update Aadhar Card');
?>

<div class="user-aadhar-update">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-sm-9">
                    <h2><?= $this->title ?></h2>
                </div>
            </div>
            <div class="form-body">

            
            <?php $form = ActiveForm::begin(['method' => 'post', 'enableClientValidation' => true]); ?>


                <?= $form->field($driverAadharCardModel, 'aadhar_number')->textInput(['maxlength' => true]) ?>

                <?= $form->field($driverAadharCardModel, 'aadhar_card_front')->fileInput() ?>
                <?= $form->field($driverAadharCardModel, 'aadhar_card_back')->fileInput() ?>

                <div class="form-group">
                    <?= Html::submitButton('Update', ['class' => 'btn btn-primary']) ?>
                </div>

                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
