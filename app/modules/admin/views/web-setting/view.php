<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\WebSetting */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Web Settings'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="web-setting-view">
<div class="card">
    <div class="card-body">
    <div class="row">
        <div class="col-sm-9">
            <h2><?= Yii::t('app', 'Web Setting').' '. Html::encode($this->title) ?></h2>
        </div>
        <div class="col-sm-3" style="margin-top: 15px">
<?=             
             Html::a('<i class="fa fa fa-hand-up"></i> ' . Yii::t('app', 'PDF'), 
                ['pdf', 'id' => $model->setting_id],
                [
                    'class' => 'btn btn-danger',
                    'target' => '_blank',
                    'data-bs-toggle' => 'tooltip',
                    'title' => Yii::t('app', 'Will open the generated PDF file in a new window')
                ]
            )?>
            
            <?= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->setting_id], ['class' => 'btn btn-primary']) ?>
            <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->setting_id], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                    'method' => 'post',
                ],
            ])
            ?>
        </div>
    </div>
    </div>
    </div>
    <div class="card">
    <div class="card-body">
    <div class="row">
<?php 
    $gridColumn = [
        'setting_id',
        'name',
        'setting_key',
        'value:ntext',
        'type_id',
        'status',
        ['attribute' => 'created_date', 'visible' => false],
        ['attribute' => 'updated_date', 'visible' => false],
        ['attribute' => 'create_user_id', 'visible' => false],
        ['attribute' => 'updated_user_id', 'visible' => false],
    ];
    echo DetailView::widget([
        'model' => $model,
        'attributes' => $gridColumn
    ]);
?>
    </div>
    </div>
    </div>
    <div class="card">
    <div class="card-body">
    <div class="row">
        <h4>User<?= ' '. Html::encode($this->title) ?></h4>
    </div>
    <?php 
    $gridColumnUser = [
        ['attribute' => 'id', 'visible' => false],
        'username',
        'email',
        'first_name',
        'last_name',
        'contact_no',
       
    ];
    echo DetailView::widget([
        'model' => $model->createUser,
        'attributes' => $gridColumnUser    ]);
    ?>
    </div>
    </div>
    <div class="card">
    <div class="card-body">
    <div class="row">
        <h4>User<?= ' '. Html::encode($this->title) ?></h4>
    </div>
    <?php 
    $gridColumnUser = [
        ['attribute' => 'id', 'visible' => false],
        'username',
        'email',
        'first_name',
        'last_name',
        'contact_no',
    ];
    echo DetailView::widget([
        'model' => $model->updatedUser,
        'attributes' => $gridColumnUser    ]);
    ?>
    </div>
    </div>
</div>
