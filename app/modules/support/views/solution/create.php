<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\modules\support\models\Solution */

$this->title = Yii::t('app', 'Create Solution');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Solutions'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="solution-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
