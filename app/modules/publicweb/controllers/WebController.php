<?php
namespace app\modules\publicweb\controllers;

use app\modules\api\controllers\BKController;
use Symfony\Component\CssSelector\Node\FunctionNode;
use yii;
use yii\filters\AccessControl;
use yii\filters\AccessRule;
use yii\helpers\ArrayHelper;
use app\modules\auth\services\AuthSettings;
use yii\data\ActiveDataProvider;
use yii\helpers\Url;
use app\models\User;
use app\modules\admin\models\Auth;
use app\modules\admin\models\WebSetting;
use app\modules\admin\models\AuthSession;
use app\modules\admin\models\FavouriteLocation;
use app\modules\admin\models\FcmNotification;
use app\modules\admin\models\Notification;

use Exception;
use kartik\mpdf\Pdf;
use yii\helpers\Json;
 
/**
 * Public website API: /api/web/*  - contact form (contact) and a latitude/longitude helper (lat-lon) for the
 * public website. No app login.
 */
class WebController extends BKController
{
    // [doc:start] behaviors - Behaviors
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'corsFilter' => [
                'class' => \yii\filters\Cors::className(),
                'cors' => [
                    // restrict access to
                    'Origin' => [
                        'http://localhost:*',
                        'http://localhost:58600',
                    ],
                    // Allow only POST and PUT methods
                    'Access-Control-Request-Method' => ['POST', 'PUT'],
                    // Allow only headers 'X-Wsse'
                    'Access-Control-Request-Headers' => ['X-Wsse'],
                    // Allow credentials (cookies, authorization headers, etc.) to be exposed to the browser
                    'Access-Control-Allow-Credentials' => true,
                    // Allow OPTIONS caching
                    'Access-Control-Max-Age' => 3600,
                    // Allow the X-Pagination-Current-Page header to be exposed to the browser.
                    'Access-Control-Expose-Headers' => [
                        'X-Pagination-Current-Page',
                    ],
                ],
            ],

            'access' => [
                'class' => AccessControl::className(),
                'ruleConfig' => [
                    'class' => AccessRule::className(),
                ],

                'rules' => [
                    [
                        'actions' => [
                            'contact',
                            'lat-lon',
                           
                         
                        ],  

                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => [
                            'contact',
                           'lat-lon',
                          

                        ],

                        'allow' => true,
                        'roles' => ['?', '*'],
                    ],
                ],
            ],
        ]);
    }
    // [doc:end] behaviors

    // [doc:start] actionIndex - /api/web/index - Index
    public function actionIndex()
    {
        $data['details'] = ['hello'];
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionIndex

    // [doc:start] actionContact - /api/web/contact - Contact
    public function actionContact()
    {
       

        $save=true;
        if($save)
        {
            $data['status'] = self::API_OK;
            $data['data'] =json_encode($_REQUEST);
        }
        else{
            $data['status'] = self::API_NOK;
            $data['error'] ="data not save";
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionContact
   

    // [doc:start] actionLatLon - /api/web/lat-lon - Lat Lon
    public function actionLatLon()
    {
       

        
        if(isset($_REQUEST['user_id']))
        {
            $user_id=$_REQUEST['user_id'];
        }
        else
        {
            $data['error'] ="user_id is empty";
            return $this->sendJsonResponse($data);
        }
        if(isset($_REQUEST['booking_id']))
        {
            $booking_id=$_REQUEST['booking_id'];
        }
        else
        {
            $data['error'] ="booking_id is empty";
            return $this->sendJsonResponse($data);
        }
       
        if(isset($_REQUEST['lat']))
        {
            $lat=$_REQUEST['lat'];
        }
        else
        {
            $data['error'] ="lat is empty";
            return $this->sendJsonResponse($data);
        }

        if(isset($_REQUEST['lon']))
        {
            $lon=$_REQUEST['lon'];
            
        }
        else
        {
            $data['error'] ="lon is empty";
            return $this->sendJsonResponse($data);
        }


$save=Yii::$app->db->createCommand()
->insert('booking_lat_long', [
'booking_id' => $booking_id,
'lat' => "$lat",
'lon' => "$lon",
'status' => 1,
'created_on' => date("Y-m-d"),
'updated_on' => date('Y-m-d H:i:s'),
'create_user_id' => $user_id,
'update_user_id' => $user_id,
])->execute();


        // $save=true;
        if($save)
        {
            $data['status'] = self::API_OK;
            $data['data'] =json_encode($_REQUEST);
        }
        else{
            $data['status'] = self::API_NOK;
            $data['error'] ="data not save";
        }
        return $this->sendJsonResponse($data);
    }
    // [doc:end] actionLatLon
   

    
   
}  
 