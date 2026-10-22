<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\BannerCategorys */

$this->title = Yii::t('app', 'Create Banner Categorys');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Banner Categorys'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="banner-categorys-create">
    <div class="card">
       <div class="card-body">
    <!-- <h1><?= Html::encode($this->title) ?></h1> -->

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
    </div>
    </div>
</div>
