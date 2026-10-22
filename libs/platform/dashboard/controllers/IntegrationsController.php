<?php

namespace app\modules\admin\controllers;

use app\models\User;
use app\modules\admin\models\Integrations;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * The dashboard module "Integrations": every third-party service the system uses (Razorpay, Cashfree, verify24x7, 2Factor SMS, OneSignal,
 * SendGrid, Google Maps, the photo storage, OpenMoney) has its own page here with its own keys, its status and a connection test.
 * Nothing about a service is kept on other screens. Administrators only.
 *
 * Each service of this system (Halting drivers, the taxi services, the customer app) has its own dashboard and its own database, so each one
 * has its own integrations settings: the keys of one service are never visible to another.
 */
class IntegrationsController extends Controller
{
    // [doc:start] behaviors - Behaviors
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [[
                    'allow' => true,
                    'matchCallback' => function () {
                        $u = Yii::$app->user->identity;

                        return $u && $u->user_role === User::ROLE_ADMIN;
                    },
                ]],
            ],
        ];
    }
    // [doc:end] behaviors

    private function definition($id)
    {
        $def = Integrations::find($id);
        if ($def === null) {
            throw new NotFoundHttpException('There is no such integration.');
        }

        return $def;
    }

    // [doc:start] actionIndex - /admin/integrations/index - Every third-party service, grouped, with its status.
    /**
     * Every third-party service, grouped, with its status.
     */
    public function actionIndex()
    {
        return $this->render('index');
    }
    // [doc:end] actionIndex

    // [doc:start] actionView - /admin/integrations/view - One service: its keys, status, webhook address and connection test. Saves the boxes when the form is posted.
    /**
     * One service: its keys, status, webhook address and connection test. Saves the boxes when the form is posted.
     */
    public function actionView($id)
    {
        $def = $this->definition($id);
        if (Yii::$app->request->isPost) {
            try {
                $changed = Integrations::save($id, Yii::$app->request->post('s', []));
                Yii::$app->session->setFlash('success', $changed ? 'Saved (' . $changed . ' setting' . ($changed === 1 ? '' : 's') . ' changed).' : 'Nothing changed.');
            } catch (\InvalidArgumentException $e) {
                Yii::$app->session->setFlash('error', 'Not saved. ' . $e->getMessage());
            }

            return $this->redirect(['view', 'id' => $id]);
        }

        return $this->render('view', ['id' => $id, 'def' => $def]);
    }
    // [doc:end] actionView

    // [doc:start] actionTest - /admin/integrations/test - Checks the connection and the keys of one service with a harmless call. POST only. Answers {ok, message}.
    /**
     * Checks the connection and the keys of one service with a harmless call. POST only. Answers {ok, message}.
     */
    public function actionTest($id)
    {
        $this->definition($id);
        if (!Yii::$app->request->isPost) {
            throw new \yii\web\MethodNotAllowedHttpException();
        }
        Yii::$app->response->format = Response::FORMAT_JSON;

        return Integrations::test($id);
    }
    // [doc:end] actionTest
}
