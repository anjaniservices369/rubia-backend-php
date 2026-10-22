<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\modules\admin\models\WebSetting;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\WebSetting */
/* @var $form yii\widgets\ActiveForm */

?>

<div class="web-setting-form">
<div class="card">
<div class="card-body">
    <?php $form = ActiveForm::begin(); ?>

    <?= $form->errorSummary($model); ?>
    <label><?= $model->name ?></label>
    <?php 
    echo $form->field($model, 'value')->widget(\kartik\file\FileInput::classname(), [
        'options' => ['accept' => 'image/*'],
        'pluginOptions' => [
            'initialPreviewShowDelete' => false,
        'initialPreview'=>[
            $model->faviconImage()
          ],
            'overwriteInitial'=>true,
        ]
    ])->label(false);?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        <?= Html::a(Yii::t('app', 'Cancel'), Yii::$app->request->referrer , ['class'=> 'btn btn-danger']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
</div>
</div>
