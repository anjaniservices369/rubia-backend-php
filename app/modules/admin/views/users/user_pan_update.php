<?php
use app\modules\admin\forms\PanDetailsForm;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\User */
/* @var $panDetailsForm app\modules\admin\forms\PanDetailsForm */

$this->title = Yii::t('app', 'Update Pan Card') . ' ' . Html::encode($model->username);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['user-pan-update', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update Pan Card');
?>

<div class="user-pan-update">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-sm-9">
                    <h2><?= $this->title ?></h2>
                </div>
            </div>
            <div class="form-body">
                <?php $form = ActiveForm::begin(['method' => 'post', 'enableClientValidation' => true]); ?>

                <?= $form->field($driverPanCardsModel, 'pan_number')->textInput(['maxlength' => true]) ?>
                <?= $form->field($driverPanCardsModel, 'pan_card')->fileInput() ?>
                <?php

                                        // echo $form->field($driverPanCardsModel, 'frontFile')->widget(FileInput::classname(), [
                                        //     'options' => ['multiple' => false, 'accept' => 'image/*'],
                                        //     'pluginOptions' => [
                                        //         'previewFileType' => 'frontFile', 'initialPreview' => [
                                        //             $model->frontFile
                                        //         ],
                                        //         'initialPreviewAsData' => true,

                                        //         'overwriteInitial' => true,

                                        //         'showUpload' => false,
                                        //     ]
                                        // ]);


                                        ?>

                <div class="form-group">
                    <?= Html::submitButton('Update', ['class' => 'btn btn-primary']) ?>
                </div>

                <?php ActiveForm::end(); ?>

            </div>
        </div>
    </div>
</div>
