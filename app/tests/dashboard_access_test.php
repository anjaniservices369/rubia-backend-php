<?php

define('YII_ENV', 'test');
define('YII_DEBUG', true);
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__) . '/config/services-classmap.php';

use app\models\User;
use yii\web\IdentityInterface;

$app = new yii\web\Application([
    'id' => 'dashboard-access-test',
    'basePath' => dirname(__DIR__),
    'components' => [
        'request' => [
            'cookieValidationKey' => 'dashboard-access-test-only',
            'scriptFile' => dirname(__DIR__) . '/index.php',
            'scriptUrl' => '/index.php',
            'hostInfo' => 'http://localhost',
        ],
        'urlManager' => ['enablePrettyUrl' => true, 'showScriptName' => false],
        'user' => ['identityClass' => User::class, 'enableAutoLogin' => false],
    ],
]);
$app->errorHandler->unregister();
$app->request->setUrl('/admin/booking/view?id=1698');
$app->request->headers->set('Accept', 'text/html');
$_SERVER['REQUEST_METHOD'] = 'GET';
$module = new yii\base\Module('admin', $app);
$controller = new app\modules\admin\controllers\BookingController('booking', $module);
$action = new yii\base\InlineAction('view', $controller, 'actionView');
$config = require dirname(__DIR__) . '/config/rbac.php';
$filter = Yii::createObject($config['as adminAccess']);
$checks = 0;
$assert = function ($condition, $message) use (&$checks) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
};
$identity = function ($role) {
    return new class($role) implements IdentityInterface {
        public $user_role;
        public function __construct($role) { $this->user_role = $role; }
        public static function findIdentity($id) { return null; }
        public static function findIdentityByAccessToken($token, $type = null) { return null; }
        public function getId() { return 1; }
        public function getAuthKey() { return 'test-only'; }
        public function validateAuthKey($authKey) { return $authKey === $this->getAuthKey(); }
    };
};

try {
    $app->user->setIdentity(null);
    $assert($filter->beforeAction($action) === false, 'Guest must not enter the dashboard.');
    $assert($app->response->statusCode === 302, 'Guest must be redirected instead of receiving 404.');
    $assert(strpos($app->response->headers->get('Location'), '/site/login') !== false, 'Guest must reach login.');
    $assert($app->user->getReturnUrl() === 'http://localhost/admin/booking/view?id=1698', 'Deep link must survive login.');

    foreach ([User::ROLE_ADMIN, User::ROLE_SUBADMIN, User::ROLE_MANAGER, User::ROLE_FRANCHISE] as $role) {
        $app->user->setIdentity($identity($role));
        $assert($filter->beforeAction($action), 'Global access must allow operator role ' . $role);
        $pageAccess = Yii::createObject($controller->behaviors()['access']);
        $assert($pageAccess->beforeAction($action), 'Booking access must allow operator role ' . $role);
        $site = new app\controllers\SiteController('site', $app);
        $site->actionLogin();
        $assert($site->layout === 'main-login', 'Sign-in must use the dedicated authentication layout.');
        $assert($app->response->headers->get('Location') === 'http://localhost/admin/booking/view?id=1698',
            'Authenticated login must return to the requested booking.');
        $app->user->setReturnUrl(null);
        $site->actionLogin();
        $expected = $role === User::ROLE_MANAGER ? '/admin/booking/index' : '/admin/dashboard';
        $assert(parse_url($app->response->headers->get('Location'), PHP_URL_PATH) === $expected,
            'Operator must have an accessible landing page: ' . $app->response->headers->get('Location'));
        $app->user->setReturnUrl('http://localhost/admin/booking/view?id=1698');
        $site->actionAdminLogin();
        $assert($app->response->headers->get('Location') === 'http://localhost/admin/booking/view?id=1698',
            'Admin login must also preserve the requested booking.');
    }

    $app->user->setIdentity($identity(User::ROLE_MANAGER));
    $dashboard = new app\modules\admin\controllers\DashboardController('dashboard', $module);
    $dashboardAction = new yii\base\InlineAction('index', $dashboard, 'actionIndex');
    $dashboardAccess = Yii::createObject($dashboard->behaviors()['access']);
    try {
        $dashboardAccess->beforeAction($dashboardAction);
        throw new RuntimeException('Manager must not inherit administrator dashboard access.');
    } catch (yii\web\ForbiddenHttpException $expected) {
        $checks++;
    }
    $app->user->setIdentity($identity(User::ROLE_FRANCHISE));
    $manageAction = new yii\base\InlineAction('manage', $controller, 'actionManage');
    try {
        $pageAccess->beforeAction($manageAction);
        throw new RuntimeException('Franchise must not inherit administrator booking-management access.');
    } catch (yii\web\ForbiddenHttpException $expected) {
        $checks++;
    }

    foreach ([User::ROLE_USER, User::ROLE_DRIVER, User::ROLE_TAXI] as $role) {
        $app->user->setIdentity($identity($role));
        try {
            $filter->beforeAction($action);
            throw new RuntimeException('Mobile-only account reached the dashboard.');
        } catch (yii\web\NotFoundHttpException $expected) {
            $checks++;
        }
    }
    $app->user->setIdentity(null);
    $app->request->headers->set('Accept', 'application/json');
    $app->request->setAcceptableContentTypes(['application/json' => []]);
    try {
        $filter->beforeAction($action);
        throw new RuntimeException('JSON request must not receive an HTML login redirect.');
    } catch (yii\web\ForbiddenHttpException $expected) {
        $checks++;
    }
} finally {
    $app->session->destroy();
}

echo "PASS: $checks dashboard access regression checks (no database writes).\n";
