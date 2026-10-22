<?php

/**
 * Shared bootstrap of the per-service dashboard entry points (web/driver-dashboard.php ...).
 *
 * @var string $service set by the entry point
 */
if (!isset($service)) {
    http_response_code(404);
    exit;
}

defined('YII_DEBUG') or define('YII_DEBUG', getenv('YII_DEBUG') === '1');
defined('YII_ENV') or define('YII_ENV', getenv('YII_ENV') ?: 'prod');
defined('COMMING_SOON') or define('COMMING_SOON', false);
defined('STATUS_SUCCESS') or define('STATUS_SUCCESS', '1');
defined('STATUS_FAILURE') or define('STATUS_FAILURE', '0');

$vendor = is_file(__DIR__ . '/../vendor/autoload.php') ? __DIR__ . '/../vendor' : __DIR__ . '/../../vendor';
require $vendor . '/autoload.php';
require $vendor . '/yiisoft/yii2/Yii.php';

$config = (require __DIR__ . '/../config/dashboard-entry.php')($service);

(new yii\web\Application($config))->run();
