<?php

namespace app\modules\messaging\services;

use Yii;
use yii\base\Component;
use app\modules\messaging\services\PushNotifier;
use app\models\User;
use app\modules\admin\models\AuthSession;
use app\modules\admin\models\FcmNotification;

/**
 * Push notifications to app users. The provider (OneSignal/Expo) lives in PushNotifier; this service decides
 * who gets what and keeps the in-app notification list (fcm_notification table) in step.
 */
class PushService extends Component
{
    // [doc:start] notifyUser - Notifies a customer: push to their device and an entry in their in-app notification list.
    /**
     * Notifies a customer: push to their device and an entry in their in-app notification list.
     *
     * @param mixed $refId order / ride id the notification is about ('' when none)
     */
    public function notifyUser($refId, $userId, $title, $body)
    {
        // Partners (drivers, taxi drivers) are this service's own people. Anybody else is a customer, and customers
        // belong to the user-app system: when it is a separate system it delivers the message (its own notifications).
        if (\app\modules\customerapi\services\CustomerClient::isRemote() && !$this->isLocalPartner($userId)) {
            return \app\modules\customerapi\services\CustomerClient::notify($userId, $title, $body, $refId);
        }

        return $this->deliverToCustomer($refId, $userId, $title, $body);
    }
    // [doc:end] notifyUser

    // [doc:start] deliverToCustomer - The delivery itself: push to the person's device and an entry in their in-app list. Used by notifyUser for local.
    /**
     * The delivery itself: push to the person's device and an entry in their in-app list. Used by notifyUser for local
     * people and by the user-app gateway to deliver a customer's notification.
     */
    public function deliverToCustomer($refId, $userId, $title, $body)
    {
        $result = $this->sendToUser($userId, $title, $body, $refId);
        $this->storeInAppNotification($userId, $title, $body);

        return $result;
    }
    // [doc:end] deliverToCustomer

    private function isLocalPartner($userId)
    {
        $role = (new \yii\db\Query())->select('user_role')->from('user')->where(['id' => (int) $userId])->scalar();

        return in_array($role, ['Driver', 'taxi'], true);
    }

    // [doc:start] notifyDriver - Notifies a partner (taxi driver). $type lets the app react, for example "newOrder" rings the ringtone.
    /**
     * Notifies a partner (taxi driver). $type lets the app react, for example "newOrder" rings the ringtone.
     */
    public function notifyDriver($refId, $userId, $title, $body, $type = '')
    {
        return $this->sendToUser($userId, $title, $body, $refId, $type);
    }
    // [doc:end] notifyDriver

    // [doc:start] notifyVendor - Notifies a vendor/manager: push plus in-app entry.
    /**
     * Notifies a vendor/manager: push plus in-app entry.
     */
    public function notifyVendor($refId, $userId, $title, $body)
    {
        $result = $this->sendToUser($userId, $title, $body, $refId);
        $this->storeInAppNotification($userId, $title, $body);

        return $result;
    }
    // [doc:end] notifyVendor

    // [doc:start] sendToUser - Send To User
    /**
     * @return string|false provider response, or false when the user has no registered device
     */
    private function sendToUser($userId, $title, $body, $refId = '', $type = '')
    {
        $deviceToken = (new AuthSession())->getDeviceToken($userId);
        if (empty($deviceToken)) {
            return false;
        }

        // Customers and partners use separate OneSignal apps when configured.
        $role = User::find()->select('user_role')->where(['id' => $userId])->scalar();
        $app = in_array($role, [User::ROLE_DRIVER, User::ROLE_TAXI], true) ? 'driver' : 'user';

        return PushNotifier::send($deviceToken, $title, strip_tags((string) $body), [
            'order_id' => $refId,
            'type' => $type,
        ], $app);
    }
    // [doc:end] sendToUser

    private function storeInAppNotification($userId, $title, $body)
    {
        $entry = new FcmNotification();
        $entry->user_id = $userId;
        $entry->title = $title;
        $entry->description = $body;
        $entry->is_read = 1;
        $entry->status = FcmNotification::STATUS_ACTIVE;
        $entry->save(false);
    }
}