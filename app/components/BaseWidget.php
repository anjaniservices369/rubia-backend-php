<?php
/**
 * Created by PhpStorm.
 * User: abhi
 * Date: 4/29/2018
 * Time: 12:54 PM
 */

namespace app\components;
use Yii;



use yii\base\Widget;

class BaseWidget extends Widget
{


    public $route_path;
    public $params;
    public function run() {
        if (empty($this->route_path) && empty(Yii::$app->controller)) {
            $this->route_path = Yii::$app->controller->getRoute ();
        }
        if (empty($this->params)) {
            $this->params = Yii::$app->request->getQueryParams ();
        }
        $this->appendStatesHtml ();
    }

}