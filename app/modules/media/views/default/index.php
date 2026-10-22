<?php
use yii\widgets\ListView;
use yii\base\Widget;

/* @var $this yii\web\View */
/* @var $searchModel app\models\DealSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t ( 'app', 'Deals' );
$this->params ['breadcrumbs'] [] = $this->title;
?>
<div class="deal-index" ng-controller="MediaController">
	<div class="panel">
		<div class="panel-body">
			<div class="row">
		<?php
		echo ListView::widget ( [ 
				'dataProvider' => $dataProvider,
				'layout' => '{summary}</br>{items}<div class="clearfix"></div>{pager}',
				'itemView' => '_file' 
		] );
		?>
		</div>
		</div>
	</div>
</div>

<script type="text/ng-template" id="mediaEditTmpl.html">
<md-dialog aria-label="Mango (Fruit)">
  <md-toolbar>
    <div class="md-toolbar-tools">
		<h3>Attachment Details</h3>
    </div>
  </md-toolbar>

  <md-dialog-content>
    <div class="md-dialog-content">
      	<div class="row">
			<div class="col-md-6">
				<div class="image_container"> 
					<img ng-src="{{imageUrl}}">
				</div>
			</div>
			<div class="col-md-6">
				<div class="file__Info">
					<div class="file_Detail">
						<div class="filename">
							<strong>File name</strong> : {{model.original_name}}.{{model.extension}}
						</div>
						<div class="filetype">
							<strong>File Type</strong> : {{model.extension}}
						</div>
						<div class="uploadedon">
							<strong>Uploaded on</strong> : {{model.size}}
						</div>
						<div class="filesize">
							<strong>File size</strong> : {{model.size}}
						</div>
						<div class="filesize">
							<strong>Uploaded By</strong> : {{uploadedBy}}
						</div>
					</div>
					<div class="devider"></div>
					<div class="file__Settings">
						<label class="setting" data-setting="url">
							<span class="name">URL</span>
							<input type="text" class="setting-input" ng-model="imageUrl" readonly="">
						</label>
				
						<label class="setting" data-setting="title">
							<span class="name">Title</span>
							<input type="text" class="setting-input" ng-model="model.title">
						</label>
				
						<label class="setting" data-setting="alt">
							<span class="name">Alt Text</span>
							<input type="text" class="setting-input" ng-model="model.alt">
						</label>
				
						<div class="attachment-compat text-right">
							<a class="btn btn-sm btn-primary" ng-click="saveSettings(model.title, model.alt)"> Save </a>
						</div>
					</div>
				</div>
			</div>
		</div>
    </div>
  </md-dialog-content>
</md-dialog>
</script>