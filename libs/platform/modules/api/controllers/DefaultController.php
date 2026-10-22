<?php

namespace app\modules\api\controllers;

use Yii;
use Yii\web\Response;
use yii\web\Controller;
use yii\filters\AccessControl;
use app\components\VController;
use app\modules\admin\models\ClientsList;
 

class DefaultController extends Controller {
	
	// [doc:start] behaviors - Behaviors
	public function behaviors() {
		return [ 
				'access' => [ 
						'class' => AccessControl::className (),
						'only' => [ 
								'index',
						],
						'rules' => [ 
								[ 
										'actions' => [ 
												'index',
												'clients'
										],
										'allow' => true,
								] 
						]
						 
				] 
		]
		;
	}
	// [doc:end] behaviors
	// [doc:start] actionIndex - /api/default/index - Index
	public function actionIndex() {
		
		
	}
	// [doc:end] actionIndex
	
	// [doc:start] actionClients - /api/default/clients - Clients
	public function actionClients() {
		
		$data = [];
		$get = Yii::$app->request->get();
      
		if(!empty($post)){
			$client = ClientsList::find()->Where(['domain' =>$get['domain']])->one();
			if(!empty($client)){
				$data['status'] = self::API_NOK;
				$data['details'] = $client;
			}else{
			$data['status'] = self::API_NOK;
            $data['error'] = "No domain found";
			}

		}else{
			$data['status'] = self::API_NOK;
            $data['error'] = "No domain found";
        }
        return $this->sendJsonResponse($data);
		
	}
	// [doc:end] actionClients
}
