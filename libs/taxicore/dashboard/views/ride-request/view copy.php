<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use kartik\grid\GridView;
use app\models\User;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\RideRequest */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Ride Requests'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ride-request-view">
<div class="card">
       <div class="card-body">
    <div class="row">
        <div class="col-sm-9">
            <h2><?= Yii::t('app', 'Ride Request').' '. Html::encode($this->title) ?></h2>
        </div>
        
    </div>
    </div>
    </div>
    <div class="card">
       <div class="card-body">

    <div class="row">
<?php 
    $gridColumn = [
        ['attribute' => 'id', 'visible' => false],
        'user_id',
        'driver_id',
        'pickup_address',
        'pickup_pincode',
        'pickup_latitude',
        'pickup_longitude',
        'drop_address',
        'drop_pincode',
        'drop_latitude',
        'drop_longitude',
        'vehical_type',
        'estimated_ride_fare',
        'estimated_distance',
        'estimated_time',
        'final_distance',
        'final_time',
        'final_price',
        'coupon_code',
        'coupon_discount',
        'coupon_applied_id',
        'total_fare',
        'otp',
        'ride_start_time',
        'ride_end_time',
        'status',
    ];
    echo DetailView::widget([
        'model' => $model,
        'attributes' => $gridColumn
    ]);
?>
</div>
</div>
    </div>
</div>

