<?php

namespace app\modules\media;

/**
 * media module definition class
 */
class Module extends \yii\base\Module {
	/**
	 * @inheritdoc
	 */
	public $controllerNamespace = 'app\modules\media\controllers';
	
	/**
	 * @inheritdoc
	 */
	public function init() {
		$this->layout = '@app/views/layouts/main';
		parent::init ();
		// custom initialization code goes here
	}
}
