<?php
use yii\helpers\Html;
use yii\widgets\DetailView;
use app\components\BasePageHeader;
use app\models\SubCategory;

/* @var $this yii\web\View */
/* @var $model app\models\Category */

$this->title = $model->title;
$this->params['breadcrumbs'][] = [
    'label' => Yii::t('app', 'Categories'),
    'url' => [
        'index'
    ]
];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="category-view">

	<h1><?=BasePageHeader::widget() ?></h1>
	<div class="panel">
		<div class="panel-body">



			<div class="row">
				<div class="col-md-3">

  <?=$model->getImageFile($model,$default = 'default.jpg', $options = [],$attribute='file_name');?>
  
  <br>
  <?=$model->getImageFile($model,$default = 'default.jpg', $options = [],$attribute='thumb_file');?>

 </div>
				<div class="col-md-9">
   
     <?php echo DetailView::widget(['model' => $model,'attributes' => ['id','title','description:ntext','created_on','create_user_id']])?>
   </div>


			</div>


		</div>

	</div>
</div>