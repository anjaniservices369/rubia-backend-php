<?php
/**
 * this file is merged together with main "app-web" config
 */

use app\models\User;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;

return [
	'as adminAccess' => [
		// TODO: write own class to simplify this check.
		'class' => AccessControl::class,
		'rules' => [
			// custom rule to deny access to "/admin/*" if user doesn't have permission 'administer'.
			[
				'allow'   => false,
				'controllers' => ['admin/*'],
				'matchCallback' => function ($rule, $action) {
					$access = User::isAdmin() || User::isSubAdmin() || User::isManager() || User::isFranchise();
					return !$access;
				},
				'denyCallback' => function ($null, $action) {
					if (\Yii::$app->user->isGuest) {
						return \Yii::$app->user->loginRequired();
					}
					\Yii::$app->errorHandler->errorAction = 'site/error';
					throw new NotFoundHttpException('Page not found.');
				}
			],
			
			// this one is required to make all other site works.
			[
				'allow' => true,
				'controllers' => ['*'],
			],
		],
	],
	// Route based permission filter.
	// 'as routeAccess' => [
	// 	'class' => \justcoded\yii2\rbac\filters\RouteAccessControl::class,
	// 	'allowActions' => [
	// 		'site/*',
	// 		'auth/*',
	// 	],
	// ],

	
];
