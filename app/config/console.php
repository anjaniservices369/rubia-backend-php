<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';
$customerDb = $db;
$customerDb['dsn'] = getenv('DB_DSN_CUSTOMER') ?: $db['dsn'];
$customerDb['username'] = getenv('DB_USER_CUSTOMER') ?: $db['username'];
$customerDb['password'] = getenv('DB_PASSWORD_CUSTOMER') ?: $db['password'];
require __DIR__ . '/services-classmap.php';   // classes of the services that live in services/<service>

$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    // Same time zone as the web app so timestamps written by either side compare correctly.
    'timeZone' => 'Asia/Kolkata',
    'controllerNamespace' => 'app\commands',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'customerDb' => $customerDb,
    ],
    'params' => $params,
    /*
    'controllerMap' => [
        'fixture' => [ // Fixture generation command line.
            'class' => 'yii\faker\FixtureController',
        ],
    ],
    */
];

if (YII_ENV_DEV && class_exists('yii\gii\Module')) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;
