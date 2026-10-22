<?php

namespace app\components;
use Yii;
use yii\base\Component;
use app\models\AuthSession;
require 'vendor/autoload.php';
use Google\Client;

class AuthSettings extends Component {

public function getAuthSession($auth_code){

    $auth_session = AuthSession::find()->where(['auth_code'=>$auth_code])->one();
    if(!empty($auth_session)){
        $user = $auth_session->createUser;
        if(!empty($user)){
            $user_id = $user->id;

            return $user_id;
        }
    }


}

public function UserNotification($user_id, $title, $body, $url = '', $image_url = '')
{

    // Custom Notification to Restaurant Owner

    $api_key = 'AAAAbWlKnEw:APA91bHMnHpTLXpm-dRuQcsS2w7_wvl60whGVeRqMtRdRi62hPz6sW0gLFYqvBRD097_LdqWWAoNWB7RW32BUHJAwFp5ZuOFcPD1diGM-Q72OeisXVVssdSK5XMujNE_EPKExTDbkvW8';

    // $device_token = 'eDD3jjgRDYU:APA91bEo4F8Z3cxnG680yM6-dSB_Cf6degECRnhkBatkvSBCy7bY2CI6oMH5HfsCyp0WND4H4G-ya339AfjgurScHcO076QFFdvvLR7OgIPAgJSUZkHpSbrJ5BZrHBcAIE1g6jmVFIMm'; //$auth_sess->getDeviceToken($rest->create_user_id);

    $auth_sess = new AuthSession();

    $device_token =  $auth_sess->getDeviceToken($user_id);
  
    $title = $title;

    $body = $body;

    $url = isset($url) ? $url : '';

    $img_url = isset($image_url) ? $image_url : '';

    $msg = array

        (

        'body' => $body,

        'title' => $title,

        "id" => "",

        "url" => $url,

        "img_url" => $img_url,

        // 'request_id' =>  $id,

    );

    $msg1 = array

        (

        'body' => $body,

        'title' => $title,

        'id' => '',

        'url' => $url,

        // 'request_id' =>  $id,

    );

    $fields = array

        (

        'to' => $device_token,

        'collapse_key' => 'type_a',

        //   'notification' => $msg1,

        'data' => $msg,

    );

    /*$headers = array

        (

        'Authorization: key=' . $api_key,

        'Content-Type: application/json',

    );*/
    
    
    $message = [
	 'token' => $device_token,
	 'notification' => [
	 'title' => 'Hello',
	 'body' => 'World',
	 ],
	 'data' => [
	      'story_id': 'story_12345'
	    ],
	];
	    
    $serviceAccountPath = 'service-account-file.json';
    $accessToken = $this->getAccessToken($serviceAccountPath);
    
    
    $headers = [
   'Authorization: Bearer ' . $accessToken,
   'Content-Type: application/json',
   ];

    $ch = curl_init();

    // curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
     curl_setopt($ch, CURLOPT_URL, 'https://firebase.google.com/docs/cloud-messaging/migrate-v1');


    curl_setopt($ch, CURLOPT_POST, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

    $result = curl_exec($ch);

    curl_close($ch);

    return $result;

}

function getAccessToken($serviceAccountPath) {
   $client = new Client();
   $client->setAuthConfig($serviceAccountPath);
   $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
   $client->useApplicationDefaultCredentials();
   $token = $client->fetchAccessTokenWithAssertion();
   return $token['access_token'];
}




}
