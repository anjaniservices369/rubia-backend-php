<?php

// comment out the following two lines when deployed to production
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

// vendor/ sits next to config/ (this repository) or one level above (installed layout)
$vendor = is_file(__DIR__ . '/../vendor/autoload.php') ? __DIR__ . '/../vendor' : __DIR__ . '/../../vendor';
require $vendor . '/autoload.php';
require $vendor . '/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';


(new yii\web\Application($config))->run();
