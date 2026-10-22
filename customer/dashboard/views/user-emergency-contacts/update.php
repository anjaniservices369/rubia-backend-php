<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\UserEmergencyContacts */

$this->title = Yii::t('app', 'Update {modelClass}: ', [
    'modelClass' => 'User Emergency Contacts',
]) . ' ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'User Emergency Contacts'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="user-emergency-contacts-update">
<div class="card">
       <div class="card-body">
    <!-- <h1><?= Html::encode($this->title) ?></h1> -->

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
</div>
</div>
