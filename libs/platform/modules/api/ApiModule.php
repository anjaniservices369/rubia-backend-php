<?php

namespace app\modules\api;

use Yii;
use yii\base\Module;
use yii\web\Response;
use app\modules\admin\models\AuthSession;

/**
 * Base class of every mobile/API module. It gives them the same conventions:
 * JSON responses and the auth_code session being resolved before an action runs.
 *
 * The `api` module itself only keeps this kernel and the shared BKController base; the actual
 * services live in their own modules (cartaxidriver, autotaxidriver, biketaxidriver and their riders, haltingdriver-app, customer-app, ...).
 */
abstract class ApiModule extends Module
{
    public function init()
    {
        parent::init();
        Yii::$app->response->format = Response::FORMAT_JSON;
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        AuthSession::authenticateSession();

        return true;
    }
}