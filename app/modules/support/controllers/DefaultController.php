<?php

namespace app\modules\support\controllers;

use yii\web\Controller;

/**
 * Default controller for the `support` module
 */
class DefaultController extends Controller
{
    // [doc:start] actionIndex - /support/default/index - Renders the index view for the module.
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }
    // [doc:end] actionIndex
}
