<?php

// Production by default. For development set YII_ENV=dev and YII_DEBUG=1 in the environment.
defined ( 'YII_DEBUG' ) or define ( 'YII_DEBUG', getenv ( 'YII_DEBUG' ) === '1' );
defined ( 'YII_ENV' ) or define ( 'YII_ENV', getenv ( 'YII_ENV' ) ?: 'prod' );	

defined ( 'COMMING_SOON' ) or define ( 'COMMING_SOON', false );

defined ( 'STATUS_SUCCESS' ) or define ( 'STATUS_SUCCESS', '1' );
defined ( 'STATUS_FAILURE' ) or define ( 'STATUS_FAILURE', '0' );

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
define ( 'BASE_PATH', __DIR__ . '/' );

//$config = require __DIR__ . '/config/web.php';
$config = \yii\helpers\ArrayHelper::merge(
	require(__DIR__ . '/config/web.php'),
	require(__DIR__ . '/config/rbac.php')
);
define ( 'UPLOAD_PATH', __DIR__ . '/uploads' );
if (! file_exists ( UPLOAD_PATH )) {
	mkdir ( UPLOAD_PATH, 0777, true );
}



(new yii\web\Application ( $config ))->run ();
