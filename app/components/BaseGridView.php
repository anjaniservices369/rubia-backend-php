<?php

namespace app\components;

use yii\grid\GridView;
use yii\web\View;
use yii\helpers\Html;
use yii\helpers\Url;

class BaseGridView extends GridView {
	public $rowClick = true;
	public $bulkDelete = true;
	public function run() {
		$id = $this->options ['id'];
		$view = $this->getView ();
		
		$view->registerJs ( "jQuery('#$id table tbody tr').click(function(e) {
			var getUrl = jQuery(this).attr('data-url');
			if (e.target.type == 'checkbox' || $(e.target).attr('class') == 'rowClick' ) {
		        // stop the bubbling to prevent firing the row's click event
		        e.stopPropagation();
		    } else {
				if( getUrl != undefined ) {
		        	window.location.href = getUrl;
				}
		    }
		});
		", View::POS_LOAD );
		
		parent::run ();
	}
	public function initBulkdelete() {
	}
	public function renderTableRow($model, $key, $index) {
		$cells = [ ];
		/* @var $column Column */
		foreach ( $this->columns as $column ) {
			$cells [] = $column->renderDataCell ( $model, $key, $index );
		}
		if ($this->rowOptions instanceof Closure) {
			$options = call_user_func ( $this->rowOptions, $model, $key, $index, $this );
		} else {
			$options = $this->rowOptions;
		}
		$options ['data-key'] = is_array ( $key ) ? json_encode ( $key ) : ( string ) $key;
		if ($this->rowClick == true) {
			$options ['style'] = 'cursor:pointer';
			$options ['data-url'] = Url::home () . '' . \yii::$app->controller->id . '/view?id=' . $options ['data-key'];
		}
		
		return Html::tag ( 'tr', implode ( '', $cells ), $options );
	}
}