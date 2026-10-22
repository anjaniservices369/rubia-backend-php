<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\UserWallet */

$this->title = Yii::t('app', 'Create User Wallet');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'User Wallets'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-wallet-create">
    <div class="card">
       <div class="card-body">
    <!-- <h1><?= Html::encode($this->title) ?></h1> -->

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
    </div>
    </div>
</div>
