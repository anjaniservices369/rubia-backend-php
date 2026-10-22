<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use kartik\grid\GridView;
use app\models\User;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\TaxiRating */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Taxi Ratings'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="taxi-rating-view">
<div class="card">
       <div class="card-body">
    <div class="row">
        <div class="col-sm-9">
            <h2><?= Yii::t('app', 'Taxi Rating').' '. Html::encode($this->title) ?></h2>
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
        [
            'attribute' => 'taxiDriver.username',
            'label' => Yii::t('app', 'Driver'),
        ],
        [
            'value' => $model->customer['name'] ?: $model->customer['phone'],
            'label' => Yii::t('app', 'User'),
        ],
        [
            'attribute' => 'ride.id',
            'label' => Yii::t('app', 'Ride'),
        ],
        'rating',
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
    <div class="card">
       <div class="card-body">
    <div class="row">
        <h4>User<?= ' '. Html::encode($this->title) ?></h4>
    </div>
    <?php 
    $gridColumnUser = [
        ['attribute' => 'id', 'visible' => false],
        'username',
        'auth_key',
        'password_hash',
        'password_reset_token',
        'email',
        'first_name',
        'last_name',
        'contact_no',
        'alternative_contact',
        'date_of_birth',
        'gender',
        'description',
        'address',
        'profile_image',
        'user_role',
        'oauth_client_user_id',
        'oauth_client',
        'access_token',
        'device_token',
        'device_type',
        'status',
        'access_token_status',
        'city_id',
        'online_status',
        'lat',
        'lng',
        'blood_group',
        'referal_code',
        'document_steps',
        'form_steps',
        'referal_id',
        'is_deleted',
        'info_delete',
        'created_at',
        'updated_at',
    ];
    echo DetailView::widget([
        'model' => $model->taxiDriver,
        'attributes' => $gridColumnUser    ]);
    ?>
    </div>
    </div>
    <div class="card">
       <div class="card-body">
    <div class="row">
        <h4>User<?= ' '. Html::encode($this->title) ?></h4>
    </div>
    <?php 
    $gridColumnUser = [
        ['attribute' => 'id', 'visible' => false],
        'username',
        'auth_key',
        'password_hash',
        'password_reset_token',
        'email',
        'first_name',
        'last_name',
        'contact_no',
        'alternative_contact',
        'date_of_birth',
        'gender',
        'description',
        'address',
        'profile_image',
        'user_role',
        'oauth_client_user_id',
        'oauth_client',
        'access_token',
        'device_token',
        'device_type',
        'status',
        'access_token_status',
        'city_id',
        'online_status',
        'lat',
        'lng',
        'blood_group',
        'referal_code',
        'document_steps',
        'form_steps',
        'referal_id',
        'is_deleted',
        'info_delete',
        'created_at',
        'updated_at',
    ];
    echo DetailView::widget([
        'model' => $model->user,
        'attributes' => $gridColumnUser    ]);
    ?>
    </div>
    </div>
    <div class="card">
       <div class="card-body">
    <div class="row">
        <h4>RideRequest<?= ' '. Html::encode($this->title) ?></h4>
    </div>
    <?php 
    $gridColumnRideRequest = [
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
        'city_id',
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
        'payment_method',
        'payment_status',
        'status',
        'payment_token',
    ];
    echo DetailView::widget([
        'model' => $model->ride,
        'attributes' => $gridColumnRideRequest    ]);
    ?>
    </div>
    </div>
</div>

