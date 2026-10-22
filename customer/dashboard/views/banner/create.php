<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\Banner */

$this->title = Yii::t('app', 'Create Banner');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Banners'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="banner-create">
    <div class="card">
       <div class="card-body">
    <!-- <h1><?= Html::encode($this->title) ?></h1> -->

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
    </div>
    </div>
</div>
