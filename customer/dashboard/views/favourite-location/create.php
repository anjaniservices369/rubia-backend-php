<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\FavouriteLocation */

$this->title = 'Create Favourite Location';
$this->params['breadcrumbs'][] = ['label' => 'Favourite Locations', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="favourite-location-create">
    <div class="card">
       <div class="card-body">
    <!-- <h1><?= Html::encode($this->title) ?></h1> -->

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
    </div>
    </div>
</div>
