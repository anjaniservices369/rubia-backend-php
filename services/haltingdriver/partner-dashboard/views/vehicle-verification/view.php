<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use kartik\grid\GridView;
use app\models\User;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\VehicleVerification */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Vehicle Verifications'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="vehicle-verification-view">
<div class="card">
       <div class="card-body">
    <div class="row">
        <div class="col-sm-9">
            <h2><?= Yii::t('app', 'Vehicle Verification').' '. Html::encode($this->title) ?></h2>
        </div>
        <div class="col-sm-3" style="margin-top: 15px">
            
            <?= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
                          <?php  if(\Yii::$app->user->identity->user_role==User::ROLE_ADMIN || \Yii::$app->user->identity->user_role==User::ROLE_SUBADMIN){ ?>
             <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                    'method' => 'post',
                ],
            ])
            ?>  
             <?php  } ?>
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
            'attribute' => 'booking.id',
            'label' => Yii::t('app', 'Booking'),
        ],
        'image',
        'image_type',
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
        <h4>Booking<?= ' '. Html::encode($this->title) ?></h4>
    </div>
    <?php 
    $gridColumnBooking = [
        ['attribute' => 'id', 'visible' => false],
        'user_id',
        'rider_type',
        'trip_type',
        'pickup_latitude',
        'pickup_longitude',
        'drop_latitude',
        'drop_longitude',
        'car_type',
        'car_model',
        'pickup_date',
        'pickup_time',
        'estimate_price',
        'usage_hours',
        'coupon_code',
        'payment_method',
        'status',
    ];
    echo DetailView::widget([
        'model' => $model->booking,
        'attributes' => $gridColumnBooking    ]);
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
        'lat',
        'lng',
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
        'online_status',
        'account_type',
        'referal_code',
        'referal_id',
        'signup_type',
        'is_paid_joining_fee',
        'paid_amount',
        'is_done_police_verification',
        'business_name',
        'gst_number',
        'is_deleted',
        'profile_verified',
        'info_delete',
        'created_at',
        'updated_at',
    ];
    echo DetailView::widget([
        'model' => $model->createUser,
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
        'lat',
        'lng',
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
        'online_status',
        'account_type',
        'referal_code',
        'referal_id',
        'signup_type',
        'is_paid_joining_fee',
        'paid_amount',
        'is_done_police_verification',
        'business_name',
        'gst_number',
        'is_deleted',
        'profile_verified',
        'info_delete',
        'created_at',
        'updated_at',
    ];
    echo DetailView::widget([
        'model' => $model->updateUser,
        'attributes' => $gridColumnUser    ]);
    ?>
    </div>
    </div>
</div>

