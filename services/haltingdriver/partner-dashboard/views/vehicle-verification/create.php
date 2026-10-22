<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\VehicleVerification */

$this->title = Yii::t('app', 'Create Vehicle Verification');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Vehicle Verifications'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="vehicle-verification-create">
    <div class="card">
       <div class="card-body">
    <!-- <h1><?= Html::encode($this->title) ?></h1> -->

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
    </div>
    </div>
</div>
