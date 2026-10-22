<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use kartik\grid\GridView;
use app\models\User;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\RideCommision */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Ride Commisions'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ride-commision-view">
<div class="card">
       <div class="card-body">
    <div class="row">
        <div class="col-sm-9">
            <h2><?= Yii::t('app', 'Rate Card').' '. Html::encode($this->title) ?></h2>
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
        [
            'attribute' => 'vehicle.title',
            'label' => Yii::t('app', 'Vehicle'),
        ],
        'base_fare',
        'min_distance',
        'fare_per_distance',
        'waiting_time_limit',
        'charges_per_minute',
        'fare_per_minute',
        'no_of_person',
        'commision',
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
        <h4>City<?= ' '. Html::encode($this->title) ?></h4>
    </div>
    <?php 
    $gridColumnCity = [
        ['attribute' => 'id', 'visible' => false],
        'name',
        'status',
    ];
    echo DetailView::widget([
        'model' => $model->city,
        'attributes' => $gridColumnCity    ]);
    ?>
    </div>
    </div>
    <div class="card">
       <div class="card-body">
    <div class="row">
        <h4>Vehicles <?= ' '. Html::encode($this->title) ?></h4>
    </div>
    <?php 
    $gridColumnVehicals = [
        ['attribute' => 'id', 'visible' => false],
        'title',
        'image',
        'base_fare',
        'min_distance',
        'fare_per_distance',
        'waiting_time_limit',
        'charges_per_minute',
        'fare_per_minute',
        'cancellation_fee',
        'admin_commison_type',
        'admin_commison',
        'no_of_person',
        'status',
    ];
    echo DetailView::widget([
        'model' => $model->vehicle,
        'attributes' => $gridColumnVehicals    ]);
    ?>
    </div>
    </div>
</div>

