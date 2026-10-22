<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\DriverBookingRatings */

$this->title = Yii::t('app', 'Create Driver Booking Ratings');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Driver Booking Ratings'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="driver-booking-ratings-create">
    <div class="card">
       <div class="card-body">
    <!-- <h1><?= Html::encode($this->title) ?></h1> -->

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
    </div>
    </div>
</div>
