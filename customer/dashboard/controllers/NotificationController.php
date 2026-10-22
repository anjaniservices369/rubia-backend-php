<?php

namespace app\modules\admin\controllers;

use Yii;
use app\modules\admin\models\Notification;
use app\modules\admin\models\search\NotificationSearch;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * NotificationController: admin notification list (the header bell links here).
 */
class NotificationController extends Controller
{
    // [doc:start] behaviors - Behaviors
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index', 'view', 'update', 'delete', 'update-read', 'get-notification'],
                        'roles' => ['@'],
                    ],
                    [
                        'allow' => false,
                    ],
                ],
            ],
        ];
    }
    // [doc:end] behaviors

    // [doc:start] actionIndex - /admin/notification/index - Index
    public function actionIndex()
    {
        $searchModel = new NotificationSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    // [doc:end] actionIndex

    // [doc:start] actionView - /admin/notification/view - Shows a notification and marks it as read.
    /**
     * Shows a notification and marks it as read.
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        if (!$model->mark_read) {
            $model->mark_read = 1;
            $model->save(false);
        }

        return $this->render('view', [
            'model' => $model,
        ]);
    }
    // [doc:end] actionView

    // [doc:start] actionUpdate - /admin/notification/update - Update
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }
    // [doc:end] actionUpdate

    // [doc:start] actionDelete - /admin/notification/delete - Delete
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }
    // [doc:end] actionDelete

    // [doc:start] actionUpdateRead - /admin/notification/update-read - Marks the notification as read and returns to the list.
    /**
     * Marks the notification as read and returns to the list.
     */
    public function actionUpdateRead($id)
    {
        $model = $this->findModel($id);
        $model->mark_read = 1;
        $model->save(false);

        return $this->redirect(['index']);
    }
    // [doc:end] actionUpdateRead

    // [doc:start] actionGetNotification - /admin/notification/get-notification - Unread notifications for the header polling script.
    /**
     * Unread notifications for the header polling script.
     */
    public function actionGetNotification()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $unread = (new Notification())->getLatestUnreadNotification();
        $detail = [];
        foreach ((array) $unread as $noty) {
            $detail[] = [
                'id' => $noty->id,
                'order_id' => $noty->order_id,
                'title' => $noty->title,
            ];
        }

        return [
            'count' => (int) Notification::find()->where(['mark_read' => 0])->count(),
            'detail' => $detail,
        ];
    }
    // [doc:end] actionGetNotification

    protected function findModel($id)
    {
        if (($model = Notification::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
