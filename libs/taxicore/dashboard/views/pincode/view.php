<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use kartik\grid\GridView;
use app\models\User;
use app\modules\admin\models\Pincode;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\Pincode */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Pincodes'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="pincode-view">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-sm-9">
                    <h2><?= Yii::t('app', 'Pincode') . ' ' . Html::encode($this->title) ?></h2>
                </div>

            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">

            <div class="row">
                <?php
                $gridColumn = [
                    ['attribute' => 'id', 'visible' => false],
                    [
                        'attribute' => 'city.name',
                        'label' => Yii::t('app', 'City'),
                    ],
                    'name',
                    [
                        'attribute' => 'vehical.title',
                        'label' => Yii::t('app', 'Vehical'),
                    ],
                    'status',
                ];
                echo DetailView::widget([
                    'model' => $model,
                    'attributes' => $gridColumn
                ]);
                ?>
            </div>
        </div>
    </div>
    <?php $city = \app\modules\admin\models\base\City::find()->where(['id' => $model->city_id])->one();
    if(!empty($city)){
    ?>
    <div class="card">
        <div class="card-body">
            <div class="row">
                <h4>City<?= ' ' . Html::encode($this->title) ?></h4>
            </div>
            <?php
            $gridColumnCity = [
                ['attribute' => 'id', 'visible' => false],
                'name',
                'status',
            ];
            echo DetailView::widget([
                'model' => $city,
                'attributes' => $gridColumnCity
            ]);
            ?>
        </div>
    </div>
    <?php }?>
    <div class="card">
        <div class="card-body">
            <div class="row">
                <h4>Vehicles <?= ' ' . Html::encode($this->title) ?></h4>
            </div>
            <?php
            $gridColumnVehicals = [
                ['attribute' => 'id', 'visible' => false],
                'title',
                'image',
                'status',
            ];
            echo DetailView::widget([
                'model' => $model->vehical,
                'attributes' => $gridColumnVehicals
            ]);
            ?>
        </div>
    </div>
</div>