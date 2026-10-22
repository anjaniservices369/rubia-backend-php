<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\TaxiRating */

$this->title = Yii::t('app', 'Create Taxi Rating');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Taxi Ratings'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="taxi-rating-create">
    <div class="card">
       <div class="card-body">
    <!-- <h1><?= Html::encode($this->title) ?></h1> -->

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
    </div>
    </div>
</div>
