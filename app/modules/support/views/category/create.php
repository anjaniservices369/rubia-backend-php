<?php
use yii\helpers\Html;
use app\components\BasePageHeader;
use yii\base\Widget;

/* @var $this yii\web\View */
/* @var $model app\models\Category */

$this->title = Yii::t ( 'app', 'Create Category' );
$this->params ['breadcrumbs'] [] = [ 
		'label' => Yii::t ( 'app', 'Categories' ),
		'url' => [ 
				'index' 
		] 
];
$this->params ['breadcrumbs'] [] = $this->title;
?>
<div class="category-create">


	 <p><?=BasePageHeader::widget();?>
	<div class="panel">
		<div class="panel-body">



    <?=$this->render ( '_form', [ 'model' => $model,'media' => $media ] )?>
</div>
	</div>
</div>
