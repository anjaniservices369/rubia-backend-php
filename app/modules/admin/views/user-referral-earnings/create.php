<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\UserReferralEarnings */

$this->title = Yii::t('app', 'Create User Referral Earnings');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'User Referral Earnings'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-referral-earnings-create">
    <div class="card">
       <div class="card-body">
    <!-- <h1><?= Html::encode($this->title) ?></h1> -->

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
    </div>
    </div>
</div>
