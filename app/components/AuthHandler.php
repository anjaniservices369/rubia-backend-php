<?php
namespace app\components;

use app\models\Auth;
use app\models\User;
use Yii;
use yii\authclient\ClientInterface;
use yii\helpers\ArrayHelper;

/**
 * AuthHandler handles successful authentication via Yii auth component
 */
class AuthHandler
{
    /**
     * @var ClientInterface
     */
    private $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function handle()
    {
        $attributes = $this->client->getUserAttributes();


        if($this->client->getId()=='google'){
            $list=$this->getDataForGoogle($attributes);
        }else if($this->client->getId()=='twitter'){
            $list=$this->getDataForTwitter($attributes);

        }
        $email = $list['email'];
        $id = ArrayHelper::getValue($attributes, 'id');
        $nickname = $list['username'];
        $profile_image=$list['profile_image'];
        $full_name=$list['name'];

        /* @var Auth $auth */
        $auth = Auth::find()->where([
            'source' => $this->client->getId(),
            'source_id' => $id,
        ])->one();

        if (Yii::$app->user->isGuest) {
            if ($auth) { // login
                /* @var User $user */
                $user = $auth->user;

                Yii::$app->user->login($user);

            } else { // signup
                if ($email !== null && User::find()->where(['email' => $email])->exists()) {
                    Yii::$app->getSession()->setFlash('error', [
                        Yii::t('app', "User with the same email as in {client} account already exists but isn't linked to it. Login using email first to link it.", ['client' => $this->client->getTitle()]),
                    ]);
                } else {
                    $password = Yii::$app->security->generateRandomString(6);

                    $user = new User([
                        'username' => $nickname,
                        'full_name'=>$full_name,
                        'email' => $email,
                        'password' => $password,
                        'profile_image'=>$profile_image,
                        'role_id'=>User::ROLE_USER
                    ]);

                    $user->scenario='social';
//                    $user->generateAuthKey();
//                    $user->generatePasswordResetToken();

                    //$transaction = User::getDb()->beginTransaction();

                    if ($user->save()) {
                        $auth = new Auth([
                            'user_id' => $user->id,
                            'source' => $this->client->getId(),
                            'source_id' => (string)$id,
                        ]);
                        if ($auth->save()) {
                            //$transaction->commit();
                            Yii::$app->user->login($user);
                        } else {

                            Yii::$app->getSession()->setFlash('error', [
                                Yii::t('app', 'Unable to save {client} account: {errors}', [
                                    'client' => $this->client->getTitle(),
                                    'errors' => json_encode($auth->getErrors()),
                                ]),
                            ]);
                        }
                    } else {
                        Yii::$app->getSession()->setFlash('error', [
                            Yii::t('app', 'Unable to save user: {errors}', [
                                'client' => $this->client->getTitle(),
                                'errors' => json_encode($user->getErrors()),
                            ]),
                        ]);
                    }
                }
            }
        } else { // user already logged in
            if (!$auth) { // add auth provider
                $auth = new Auth([
                    'user_id' => Yii::$app->user->id,
                    'source' => $this->client->getId(),
                    'source_id' => (string)$attributes['id'],
                ]);
                if ($auth->save()) {
                    /** @var User $user */
                    $user = $auth->user;
                    Yii::$app->getSession()->setFlash('success', [
                        Yii::t('app', 'Linked {client} account.', [
                            'client' => $this->client->getTitle()
                        ]),
                    ]);
                } else {
                    Yii::$app->getSession()->setFlash('error', [
                        Yii::t('app', 'Unable to link {client} account: {errors}', [
                            'client' => $this->client->getTitle(),
                            'errors' => json_encode($auth->getErrors()),
                        ]),
                    ]);
                }
            } else { // there's existing auth
                Yii::$app->getSession()->setFlash('error', [
                    Yii::t('app',
                        'Unable to link {client} account. There is another user using it.',
                        ['client' => $this->client->getTitle()]),
                ]);
            }
        }
    }

    /**
     * @param User $user
     */

    public function getDataForGoogle($data){
        $list=[];
        $list['email']=$data['emails'][0]['value'];
        $list['username']=$data['id'];
        $list['profile_image']=$data['image']['url'];
        $list['name']=$data['displayName'];
        return $list;
   }


   public function getDataForFacebook($data){


   }

    public function getDataForTwitter($data){
        $list=[];
        $list['email']=$data['name'].'@twitter.com';
        $list['username']=$data['screen_name'];
        $list['profile_image']=$data['profile_image_url'];
        $list['name']=$data['name'];
        return $list;
    }

}