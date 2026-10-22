<?php

namespace app\modules\admin;

use Yii;

class Module extends \yii\base\Module
{
	/**
	 * @inheritdoc
	 */
	public $layout = 'main';

	/**
	 * @inheritdoc
	 */
	public $defaultRoute = 'dashboard';

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		// configure module.
		Yii::configure($this, require(__DIR__ . '/config/main.php'));

		// reconfigure service container.
		Yii::configure(Yii::$container, require(__DIR__ . '/config/container.php'));

		// set bootstrap version to 5 for Kartik widgets.
		Yii::$app->params['bsVersion'] = 5;
		
		// the site config pins jQuery 1.11.2 from a CDN; the admin uses the framework's bundled jQuery 3.
		// load it in <head>: the admin views call $() in inline scripts that run before the end of <body>.
		Yii::$app->assetManager->bundles['yii\web\JqueryAsset'] = ['jsOptions' => ['position' => \yii\web\View::POS_HEAD]];

		// change error action to match admin styles.
		Yii::$app->errorHandler->errorAction = 'admin/dashboard/error';
	}
}
