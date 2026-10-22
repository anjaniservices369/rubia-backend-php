<?php

namespace app\modules\admin\controllers;

use app\models\User;
use app\modules\admin\models\base\CancelRideCharges;
use app\modules\admin\models\search\CancelRideChargesSearch;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Cancellation charges of the Halting drivers service: what a customer owes (or a driver paid) for a booking cancelled after
 * a driver accepted it, how it was split between the driver and the company, and whether it has been collected yet.
 * Read-only: the charges are written by the booking flow (customer cancels) and collected with the customer's next ride.
 *
 * Admin and sub-admin see everything; a manager or franchise bound to this service sees it too (read only).
 */
class CancelRideChargesController extends Controller
{
    // [doc:start] behaviors - Behaviors
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'matchCallback' => function () {
                            $u = Yii::$app->user->identity;

                            return $u && (in_array($u->user_role, [User::ROLE_ADMIN, User::ROLE_SUBADMIN], true)
                                || (in_array($u->user_role, [User::ROLE_MANAGER, User::ROLE_FRANCHISE], true) && (int) $u->vehicle_id === 1));
                        },
                    ],
                ],
            ],
        ];
    }
    // [doc:end] behaviors

    // [doc:start] actionIndex - /admin/cancel-ride-charges/index - Cancellation charges with totals by status; filter by status, who cancelled, booking, driver, customer and period.
    /**
     * Cancellation charges with totals by status; filter by status, who cancelled, booking, driver, customer and period.
     */
    public function actionIndex()
    {
        $searchModel = new CancelRideChargesSearch();
        $params = Yii::$app->request->queryParams;

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $searchModel->search($params),
            'totals' => $searchModel->totals($params),
        ]);
    }
    // [doc:end] actionIndex

    // [doc:start] actionView - /admin/cancel-ride-charges/view - One charge: the booking, who cancelled and why, and how the amount was split.
    /**
     * One charge: the booking, who cancelled and why, and how the amount was split.
     */
    public function actionView($id)
    {
        return $this->render('view', ['model' => $this->findModel($id)]);
    }
    // [doc:end] actionView

    // [doc:start] actionCsv - /admin/cancel-ride-charges/csv - The filtered list as a CSV file (for the accounts).
    /**
     * The filtered list as a CSV file (for the accounts).
     */
    public function actionCsv()
    {
        $searchModel = new CancelRideChargesSearch();
        $provider = $searchModel->search(Yii::$app->request->queryParams);
        $provider->pagination = false;
        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['id', 'booking', 'customer id', 'driver id', 'cancelled by', 'reason', 'charge', 'company share', 'status', 'date']);
        foreach ($provider->getModels() as $m) {
            fputcsv($out, [$m->id, $m->booking_id, $m->user_id, $m->driver_id, $m->cancel_role, $m->cancel_reason, $m->cancel_charge,
                $m->admin_cancellation_earnings, $m->status, $m->created_date]);
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return Yii::$app->response->sendContentAsFile($csv, 'cancellation-charges-' . date('Ymd') . '.csv', ['mimeType' => 'text/csv']);
    }
    // [doc:end] actionCsv

    private function findModel($id)
    {
        $m = CancelRideCharges::findOne((int) $id);
        if ($m === null) {
            throw new NotFoundHttpException('The cancellation charge was not found.');
        }

        return $m;
    }
}
