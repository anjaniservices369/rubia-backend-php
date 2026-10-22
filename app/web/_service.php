<?php

/**
 * Shared bootstrap of the per-service entry points (web/driver.php, taxi.php, auto.php, bike.php).
 *
 * @var string $service set by the entry point
 */
if (!isset($service)) {
    http_response_code(404);
    exit;
}

defined('YII_DEBUG') or define('YII_DEBUG', getenv('YII_DEBUG') === '1');
defined('COMMING_SOON') or define('COMMING_SOON', false);
defined('STATUS_SUCCESS') or define('STATUS_SUCCESS', '1');
defined('STATUS_FAILURE') or define('STATUS_FAILURE', '0');
defined('YII_ENV') or define('YII_ENV', getenv('YII_ENV') ?: 'prod');

// vendor/ sits next to config/ (this repository) or one level above (installed layout used by index.php)
$vendor = is_file(__DIR__ . '/../vendor/autoload.php') ? __DIR__ . '/../vendor' : __DIR__ . '/../../vendor';
require $vendor . '/autoload.php';
require $vendor . '/yiisoft/yii2/Yii.php';

$config = (require __DIR__ . '/../config/service-entry.php')($service);

(new yii\web\Application($config))->run();
