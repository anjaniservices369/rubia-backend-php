<?php

namespace app\modules\comingsoon\controllers;

use yii\web\Controller;

/**
 * Default controller for the `commingsoon` module
 */
class DefaultController extends Controller {
	// [doc:start] actionIndex - /comingsoon/default/index - Renders the index view for the module.
	/**
	 * Renders the index view for the module
	 *
	 * @return string
	 */
	public function actionIndex() {
		if (COMMING_SOON) {
			return $this->render ( 'index' );
		}
		return $this->redirect ( [ 
				'/' 
		] );
	}
	// [doc:end] actionIndex
}
