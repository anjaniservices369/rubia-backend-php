<?php
use yii\helpers\Html;
use app\components\BasePageHeader;
use yii\base\Widget;

/* @var $this yii\web\View */
/* @var $model app\models\Category */

$this->title = Yii::t ( 'app', 'Update Category: {nameAttribute}', [ 
		'nameAttribute' => $model->title 
] );
$this->params ['breadcrumbs'] [] = [ 
		'label' => Yii::t ( 'app', 'Categories' ),
		'url' => [ 
				'index' 
		] 
];
$this->params ['breadcrumbs'] [] = [ 
		'label' => $model->title,
		'url' => [ 
				'view',
				'id' => $model->id 
		] 
];
$this->params ['breadcrumbs'] [] = Yii::t ( 'app', 'Update' );
?>
<div class="category-update">
	<div class="panel">
		<div class="panel-body">

    <p1><?= BasePageHeader::widget() ?></p1>

    <?= $this->render('_form', [
        'model' => $model,
        'media'=>$media
    ]) ?>

</div>
