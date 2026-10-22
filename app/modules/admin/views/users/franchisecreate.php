<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model \app\modules\admin\forms\UserForm */

$this->title  = 'Create Franchise';
$this->params['breadcrumbs'][] = ['label' => 'Franchise', 'url' => ['franchise']];
$this->params['breadcrumbs'][] = $this->title;
$this->params['heading']       = 'Franchises';
$this->params['subheading']    = 'Add New';
?>
<div class="user-create">

	<?= $this->render('_formfranchise', [
		'model' => $model,
	]) ?>

</div>
