<?php
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\User */
/* @var $driverAadharCardModel app\modules\admin\models\DriverAadharCard */

$this->title = Yii::t('app', 'Driver Aadhar Card') . ' ' . Html::encode($model->username);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['user-adhar-update', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'View Aadhar Card');
?>

<div class="user-adhar-view">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-sm-9">
                    <h2><?= $this->title ?></h2>
                </div>
                <div class="col-sm-3" style="margin-top: 15px">
                    <?= Html::a(Yii::t('app', 'Update Aadhar Card'), ['user-aadhar-update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
                </div>
            </div>
            <div class="row">
                <?php
                $gridColumn = [
                    'aadhar_number',
                    'aadhar_card_front',
                    'aadhar_card_back',
                    // Add other Aadhar Card fields here if needed
                ];
                echo DetailView::widget([
                    'model' => $driverAadharCardModel,
                    'attributes' => $gridColumn
                ]);
                ?>
            </div>
        </div>
    </div>
</div>
