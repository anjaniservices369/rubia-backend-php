<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\BookingLogs */

$this->title = Yii::t('app', 'Create Booking Logs');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Booking Logs'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="booking-logs-create">
    <div class="card">
       <div class="card-body">
    <!-- <h1><?= Html::encode($this->title) ?></h1> -->

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
    </div>
    </div>
</div>
