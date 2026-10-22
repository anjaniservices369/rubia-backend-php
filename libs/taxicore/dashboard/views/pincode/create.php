<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\Pincode */

$this->title = Yii::t('app', 'Create Pincode');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Pincodes'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="pincode-create">
    <div class="card">
       <div class="card-body">
    <!-- <h1><?= Html::encode($this->title) ?></h1> -->

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
    </div>
    </div>
</div>
