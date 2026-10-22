<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\DriverAadharCard */

$this->title = Yii::t('app', 'Create Driver Aadhar Card');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Driver Aadhar Cards'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="driver-aadhar-card-create">
    <div class="card">
       <div class="card-body">
    <!-- <h1><?= Html::encode($this->title) ?></h1> -->

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
    </div>
    </div>
</div>
