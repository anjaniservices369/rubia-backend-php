<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use kartik\grid\GridView;
use app\models\User;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\DriverDetails */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Taxi Driver Details'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="driver-details-view">
<div class="card">
       <div class="card-body">
    <div class="row">
        <div class="col-sm-9">
            <h2><?= Yii::t('app', 'Taxi Driver Details').' '. Html::encode($this->title) ?></h2>
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
            'attribute' => 'user.username',
            'label' => Yii::t('app', 'User'),
        ],
        'commission_percent',
        [
            'attribute' => 'city.name',
            'label' => Yii::t('app', 'City'),
        ],
        'address',
        'license_no',
        'proof_of_license',
        'proof_of_license_back',
        [
            'attribute' => 'vehical.title',
            'label' => Yii::t('app', 'Vehical'),
        ],
        'rc_number',
        'vehical_number',
        'rc_proof',
        'rc_proof_back',
        'adhaar_number',
        'adhaar_front',
        'adhaar_back',
        'pan_number',
        'pan_front',
        'pan_back',
        'is_verified',
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
        <h4>Vehicals<?= ' '. Html::encode($this->title) ?></h4>
    </div>
    <?php 
    $gridColumnVehicals = [
        ['attribute' => 'id', 'visible' => false],
        'title',
        'image',
        'status',
    ];
    echo DetailView::widget([
        'model' => $model->vehical,
        'attributes' => $gridColumnVehicals    ]);
    ?>
    </div>
    </div>
    
</div>

