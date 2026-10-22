<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\Page */

$this->title = Yii::t('app', 'Create Page');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Pages'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="page-create">

    <h1><?php Html::encode($this->title) ?></h1>
    <div class="card">
    <div class="card-body">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
</div>
</div>
