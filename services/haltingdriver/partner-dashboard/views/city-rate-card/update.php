<?php

use app\modules\admin\models\City;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\CityRateCard */
$city_id = Yii::$app->request->get('city-id');
$trip_type = Yii::$app->request->get('trip_type');
$ride_type = Yii::$app->request->get('ride_type');
$city = City::find()
    ->where(['id' => $city_id])
    ->one();


$cityName = isset($city->name) ? $city->name : '';

$this->title =
    Yii::t('app', 'Update {modelClass}: ', [
        'modelClass' => 'City Rate Card ' . $cityName,
    ]) .
    ' ' .
    $model->id;
$this->params['breadcrumbs'][] = [
    'label' => Yii::t('app', 'City Rate Cards'),
    'url' => ['index'],
];
$this->params['breadcrumbs'][] = [
    'label' => $model->id,
    'url' => ['view', 'id' => $model->id],
];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="city-rate-card-update">
<div class="card">
       <div class="card-body">
    <!-- <h1><?= Html::encode($this->title) ?></h1> -->

    <?= $this->render('_form', [
        'model' => $model,
        'city_id' => $city_id,
        'trip_type' => $trip_type,
        'ride_type' => $ride_type,
    ]) ?>
</div>
</div>
</div>
