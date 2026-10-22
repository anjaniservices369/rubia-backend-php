<?php

namespace app\components;

use app\modules\customerapi\services\CustomerClient;
use Yii;
use yii\web\IdentityInterface;
use yii\web\User;

/**
 * The application's `user` component.
 *
 * Behaves exactly like yii\web\User. The one addition is for a service that is separate from the user app: a customer's
 * request carries an `auth_code` the service does not know, the user app does. When there is no local identity and the
 * user app is a separate system, the code is checked through the gateway and the customer becomes the identity of the
 * request (id, uuid, name and phone only: `ApiIdentity`). Everything that records "who did this" (create_user_id, ...)
 * then works unchanged, without a customer row in the service's database.
 */
class ApiUser extends User
{
    private $apiIdentity = false;

    // [doc:start] getIdentity - Get Identity
    public function getIdentity($autoRenew = true)
    {
        $identity = parent::getIdentity($autoRenew);
        if ($identity !== null || Yii::$app->request instanceof \yii\console\Request || !CustomerClient::isRemote()) {
            return $identity;
        }
        if ($this->apiIdentity === false) {
            $this->apiIdentity = null;
            $code = Yii::$app->request->headers->get('auth_code') ?: Yii::$app->request->get('auth_code');
            // a partner's code is known here: only an unknown code is the user app's customer
            if ($code && !(new \yii\db\Query())->from('auth_session')->where(['auth_code' => (string) $code])->exists()) {
                $profile = CustomerClient::authenticate((string) $code);
                if ($profile) {
                    $this->apiIdentity = new ApiIdentity($profile);
                }
            }
        }

        return $this->apiIdentity;
    }
    // [doc:end] getIdentity
}

/**
 * A customer known only through the user app. Never stored, never logged in to a session.
 */
class ApiIdentity implements IdentityInterface
{
    public $id;
    public $uuid;
    public $first_name;
    public $last_name;
    public $contact_no;
    public $username;
    public $user_role = 'User';
    public $status = 10;

    public function __construct(array $profile)
    {
        $this->id = (int) $profile['id'];
        $this->uuid = $profile['uuid'] ?? '';
        $this->first_name = $profile['first_name'] ?? ($profile['name'] ?? '');
        $this->last_name = $profile['last_name'] ?? '';
        $this->contact_no = $profile['phone'] ?? '';
        $this->username = $profile['username'] ?? $this->contact_no;
    }

    public static function findIdentity($id)
    {
        return null;
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return null;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAuthKey()
    {
        return null;
    }

    public function validateAuthKey($authKey)
    {
        return false;
    }
}
