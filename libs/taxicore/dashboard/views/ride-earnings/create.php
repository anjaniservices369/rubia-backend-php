<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\RideEarnings */

$this->title = Yii::t('app', 'Create Ride Earnings');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Ride Earnings'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ride-earnings-create">
    <div class="card">
       <div class="card-body">
    <!-- <h1><?= Html::encode($this->title) ?></h1> -->

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
    </div>
    </div>
</div>
