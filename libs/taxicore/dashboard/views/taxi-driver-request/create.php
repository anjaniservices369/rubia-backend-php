<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\TaxiDriverRequest */

$this->title = Yii::t('app', 'Create Taxi Driver Request');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Taxi Driver Requests'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="taxi-driver-request-create">
    <div class="card">
       <div class="card-body">
    <!-- <h1><?= Html::encode($this->title) ?></h1> -->

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
    </div>
    </div>
</div>
