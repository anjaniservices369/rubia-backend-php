<?php
namespace app\components;
use app\models\User;
use yii\filters\AccessControl;

class BaseController extends \yii\web\Controller
{
    public $seoParams = [];

    public $pageTitle = 'News';

    public $metaDescription = '';

    public $metaKeywords = '';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => [
                            'bulk-delete'
                        ],
                        'allow' => true,
                        'roles' => [
                            '@'
                        ]
                    ]
                ]
            ]
        ];
    }

    public function beforeAction($action)
    {
        $this->layout = 'main';
        if (! \Yii::$app->user->isGuest) {
            //if (User::isAdmin()  ||  \Yii::$app->user->identity->user_role==User::ROLE_MERCHANT) {
            if (User::isAdmin()) {
                $this->layout = 'main';
            } elseif (User::isUser()) {
                // $this->layout = 'user-main';
                $this->layout = 'main';
            }
            if (\Yii::$app->controller->id == 'user' && \Yii::$app->controller->action->id == 'logout') {
                $user = \Yii::$app->user->identity;
                //$user->scenario = 'last-login';
               // $user->last_login = date('Y-m-d h:i:s');
                $user->save();
            }
        } else if ((\Yii::$app->controller->action->id != 'login')) {
            if (! User::isUser()) {
                if (\Yii::$app->hasModule('comingsoon') && COMMING_SOON == true) {
                    return $this->redirect([
                        '/comingsoon'
                    ]);
                }
            }
        }
        
        return parent::beforeAction($action);
    }

    public function renderSeo($params = [])
    {
        if (array_key_exists('title', $this->seoParams)) {
            \Yii::$app->view->title = $this->seoParams['title'];
        } else {
            \Yii::$app->view->title = array_key_exists('model', $params) && isset($params->title) ? $params->title . ' | ' . \Yii::$app->name : \Yii::$app->controller->action->id . ' | ' . \Yii::$app->name;
        }
        
        \Yii::$app->view->registerMetaTag([
            'name' => 'description',
            'content' => array_key_exists('description', $this->seoParams) ? $this->seoParams['description'] : $this->metaDescription
        ]);
        
        \Yii::$app->view->registerMetaTag([
            'name' => 'keywords',
            'content' => array_key_exists('keywords', $this->seoParams) ? $this->seoParams['keywords'] : $this->metaKeywords
        ]);
    }

    public function render($view, $params = [])
    {
        $this->renderSeo($params);
        return parent::render($view, $params);
    }

    public function actionBulkDelete()
    {
        \Yii::$app->response->format = 'json';
        $response = [
            'status' => STATUS_FAILURE
        ];
        $data = \Yii::$app->request->post('data', '');
        if (is_array($data)) {
            foreach ($data as $id) {
                $model = $this->findModel($id);
                $model->delete();
            }
            $response['status'] = STATUS_SUCCESS;
        }
        return $response;
    }
}
