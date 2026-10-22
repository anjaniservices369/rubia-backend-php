<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use kartik\grid\GridView;
use app\models\User;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\CityRateCard */

$this->title = $model->id;
$this->params['breadcrumbs'][] = [
    'label' => Yii::t('app', 'City Rate Cards'),
    'url' => ['index'],
];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="city-rate-card-view">
<div class="card">
       <div class="card-body">
    <div class="row">
        <div class="col-sm-9">
            <h2><?= Yii::t('app', 'City Rate Card') .
                ' ' .
                Html::encode($this->title) ?></h2>
        </div>
        <div class="col-sm-3" style="margin-top: 15px">
            
                          <?php if (
                              \Yii::$app->user->identity->user_role ==
                                  User::ROLE_ADMIN ||
                              \Yii::$app->user->identity->user_role ==
                                  User::ROLE_SUBADMIN
                          ) { ?>
             <?= Html::a(
                 Yii::t('app', 'Delete'),
                 ['delete', 'id' => $model->id],
                 [
                     'class' => 'btn btn-danger',
                     'data' => [
                         'confirm' => Yii::t(
                             'app',
                             'Are you sure you want to delete this item?'
                         ),
                         'method' => 'post',
                     ],
                 ]
             ) ?>  
             <?php } ?>
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
        'attribute' => 'city.name',
        'label' => Yii::t('app', 'City'),
    ],
    'trip_type',
    'ride_type',
    'base_hour',
    'base_km',
    'base_fare',
    'extra_km_charge',
    'extra_min_charge',
    'night_charge',
    'out_station_charge',
    'facilities',
    'status',
];
echo DetailView::widget([
    'model' => $model,
    'attributes' => $gridColumn,
]);
?>
</div>
</div>
    </div>
    <div class="card">
       <div class="card-body">
    <div class="row">
        <h4>City<?= ' ' . Html::encode($this->title) ?></h4>
    </div>
    <?php
    $gridColumnCity = [
        ['attribute' => 'id', 'visible' => false],
        'name',
        'boundary',
        'status',
    ];
    echo DetailView::widget([
        'model' => $model->city,
        'attributes' => $gridColumnCity,
    ]);
    ?>
    </div>
    </div>
    <div class="card">
       <div class="card-body">
    <div class="row">
        <h4>User<?= ' ' . Html::encode($this->title) ?></h4>
    </div>
    <?php
    $gridColumnUser = [['attribute' => 'id', 'visible' => false], 'username'];
    echo DetailView::widget([
        'model' => $model->createUser,
        'attributes' => $gridColumnUser,
    ]);
    ?>
    </div>
    </div>
    <div class="card">
       <div class="card-body">
    <div class="row">
        <h4>User<?= ' ' . Html::encode($this->title) ?></h4>
    </div>
    <?php
    $gridColumnUser = [['attribute' => 'id', 'visible' => false], 'username'];
    echo DetailView::widget([
        'model' => $model->updateUser,
        'attributes' => $gridColumnUser,
    ]);
    ?>
    </div>
    </div>
</div>

