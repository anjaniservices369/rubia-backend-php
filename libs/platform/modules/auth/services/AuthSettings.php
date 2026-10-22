<?php

namespace app\modules\auth\services;
use Yii;
use yii\base\Component;
use app\models\AuthSession;

class AuthSettings extends Component {

// [doc:start] getAuthSession - Get Auth Session
public function getAuthSession($auth_code){

    // A call forwarded by the user-app gateway carries a signed customer id: the customer is already
    // authenticated by the user-app system, this service never holds their login.
    $forwarded = \app\modules\customerapi\services\ServiceSignature::forwardedUserId();
    if ($forwarded !== null) {
        return $forwarded;
    }

    $auth_session = AuthSession::find()->where(['auth_code'=>$auth_code])->one();
    if(!empty($auth_session)){
        $user = $auth_session->createUser;
        if(!empty($user) && (int) $user->status === \app\models\User::STATUS_ACTIVE){ // blocked, inactive or deleted accounts lose API access at once
            $user_id = $user->id;

            return $user_id;
        }
    }

    // Not a login this service knows: when the user app is a separate system, a customer's code is theirs to check.
    $CG = '\app\modules\customerapi\services\CustomerClient';
    if ($auth_code !== '' && $auth_code !== null && $CG::isRemote()) {
        $customer = $CG::authenticate($auth_code);
        if ($customer) {
            return (int) $customer['id'];
        }
    }
}
// [doc:end] getAuthSession

// [doc:start] UserNotification - User Notification
public function UserNotification($user_id, $title, $body, $url = '', $image_url = '')
{
    $auth_sess = new AuthSession();
    $device_token = $auth_sess->getDeviceToken($user_id);

    return \app\modules\messaging\services\PushNotifier::send($device_token, $title, $body, [
        'url' => $url,
        'img_url' => $image_url,
    ], \app\modules\messaging\services\PushNotifier::appFor($user_id));
}
// [doc:end] UserNotification

}
