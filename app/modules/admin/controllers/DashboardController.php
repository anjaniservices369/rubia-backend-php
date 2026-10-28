<?php

namespace app\modules\admin\controllers;

use app\modules\admin\services\DashboardStats;
use app\modules\geo\services\ServiceModules;
use yii;
use app\models\User;
use app\modules\admin\models\base\Orders;
use app\modules\admin\models\base\Booking;
use app\modules\admin\models\base\RideRequest;
use app\modules\admin\models\SubscriptionOrders;
use app\modules\admin\forms\AdminUserForm;
use app\modules\admin\forms\UserForm;


/**
 * Admin dashboard home page and the logged-in admin's own profile.
 *
 * The numbers on the home page come from DashboardStats (modules/admin/services): drivers online, rides waiting
 * for a driver, bookings by state, push-notification status. Nothing here changes bookings or money.
 */
class DashboardController extends Controller
{
	// [doc:start] behaviors - Guests are sent to the sign-in page instead of hitting a null identity. Logged-in users who are not.
	/**
	 * Guests are sent to the sign-in page instead of hitting a null identity. Logged-in users who are not
	 * admin/sub-admin/franchise are denied outright rather than shown a dashboard with its numbers zeroed out.
	 */
	public function behaviors()
	{
		return [
			'access' => [
				'class' => \yii\filters\AccessControl::className(),
				'rules' => [
					['allow' => true, 'actions' => ['error']],
					[
						'allow' => true,
						'roles' => ['@'],
						'matchCallback' => function () {
							return User::isAdmin() || User::isSubAdmin() || User::isFranchise();
						},
					],
				],
			],
		];
	}
	// [doc:end] behaviors

	// [doc:start] actions - Actions
	/**
	 * @inheritdoc
	 */
	public function actions()
	{
		return [
			'error'   => [
				'class' => 'yii\web\ErrorAction',
			],
		];
	}
	// [doc:end] actions

	// [doc:start] actionIndex - /admin/dashboard/index - Displays dashboard with some statistics.
	/**
	 * Displays dashboard with some statistics.
	 *
	 * @return string
	 */
	public function actionIndex()
	{
		$identity = \Yii::$app->user->identity;
		$isAdmin = $identity->user_role == User::ROLE_ADMIN;
		$isFranchise = $identity->user_role == User::ROLE_FRANCHISE;

		// Administrators see everything, franchise users only their city, everyone else nothing.
		$cityId = $isFranchise ? $identity->city_id : null;
		$visible = $isAdmin || $isFranchise;

		// 0 on the main super admin panel (sees every service); 1/2/3/4 on one service's own dashboard
		// (ServiceModules: Driver/Taxi/Auto/Bike), set by that service's entry script (dashboard-entry.php).
		$serviceDashboard = (int) (Yii::$app->params['serviceDashboard'] ?? 0);
		$onMainPanel = $serviceDashboard === 0;

		$empty = ['all' => 0, 'completed' => 0, 'cancelled' => 0, 'pending' => 0, 'ongoing' => 0, 'noDriver' => 0, 'driverRejected' => 0];
		// Driver Bookings is the Halting drivers service's own booking table: show it on the main panel
		// (which sees every service) or on Halting drivers' own dashboard, never on another service's.
		$showDriverBookings = $visible && ($onMainPanel || $serviceDashboard === ServiceModules::DRIVER);
		$bookings = $showDriverBookings ? DashboardStats::bookings($cityId) : $empty;

		$params = [
			'modelAllBookings' => $bookings['all'],
			'modelCompleteBookings' => $bookings['completed'],
			'modelCancelledBookings' => $bookings['cancelled'],
			'modelPendingBookings' => $bookings['pending'],
			'modelOngoingBookings' => $bookings['ongoing'],
			'modelDriverRejectBookings' => $bookings['driverRejected'],
		];

		// One block per ride category (Bike Taxi, Auto Taxi, Car Taxi, ...). A franchise sees its own vehicle
		// only; on one taxi-family service's own dashboard, only that service's own category; Halting
		// drivers' own dashboard shows none of these (it has no taxi/ride categories of its own).
		$serviceCategoryKey = [ServiceModules::TAXI => 'taxi', ServiceModules::AUTO => 'auto', ServiceModules::BIKE => 'bike'];
		$rideBlocks = [];
		if ($visible && $serviceDashboard !== ServiceModules::DRIVER) {
			foreach (DashboardStats::categories() as $category) {
				if ($isFranchise && (int) $identity->vehicle_id !== $category['id']) {
					continue;
				}
				if (!$onMainPanel && ($serviceCategoryKey[$serviceDashboard] ?? null) !== $category['key']) {
					continue;
				}
				$rideBlocks[] = ['label' => $category['label'], 'stats' => DashboardStats::rides($category['id'], $cityId)];
			}
		}
		$params['rideBlocks'] = $rideBlocks;
		$params['showDriverBookings'] = $showDriverBookings;
		// Site-wide operational numbers (drivers online, rides waiting, etc.) belong on the main panel
		// only -- not the customer app's dashboard (no taxi/ride operations of its own), and not any one
		// service's own dashboard either (they are not that one service's own numbers).
		$params['operations'] = ($isAdmin && $onMainPanel) ? DashboardStats::operations() : null;
		return $this->render('index', $params);
	}
	// [doc:end] actionIndex

	
	// [doc:start] actionUpdateProfile - /admin/dashboard/update-profile - Profile editor. Everybody edits their own profile; an administrator may edit anyone's.
	/**
	 * Profile editor. Everybody edits their own profile; an administrator may edit anyone's.
	 * Only profile data is applied: first/last name, email and password. Role, status and city can be
	 * changed only by an administrator, and only to a role the administrator is allowed to assign.
	 */
	public function actionUpdateProfile($id)
	{
		$actor = Yii::$app->user->identity;
		$model = $this->findUserModel($id);
		if (!User::canManage($actor, $model)) {
			throw new \yii\web\ForbiddenHttpException('You are not allowed to edit this profile.');
		}

		$isAdmin = $actor->user_role == User::ROLE_ADMIN;
		$userFormClass = $isAdmin ? AdminUserForm::class : UserForm::class;
		$userForm = new $userFormClass(['scenario' => 'update']);
		$userForm->setAttributes($model->attributes);
		$request = Yii::$app->request;

		if ($request->isPost) {
			$userForm->load($request->post());
			if ($userForm->validate()) {
				$editable = ['first_name', 'last_name', 'email'];
				if ($isAdmin) {
					$editable = array_merge($editable, ['user_role', 'status', 'city_id']);
					if ($userForm->user_role && !in_array($userForm->user_role, User::assignableRoles($actor), true)) {
						throw new \yii\web\ForbiddenHttpException('You are not allowed to assign this role.');
					}
				}
				foreach ($editable as $attribute) {
					$model->$attribute = $userForm->$attribute;
				}
				if ($userForm->password) {
					$model->setPassword($userForm->password);
				}
				if ($model->save()) {
					return $this->render('update-success', ['model' => $model]);
				}
				$userForm->addErrors($model->errors);
			}
		}

		return $this->render('update-profile', ['model' => $userForm]);
	}
	// [doc:end] actionUpdateProfile


	// public function actionView($id) {
	// 	$model = User::find()->where(['id' => $id])->one();
	// 	return $this->render('view_old',['model' => $model]);

	// }

    protected function findUserModel($id)
{
    if ($id !== null && ($model = User::findOne($id)) !== null) {
        return $model;
    } else {
        throw new NotFoundHttpException('The requested page does not exist.');
    }
}

}

