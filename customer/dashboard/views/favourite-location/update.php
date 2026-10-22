<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\FavouriteLocation */

$this->title = 'Update Favourite Location: ' . ' ' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Favourite Locations', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="favourite-location-update">
<div class="card">
       <div class="card-body">
    <!-- <h1><?= Html::encode($this->title) ?></h1> -->

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
</div>
</div>
