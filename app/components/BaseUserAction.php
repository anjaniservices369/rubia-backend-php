<?php
/**
 * Created by PhpStorm.
 * User: abhi
 * Date: 4/29/2018
 * Time: 12:40 PM
 */

namespace app\components;


class BaseUserAction extends BaseWidget
{

    public $html_element;
    public $current_model;
    public $attribute;
    public $states;
    public $actions;
    public $allowed;
    public $title;

    public function init() {
        if (empty ( $this->actions ))
            $this->actions = $this->states;
        if (empty ( $this->allowed ))
            $this->allowed = $this->actions;
        $this->title = 'Please select operations';
        parent::init ();
    }

    public function appendStatesHtml() {
      if (isset ( $_POST ['get-state'] )) {
            $submit = trim ( $_POST ['get-state'] );
            $state_list = $this->states;
            $actions = $this->actions;
          $state_id = array_search ( $submit, $actions );
            if ($state_id >= 0 && $state_id != $this->current_model->{$this->attribute}) {
                $old_state = $state_list [$this->current_model->{$this->attribute}];
                $new_state = $state_list [$state_id];
                $this->current_model->{$this->attribute} = $state_id;
                if ($this->current_model->save (false)) {
                        \Yii::$app->session->setFlash ( 'succes', 'State Changed.' );
                        $msg = 'State Changed : ' . $old_state . ' to ' . $new_state;
                    } else {
                    print_r($this->current_model->getErrors());exit;
                         $error='Something Goes Wrong';
                        \Yii::$app->session->setFlash ( 'error', $error );
                    }

            }
            \Yii::$app->controller->redirect ( array (
                'view',
                'id' => $this->current_model->id
            ) );
        }

        if (! empty ( $this->current_model ))
        {
            echo $this->render ( 'append-html', [
                'model' => $this->current_model,
                'allowed' => $this->allowed,
                'attribute' => $this->attribute
            ] );
        }

    }




}