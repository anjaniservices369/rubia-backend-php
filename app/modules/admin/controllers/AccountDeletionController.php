<?php

namespace app\modules\admin\controllers;

use app\models\User;
use Yii;
use yii\data\SqlDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;

class AccountDeletionController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            $isCustomerOrMainDashboard = !empty(Yii::$app->params['customerDashboard'])
                                || empty(Yii::$app->params['serviceDashboard']);

                            return $isCustomerOrMainDashboard && User::isAdmin();
                        },
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $db = Yii::$app->customerDb;
        $totalCount = (int) $db->createCommand('SELECT COUNT(*) FROM account_deletion_request')->queryScalar();
        $dataProvider = new SqlDataProvider([
            'sql' => 'SELECT id, role, source, status, requested_on, completed_on
                FROM account_deletion_request
                ORDER BY requested_on DESC, id DESC',
            'totalCount' => $totalCount,
            'key' => 'id',
            'pagination' => ['pageSize' => 25],
        ]);

        return $this->render('index', ['dataProvider' => $dataProvider]);
    }
}
