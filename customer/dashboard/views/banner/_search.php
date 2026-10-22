<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\search\BannerSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="form-banner-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id', ['template' => '{input}'])->textInput(['style' => 'display:none']); ?>

    <?= $form->field($model, 'banner_category_id')->widget(\kartik\widgets\Select2::classname(), [
        'data' => \yii\helpers\ArrayHelper::map(\app\modules\admin\models\BannerCategorys::find()->orderBy('id')->asArray()->all(), 'id', 'title'),
        'options' => ['placeholder' => Yii::t('app', 'Choose Banner categorys')],
        'pluginOptions' => [
            'allowClear' => true
        ],
    ]); ?>

    <?= $form->field($model, 'title')->textInput(['maxlength' => true, 'placeholder' => 'Title']) ?>

    <?= $form->field($model, 'image')->textInput(['maxlength' => true, 'placeholder' => 'Image']) ?>

    <?= $form->field($model, 'description')->widget(\mihaildev\ckeditor\CKEditor::className(),[
                'editorOptions' => [
                    'preset' => 'full',
                    'inline' => false, 
                ],
            ]) ?>

    <?php /* echo $form->field($model, 'type_id')->textInput(['placeholder' => 'Type']) */ ?>

    <?php /* echo $form->field($model, 'sort_order')->textInput(['placeholder' => 'Sort Order']) */ ?>

    <?php /* echo $form->field($model, 'status')->dropDownList($model->getStateOptions()) */ ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
