<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\VehicleTypes */

$this->title = Yii::t('app', 'Create Vehicle Types');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Vehicle Types'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="vehicle-types-create">
    <div class="card">
       <div class="card-body">
    <!-- <h1><?= Html::encode($this->title) ?></h1> -->

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
    </div>
    </div>
</div>
