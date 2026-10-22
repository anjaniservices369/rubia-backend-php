<?php
use app\models\Setting;
use yii\helpers\Url;
use kartik\datecontrol\Module;
use app\models\User; 

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';
// services that live in their own folder (services/<service>): class map + the views of their dashboards
$serviceCode = require __DIR__ . '/services-classmap.php';
// var_dump(\Yii::$app->user->id);

$config = [ 
	

		'timeZone' => 'Asia/Kolkata',
		'id' => 'main',
		'name' => 'Rubia',
		'basePath' => dirname ( __DIR__ ),
		'bootstrap' => [ 
				'log' ,
				// service modules that register application-wide components
				'messaging', 'auth', 'dispatch',
		],
		'aliases' => [ 
				'@bower' => '@vendor/bower-asset',
				'@npm' => '@vendor/npm-asset' 
		],
		
		'components' => [ 
			'config' => array(
				'class' => 'app\components\SettingConfig'),
			   //   'cache'=>3600,
			 //),
			
				'assetManager' => [ 
						// the address of every style sheet and script carries the time the file was changed (?v=...), so a browser never keeps showing an old look
						'appendTimestamp' => true,
						// Yii names the published copy of a folder after the folder's own time, which does not change when a file inside is edited, so an edited
						// style sheet of the dashboard never reached the browser. For the dashboard's own assets the name now follows the newest file inside.
						'hashCallback' => function ($path) {
							$dir = is_file($path) ? dirname($path) : $path;
							$latest = (int) @filemtime($dir);
							$norm = str_replace('\\', '/', $dir);
							if (strpos($norm, '/app/modules/admin/assets') !== false || preg_match('#/app/assets(/|$)#', $norm)) {
								foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $file) {
									$latest = max($latest, $file->getMTime());
								}
							}

							return sprintf('%x', crc32($dir . $latest . \Yii::getVersion()));
						},
						'bundles' => [ 
								'yii\web\JqueryAsset' => [ 
										'sourcePath' => null, // do not publish the bundle
										'js' => [ 
												'https://cdnjs.cloudflare.com/ajax/libs/jquery/1.11.2/jquery.min.js'  // use custom jquery
										
										] 
										],
										
						
						] 
				],
				'request' => [ 
						// !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
						'cookieValidationKey' => (getenv('COOKIE_VALIDATION_KEY') ?: (require __DIR__ . '/secrets-loader.php')['cookieValidationKey'] ?? '') 
				],
				'i18n' => [
					'translations' => [
					   'app*' => [
						  'class' => 'yii\i18n\PhpMessageSource',
						  'fileMap' => [
							 'app' => 'app.php'
						  ],
					   ],
					],
				 ],
				
				'cache' => [ 
						'class' => 'yii\caching\FileCache' 
				],
				'user' => [
						'class' => 'app\components\ApiUser',
						'identityClass' => 'app\models\User',
						'enableAutoLogin' => true 
				],
				'defaultRoute' => 'site/index',
				'errorHandler' => [ 
						'errorAction' => 'site/error' 
				],
				
				
				
				'mailer' => [
						// SMTP via Gmail. Username/password (an App Password, not the account's real password)
						// come from the dashboard: Integrations > Gmail SMTP, which DashboardMailer overrides
						// these placeholder values with at runtime.
						'class' => 'app\components\DashboardMailer',
						'transport' => [
							'scheme' => 'smtp',
							'host' => 'smtp.gmail.com',
							'port' => 587,
							'username' => '',
							'password' => '',
						],
				],
				'log' => [ 
						'traceLevel' => YII_DEBUG ? 3 : 0,
						'targets' => [ 
								[
										'class' => 'yii\log\FileTarget',
										'categories' => ['yii\symfonymailer\Mailer::*', 'mail'],
										'levels' => [
												'error',
												'warning'
										]
								],
								[
									'class' => 'yii\log\FileTarget',
									'levels' => ['error', 'warning'],
									// OTP/SMS, push notification and auth (JWT) problems; request variables are not dumped.
									'categories' => ['otp', 'push', 'auth', 'geo', 'api', 'upload'],
									'logVars' => [],
									'logFile' => '@runtime/logs/app.log',
								],
								[
					                'class' => 'yii\log\FileTarget',
					                'levels' => ['info', 'trace', 'error', 'warning'],
					                'categories' => ['postData'],
					                'logVars' => [],
					                'logFile' => '@runtime/logs/postData.log',
					            ]  
						] 
				],
				'view' => [ 
						'theme' => [ 
								'pathMap' => [ 
										'@app/views' => '@app/views' 
								],
								'basePath' => '@app/themes',
								'baseUrl' => '@web/themes' 
						] 
				],
				
				'db' => $db,
				'urlManager' => [ 
						'class' => 'yii\web\UrlManager',
						// Disable index.php
						'showScriptName' => false,
						// Disable r= routes
						'enablePrettyUrl' => true,
						'rules' => array_merge(require __DIR__ . '/api-routes.php', [ 
								'' => 'site/index' ,
								'privacy-policy' => 'site/privacy-policy',
								'terms-conditions' => 'site/terms-conditions',
								'all-stores' => 'site/all-stores',
								'all-categories' => 'site/all-categories',
								'join' => 'site/join',
								'signin' => 'site/signin',
								'store/<slug:[0-9a-zA-Z\-]+>/' => 'site/store',
								//'categories' => 'site/categories',
								'categories/<slug:[0-9a-zA-Z\-]+>/' => 'site/categories',
								'<controller:\w+>/<id:\d+>' => '<controller>/view',
								'<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
								'<controller:\w+>/<action:\w+>' => '<controller>/<action>',
								'media' => 'media/default/index' ,
                        		'admin/booking/driver-cancel-duplicate/index' => 'admin/driver-cancel-duplicate/index',
						])
				] ,
				'orderStatus' =>[
					'class' => 'app\components\OrderStats',
				],
				'stats' =>[
					'class' => 'app\components\Dashboard',
				],
				
		
		],
		'modules' => [ 
			
			'noty' => [
				'class' => 'lo\modules\noty\Module',
			],
			
			'admin' => [
				'class' => 'app\modules\admin\Module',
			],
				'comingsoon' => [ 
						'class' => 'app\modules\comingsoon\Module' 
				],
				'email-templates' => [
					'class' => bl\emailTemplates\EmailTemplates::class,
					'languageProvider' => [
					   'class' => bl\emailTemplates\providers\ConfigLanguageProvider::class,
					   'languages' => [
						[
							'locale' => 'en',
							'label' => 'English',
						],
					],
					'defaultLanguage' => [
						'locale' => 'en',
						'label' => 'English',
					],
					
					]
				],
			
				'support' => [ 
						'class' => 'app\modules\support\Module' 
				],
				'media' => [ 
						'class' => 'app\modules\media\Module' 
				],
				'gridview' => [
					'class' => 'kartik\grid\Module',
				],
				// ---- API: kernel + one module per service (see config/api-routes.php for the public URLs)
				'api' => ['class' => 'app\modules\api\Api'],
				'admin-api' => ['class' => 'app\modules\adminapi\Module'],   // reporting for the super admin dashboard
				'customer-api' => ['class' => 'app\modules\customerapi\Module'],   // what the services ask the user-app system
				// each taxi service on its own address (/api/bike, /api/auto, /api/car and the -user ones)
				'biketaxidriver' => ['class' => 'app\modules\biketaxidriver\Module'],
				'biketaxirider' => ['class' => 'app\modules\biketaxirider\Module'],
				'autotaxidriver' => ['class' => 'app\modules\autotaxidriver\Module'],
				'autotaxirider' => ['class' => 'app\modules\autotaxirider\Module'],
				'cartaxidriver' => ['class' => 'app\modules\cartaxidriver\Module'],
				'cartaxirider' => ['class' => 'app\modules\cartaxirider\Module'],
				'haltingdriver-app' => ['class' => 'app\modules\haltingdriverapp\Module'],
				'haltingdriver-customer' => ['class' => 'app\modules\haltingdrivercustomer\Module'],   // the service's customer-side booking flow
				'customer-app' => ['class' => 'app\modules\customerapp\Module', 'controllerNamespace' => 'app\glue'],   // shared process: user app + Halting drivers booking flow (app/glue)
				'manager-app' => ['class' => 'app\modules\managerapp\Module'],
				'file-storage' => ['class' => 'app\modules\filestorage\Module'],
				'public-web' => ['class' => 'app\modules\publicweb\Module'],
				// ---- service modules (no routes)
				'messaging' => ['class' => 'app\modules\messaging\Module'],
				'auth' => ['class' => 'app\modules\auth\Module'],
				'dispatch' => ['class' => 'app\modules\dispatch\Module'],
				'payments' => ['class' => 'app\modules\payments\Module'],
				'geo' => ['class' => 'app\modules\geo\Module'], 
				'datecontrol' => [
					'class' => 'kartik\datecontrol\Module',
		
					// format settings for displaying each date attribute (ICU format example)
					'displaySettings' => [
						Module::FORMAT_DATE => 'dd-MM-yyyy',
						Module::FORMAT_TIME => 'HH:mm:ss a',
						Module::FORMAT_DATETIME => 'dd-MM-yyyy HH:mm:ss a',
					],
		
					// format settings for saving each date attribute (PHP format example)
					'saveSettings' => [
						Module::FORMAT_DATE => 'php:U', // saves as unix timestamp
						Module::FORMAT_TIME => 'php:H:i:s',
						Module::FORMAT_DATETIME => 'php:Y-m-d',
					],
		
					// set your display timezone
					'displayTimezone' => 'Asia/Kolkata',
		
					// set your timezone for date saved to db
					'saveTimezone' => 'UTC',
		
					// automatically use kartik\widgets for each of the above formats
					'autoWidget' => true,
		
					// use ajax conversion for processing dates from display format to save format.
					'ajaxConversion' => true,
		
					// default settings for each widget from kartik\widgets used when autoWidget is true
					'autoWidgetSettings' => [
						Module::FORMAT_DATE => ['type' => 2, 'pluginOptions' => ['autoclose' => true]], // example
						Module::FORMAT_DATETIME => [], // setup if needed
						Module::FORMAT_TIME => [], // setup if needed
					],
		
					// custom widget settings that will be used to render the date input instead of kartik\widgets,
					// this will be used when autoWidget is set to false at module or widget level.
					'widgetSettings' => [
						Module::FORMAT_DATE => [
							'class' => 'yii\jui\DatePicker', // example
							'options' => [
								'dateFormat' => 'php:d-M-Y',
								'options' => ['class' => 'form-control'],
							],
						],
					],
					// other settings
				],

				'db-manager' => [
					'class' => 'bs\dbManager\Module',
					// path to directory for the dumps
					'path' => '@app/backups',
					// list of registerd db-components
					'dbList' => ['db'],
					'as access' => [
						'class' => 'yii\filters\AccessControl',
						'rules' => [
							[
								'allow' => true,
								'roles' => ['@'],
							],
					],
				],
				],
				
				
		],
		'params' => $params ,
		// 'preload' => 'config'
];

// dashboards of the separate services show their own views over the shared admin views
$config['components']['view']['theme']['pathMap'] = $serviceCode['views'] + $config['components']['view']['theme']['pathMap'];

if (YII_ENV_DEV && class_exists('yii\gii\Module')) {
    // configuration adjustments for 'dev' environment.
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => \yii\debug\Module::class,
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['*'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => \yii\gii\Module::class,
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['*'],

    ];
}

return $config;
