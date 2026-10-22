<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\WebSetting */

$this->title = Yii::t('app', 'Create Web Setting');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Web Settings'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="web-setting-create">

    <!-- <h1><?= Html::encode($this->title) ?></h1> -->
    <div class="card">
    <div class="card-body">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
    </div>
    </div>
</div>
