<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\forms\LoginForm;
use app\forms\ContactForm;
use app\forms\PasswordRequestForm;
use app\forms\PasswordUpdateForm;
use app\models\User;
use app\modules\admin\models\Page;
use yii\data\ActiveDataProvider;
use yii\widgets\ActiveForm;
use app\modules\admin\models\WebSetting;
use app\modules\admin\forms\UserForm;
use app\modules\admin\models\Notification;
use app\modules\admin\models\UserSearch;
use app\modules\admin\models\EmailTemplate;
use yii\web\UploadedFile;
use app\components\AuthHandler;
use app\modules\admin\models\Auth;

class SiteController extends Controller
{
	public function behaviors() {
		return [ 
			'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => [
                            'error',
							'index',
							'home',
							'contact',
							'about',
							'account-deletion',
							'captcha',
							'login',
							'admin-login',
							'logout',
							'password-request',
							'reset-password',
                        ],
                        'allow' => true,
                        'roles' => [
                            '?',
                        ],
                    ],
                    [
                        'actions' => [
                            'error',
							'index',
							'home',
							'contact',
							'about',
							'account-deletion',
							'captcha',
							'login',
							'logout',

                        ],
                        'allow' => true,
                        'roles' => [
                            '@',
                        ],
                    ],

                ],
                /*'denyCallback' => function ($rule, $action) {
            throw new \Exception('Sorry Page Not Found');
            },*/
            ],
				'verbs' => [ 
						'class' => VerbFilter::className (),
						'actions' => [ 
								'logout' => [ 
										'post' 
								] 
						] 
				] 
		];
    }
    /**
	 * Displays Errors.
	 *
	 * @return string
	 */
	public function actionError()
    {
        $app = Yii::app();
        if ($error = $app->errorHandler->error->code) {
            if ($app->request->isAjaxRequest) {
                echo $error['message'];
            } else {
                //$this->layout = 'doRedirect';
                $this->render('error' . ($this->getViewFile('error' . $error) ? $error : ''), $error);
            }
        }
    }

	/**
	 * @inheritdoc
	 */
	public function actions()
	{
		return [
			'error'   => [
				'class' => 'yii\web\ErrorAction',
			],
			'captcha' => [
				'class'           => 'yii\captcha\CaptchaAction',
				'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
		];
	}

	/**
	 * Displays homepage.
	 *
	 * @return string
	 */
	public function actionIndex()
	{
		if (!User::isAdmin()) {
			if(User::isFranchise())
			{
				return $this->redirect(['/admin/dashboard']);
			}
		return $this->render('index');
		}else{
			return $this->redirect(['/admin/dashboard']);
		}
	}

	/**
	 * Public "delete my account" page (the link given to Google Play in the Data safety form). Records the request; support confirms it
	 * by phone and processes it within 30 days. Signed-in app users can delete at once inside the app.
	 */
	public function actionAccountDeletion()
	{
		$this->layout = false;
		$done = '';
		$error = '';
		$req = Yii::$app->request;
		if ($req->isPost) {
			$contact = preg_replace('/\D/', '', (string) $req->post('contact'));
			$name = trim((string) $req->post('name'));
			$role = $req->post('role') === 'Driver' ? 'Driver' : 'User';
			if (strlen($contact) !== 10 || $name === '') {
				$error = 'Please enter your name and your 10 digit registered mobile number.';
			} else {
				\app\components\AccountDeletion::logWebRequest($contact, $name, $role, (string) $req->post('reason'));
				$done = 'Your request is received. We will call you on ' . $contact . ' to confirm and delete the account within 30 days.';
			}
		}

		return $this->render('account-deletion', ['done' => $done, 'error' => $error]);
	}

	/**
	 * Displays contact page.
	 *
	 * @return Response|string
	 */
	public function actionContact()
	{
		$model = new ContactForm();
		if ($model->load(Yii::$app->request->post()) && $model->contact()) {
			Yii::$app->session->setFlash('contactFormSubmitted');

			return $this->refresh();
		}

		return $this->render('contact', [
			'model' => $model,
		]);
	}

	/**
	 * Displays about page.
	 *
	 * @return string
	 */
	public function actionAbout()
	{
		$model = Page::find()->where(['type_id' => Page::TYPE_ABOUT])->one();
		return $this->render('about-us',['model' => $model]);
    }

  
	

	/**
	 * Logout action.
	 *
	 * @return Response
	 */
	public function actionLogout()
	{
		Yii::$app->user->logout();

		return $this->goHome();
	}

	/**
	 * Forgot-password request: emails a reset link if the address belongs to an account that can sign in
	 * with a password at all (so never for riders/drivers, who use the mobile apps' OTP login instead).
	 * The same confirmation message is shown regardless of whether an email was actually sent, so this page
	 * cannot be used to find out which addresses are registered.
	 */
	public function actionPasswordRequest()
	{
		if (!Yii::$app->user->isGuest) {
			return $this->goHome();
		}

		$model = new PasswordRequestForm();
		if ($model->load(Yii::$app->request->post()) && $model->request()) {
			Yii::$app->session->setFlash('success', 'If that email is registered, a reset link has been sent to it.');
			return $this->redirect(['site/login']);
		}

		return $this->render('password-request', ['model' => $model]);
	}

	/**
	 * Sets a new password from the link in the e-mail sent by actionPasswordRequest. The token is only valid
	 * for 1 hour (app\models\User::isPasswordResetTokenValid) and is consumed (cleared) once used.
	 */
	public function actionResetPassword($token)
	{
		if (!User::findByPasswordResetToken($token)) {
			Yii::$app->session->setFlash('error', 'This reset link is invalid or has expired. Please request a new one.');
			return $this->redirect(['site/password-request']);
		}

		$model = new PasswordUpdateForm(['resetToken' => $token]);
		if ($model->load(Yii::$app->request->post()) && $model->update()) {
			Yii::$app->session->setFlash('success', 'Your password has been changed. You can now sign in.');
			return $this->redirect(['site/login']);
		}

		return $this->render('reset-password', ['model' => $model]);
	}

	public function actionLogin(){
		
		if (!Yii::$app->user->isGuest) {
            return $this->redirect('index');
        }
		$model = new LoginForm ();
		if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post())) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return ActiveForm::validate($model);
            \Yii::$app->end();

        }
		if ($model->load ( Yii::$app->request->post () ) ) {
						//var_dump( $user_verification); exit;
			//if($user_verification['status'] == User::STATUS_ACTIVE){
				if($model->login()){
					
					if(\Yii::$app->user->identity->user_role==User::ROLE_ADMIN || \Yii::$app->user->identity->user_role==User::ROLE_FRANCHISE ){
						return $this->redirect(['/admin/dashboard']);
					}
					//var_dump($model); exit;
					
					return $this->goBack();
				}
			/*}else{
                Yii::$app->session->setFlash('error','Please Activate Your  Account');
            }*/

		}
		return $this->render('login',['model' => $model]);
	}



	public function actionAdminLogin() {
		//$this->layout = '//main-login';
		if (! Yii::$app->user->isGuest) {
			return $this->goHome ();
		}
		
		$model = new LoginForm ();
		$model->scenario = 'admin';
		if ($model->load ( Yii::$app->request->post () ) ) {
				if($model->login ()){
					if(\Yii::$app->user->identity->user_role==User::ROLE_ADMIN){
						return $this->redirect(['/admin/dashboard']);
					}
					return $this->goBack();
				}
		}
		return $this->render ( '/site/admin-login', [ 
				'model' => $model 
		] );
	}

	

}
