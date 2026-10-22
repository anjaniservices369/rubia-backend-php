<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\BookingLogs */

$this->title = Yii::t('app', 'Update {modelClass}: ', [
    'modelClass' => 'Booking Logs',
]) . ' ' . $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Booking Logs'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', ]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="booking-logs-update">
<div class="card">
       <div class="card-body">
    <!-- <h1><?= Html::encode($this->title) ?></h1> -->

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
</div>
</div>
