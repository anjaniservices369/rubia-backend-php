<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\DriverSubscription */

$this->title = Yii::t('app', 'Create Driver Subscription');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Driver Subscriptions'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="driver-subscription-create">
    <div class="card">
       <div class="card-body">
    <!-- <h1><?= Html::encode($this->title) ?></h1> -->

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
    </div>
    </div>
</div>
