<?php

namespace app\modules\adminapi\controllers;

use app\modules\api\controllers\BKController;
use app\modules\billing\services\ServiceReport;
use app\modules\customerapi\services\ServiceSignature;
use Yii;

/**
 * Reporting endpoint of a service for the super admin dashboard.
 *
 *   GET /api/admin-report/summary?module=N&from=YYYY-MM-DD&to=YYYY-MM-DD
 *
 * Only calls signed with ADMIN_SECRET_<SERVICE> are answered (same HMAC scheme as the user-app gateway, other
 * direction); everything else is 403. Read-only; returns totals, never personal data.
 */
class ReportController extends BKController
{
    // [doc:start] behaviors - Behaviors
    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::class,
                'rules' => [['allow' => true, 'actions' => ['summary'], 'roles' => ['?', '@']]],
            ],
        ];
    }
    // [doc:end] behaviors

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        if (ServiceSignature::verifyRequest('ADMIN') === null) {
            Yii::$app->response->statusCode = 403;
            Yii::$app->response->data = ['status' => 'NOK', 'error' => 'Forbidden'];

            return false;
        }

        return true;
    }

    // [doc:start] actionSummary - /api/admin-report/summary - Today's and the period's numbers of one service on this database.
    /**
     * Today's and the period's numbers of one service on this database.
     */
    public function actionSummary($module = 0, $from = null, $to = null)
    {
        if ((Yii::$app->params['serviceEntry']['service'] ?? '') === 'customer') {
            return $this->sendJsonResponse(['status' => self::API_OK, 'details' => \app\modules\customerapp\services\CustomerReport::summary($from, $to)]);
        }
        $module = (int) $module ?: (int) (Yii::$app->params['serviceEntry']['module'] ?? 1);

        return $this->sendJsonResponse(['status' => self::API_OK, 'details' => ServiceReport::summary($module, $from, $to)]);
    }
    // [doc:end] actionSummary
}
