<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\User */
/* @var $bankDetailsModel app\modules\admin\models\base\DriverBankDetails */

$this->title = Yii::t('app', 'Driver Bank Details') . ' ' . Html::encode($model->username);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['user-bank-update', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'View Bank Details');

?>

<div class="user-bank-view">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-sm-9">
                    <h2><?= $this->title ?></h2>
                </div>
                <div class="col-sm-3" style="margin-top: 15px">
                    <?= Html::a(Yii::t('app', 'Update Bank Details'), ['user-bank-update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
                </div>
            </div>
            <div class="row">
            <?php
                $gridColumn = [
                    'account_holder_name',
                    'account_number',
                    'ifsc',
                    'bank_name',
                    'upi',
                    'branch_name',
                ];
                echo DetailView::widget([
                    'model' => $bankDetailsModel,
                    'attributes' => $gridColumn
                ]);
                ?>

            </div>
        </div>
    </div>
</div>
