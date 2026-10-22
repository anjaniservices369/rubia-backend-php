<?php
use app\components\BaseGridView;
use app\components\BasePageHeader;
use yii\base\Widget;
use yii\widgets\Pjax;
/* @var $this yii\web\View */
/* @var $searchModel app\models\CategorySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t ( 'app', 'Categories' );
$this->params ['breadcrumbs'] [] = $this->title;
?>
<div class="category-index">

	<?= BasePageHeader::widget();?>
	<div class="panel">
		<div class="panel-body">
			<?php Pjax::begin(['id' => 'pjax-grid-view']); ?>
	    <?php
					
					echo BaseGridView::widget ( [ 
							'dataProvider' => $dataProvider,
							'filterModel' => $searchModel,
							'columns' => [ 
									[ 
											'class' => 'yii\grid\CheckboxColumn' 
									],
									'id',
									'title',
									[ 
											'class' => 'app\components\BaseActionColumn' 
									] 
							] 
					] );
					?>
	    <?php Pjax::end(); ?>
	    </div>
	</div>

</div>
