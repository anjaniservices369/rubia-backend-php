<?php
use yii\helpers\Html;
?>
<div class="col-md-4">
	<div class="thumbnail">
		<a href="javascript:;" ng-click="openModel($event, <?= $model->id ?>)"> 
			<?php
			echo Html::img ( [ 
					'/uploads/thumbnail/' . $model->thumb_file 
			], [ 
					'alt' => $model->alt,
					'width' => "150",
					'height' => "150" 
			] );
			?>
			<div class="caption">
				<p class="mediaTitle__<?= $model->id ?>"><?= $model->title?></p>
			</div>
		</a>
	</div>
</div>