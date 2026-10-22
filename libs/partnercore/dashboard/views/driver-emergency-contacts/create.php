<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\DriverEmergencyContacts */

$this->title = Yii::t('app', 'Create Driver Emergency Contacts');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Driver Emergency Contacts'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="driver-emergency-contacts-create">
    <div class="card">
       <div class="card-body">
    <!-- <h1><?= Html::encode($this->title) ?></h1> -->

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
    </div>
    </div>
</div>
