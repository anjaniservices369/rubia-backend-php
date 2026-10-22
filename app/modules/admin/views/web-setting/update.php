<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\WebSetting */

$this->title = Yii::t('app', 'Update {modelClass}: ', [
    'modelClass' => 'Web Setting',
]) . ' ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Web Settings'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->setting_id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="web-setting-update">

    <!-- <h1><?= Html::encode($this->title) ?></h1> -->
    <div class="card">
    <div class="card-body">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
</div>
</div>
