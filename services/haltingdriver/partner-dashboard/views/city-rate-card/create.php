<?php

use app\modules\admin\models\City;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\CityRateCard */
$city_id = Yii::$app->request->get('city_id');
$trip_type = Yii::$app->request->get('trip_type');
$ride_type = Yii::$app->request->get('ride_type');

$city = City::find()
    ->where(['id' => $city_id])
    ->one();

$this->title = Yii::t('app', 'Create City Rate Card ' . $city->name??'');
$this->params['breadcrumbs'][] = [
    'label' => Yii::t('app', 'City Rate Cards'),
    'url' => ['index'],
];
$this->params['breadcrumbs'][] = $this->title;
$model->trip_type = $trip_type;
$model->ride_type = $ride_type;
?>
<div class="city-rate-card-create">
    <div class="card">
       <div class="card-body">

    <h1>Add data for <?= $model->getTripTypeBadges() ?> - <?= $model->getRideTypeBadges() ?></h1> 

    <?= $this->render('_form', [
        'model' => $model,
        'city_id' => $city_id,
        'trip_type' => $trip_type,
        'ride_type' => $ride_type,
    ]) ?>
    </div>
    </div>
</div>
