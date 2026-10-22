<?php

use app\models\User;
use app\modules\admin\widgets\LinkedColumn;
use yii\helpers\Html;
use app\modules\admin\widgets\BoxGridView;
use app\modules\admin\Module as AdminModule;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\UserSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title                   = 'Users';
$this->params['breadcrumbs'][] = $this->title;
?>
<?php $this->beginBlock('content-title'); ?>
	<?= Html::a('Add New', ['create'], ['class' => 'btn btn-sm btn-success']) ?>
<?php $this->endBlock(); ?>

<div class="user-index">
<div class="card">
    <div class="card-body">

	<?= GridView::widget([
		'dataProvider' => $dataProvider,
		'filterModel'  => $searchModel,
		'columns'      => [
			['class' => 'yii\grid\SerialColumn'],
			/*[
				'class' => 'kartik\grid\ExpandRowColumn',
				'width' => '50px',
				'value' => function ($model, $key, $index, $column) {
					return GridView::ROW_COLLAPSED;
				},
				'detail' => function ($model, $key, $index, $column) {
					return Yii::$app->controller->renderPartial('_subexpand', ['model' => $model]);
				},
				'headerOptions' => ['class' => 'kartik-sheet-style'],
				'expandOneOnly' => true
			],*/
			// [
			// 	'class' => LinkedColumn::class,
			// 	'header' => '<a href="#">Name</a>',
			// 	'attribute' => 'full_name',
			// 	'value' => 'fullName',
			// ],
			
			'id',
			/*[
				'class' => LinkedColumn::class,
				'header' => '<a href="#">id</a>',
				'attribute' => 'username',
				'value' => 'id',
			],*/
			[
				'class' => LinkedColumn::class,
				'header' => '<a href="#">Name</a>',
				'attribute' => 'first_name',
				'value' => 'first_name',
			],
			//'username',
			[
				'class' => LinkedColumn::class,
				'header' => '<a href="#">Email</a>',
				'attribute' => 'username',
				'value' => 'email',
			],
			'contact_no',
			array(
				'attribute' => 'status',
				'value' => 'statusAlias',
				'filter' => Html::activeDropDownList(
					$searchModel,
					'status',
					User::getStatusesList(),
					['prompt' => 'All', 'class' => 'form-control']
				),
			),
			'created_at:date:Registered',
			// 'updated_at',
			[
				'class'=>'kartik\grid\ActionColumn',
				'template'=> '{view} {update} ',
	
				'buttons'=> [
					'update'=> function($url,$model) {
						return Html::a( '<i class="fas fa-pencil-alt"></i>', $url);
		
					},
					'view'=> function($url,$model) {
						return Html::a( '<i class="fa fa-eye" aria-hidden="true"></i>', $url);
		
					},
					'delete'=> function($url,$model) {
						return Html::a( '<i class="fas fa-trash-alt"></i>', $url);
		
					},
	
				   
				],
	
			],
		],
	]); ?>

</div>
</div>
</div>