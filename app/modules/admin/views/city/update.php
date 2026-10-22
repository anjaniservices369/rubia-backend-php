<?php

use kartik\helpers\Html;

use yii\web\View;

/* @var $this yii\web\View */
/* @var $model app\models\City */

$this->title = 'Update City: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Cities', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';

// // Register the Google Maps JavaScript code
// $this->registerJsFile('https://maps.googleapis.com/maps/api/js?key=YOUR_KEY&libraries=drawing', ['position' => View::POS_HEAD]);
// $this->registerJs($this->render('_map-script', ['model' => $model]), View::POS_END);
?>

<div class="city-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'boundaryPoints' => $model->boundary,
    ]) ?>

    <div id="map" style="height: 500px; width: 100%;"></div>

</div>
