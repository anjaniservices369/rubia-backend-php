<?php
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\User */
/* @var $driverPanCardsModel app\modules\admin\models\DriverPanCards */

$this->title = Yii::t('app', 'Driver Pan Card') . ' ' . Html::encode($model->username);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['user-pan-update', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'View Pan Card');
?>

<div class="user-pan-view">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-sm-9">
                    <h2><?= $this->title ?></h2>
                </div>
                <div class="col-sm-3" style="margin-top: 15px">
                    <?= Html::a(Yii::t('app', 'Update Pan Card'), ['user-pan-update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
                </div>
            </div>
            <div class="row">
                <?php
                $gridColumn = [
                    'pan_number',
                    'pan_card',
                    // Add other Pan Card fields here if needed
                ];
                echo DetailView::widget([
                    'model' => $driverPanCardsModel,
                    'attributes' => $gridColumn
                ]);
                ?>
            </div>
        </div>
    </div>
</div>
