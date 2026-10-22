<?php

namespace app\modules\admin\controllers;

use Yii;
use app\models\User;
use app\modules\admin\models\Vehicals;
use app\modules\admin\models\search\VehicalsSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * VehicalsController implements the CRUD actions for Vehicals model.
 */
class VehicalsController extends Controller
{
    // [doc:start] behaviors - Behaviors
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
            'access' => [
                'class' => \yii\filters\AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index', 'view', 'create', 'update', 'delete', 'update-status', 'add-pincode'],
                        'matchCallback' => function () {
                            return User::isAdmin();
                        }

                    ],
                    [
                        'allow' => true,
                        'actions' => ['index', 'view','create', 'update', 'pdf', 'update-status'],
                        'matchCallback' => function () {
                            return User::isManager();
                        }
                    ],
                    [
                        'allow' => false
                    ]
                ]
            ]
        ];
    }
    // [doc:end] behaviors

    // [doc:start] actionIndex - /admin/vehicals/index - Lists all Vehicals models.
    /**
     * Lists all Vehicals models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new VehicalsSearch();
        if (\Yii::$app->user->identity->user_role == User::ROLE_ADMIN || \Yii::$app->user->identity->user_role == User::ROLE_SUBADMIN) {
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        } else if (\Yii::$app->user->identity->user_role == User::ROLE_MANAGER) {
            $dataProvider = $searchModel->managersearch(Yii::$app->request->queryParams);
        }
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    // [doc:end] actionIndex

    // [doc:start] actionView - /admin/vehicals/view - Displays a single Vehicals model.
    /**
     * Displays a single Vehicals model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $providerPincode = new \yii\data\ArrayDataProvider([
            'allModels' => $model->pincodes,
        ]);
        return $this->render('view', [
            'model' => $this->findModel($id),
            'providerPincode' => $providerPincode,
        ]);
    }
    // [doc:end] actionView

    // [doc:start] actionCreate - /admin/vehicals/create - Creates a new Vehicals model.
    /**
     * Creates a new Vehicals model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Vehicals();

        if ($model->loadAll(Yii::$app->request->post())) {
            $upload_image = \yii\web\UploadedFile::getInstance($model, 'image');
            //   var_dump($image_path);exit;
            $image = \app\modules\filestorage\services\MediaStore::saveUploadedFile($upload_image);

            // var_dump($image['url']);
            // exit;
            //Add servicetype by service id

            $model->image = $image['url'];


            if ($model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                print_r($model->getErrors());
                exit();
            }
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }
    // [doc:end] actionCreate

    // [doc:start] actionUpdate - /admin/vehicals/update - Updates an existing Vehicals model.
    /**
     * Updates an existing Vehicals model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $oldImage = $model['image'];

        if ($model->loadAll(Yii::$app->request->post())) {
            $upload_image = \yii\web\UploadedFile::getInstance($model, 'image');
            if (!empty($upload_image)) {
                $image = \app\modules\filestorage\services\MediaStore::saveUploadedFile($upload_image);

                $model->image = $image['url'];
            } else {
                $model->image = $oldImage;
            }

            if ($model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                echo error_log('not uploaded');
            }
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }
    // [doc:end] actionUpdate

    // [doc:start] actionDelete - /admin/vehicals/delete - Deletes an existing Vehicals model.
    /**
     * Deletes an existing Vehicals model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {

        $model = $this->findModel($id);
        if (!empty($model)) {
            $model->status = Vehicals::STATUS_DELETE;
            $model->save(false);
        }

        return $this->redirect(['index']);
    }
    // [doc:end] actionDelete

    // [doc:start] actionUpdateStatus - /admin/vehicals/update-status - Update Status
    public function actionUpdateStatus()
    {
        $data = [];
        $post = \Yii::$app->request->post();
        \Yii::$app->response->format = 'json';
        if (!empty($post['id'])) {
            $model = Vehicals::find()->where([
                'id' => $post['id'],
            ])->one();
            if (!empty($model)) {

                $model->status = $post['val'];
            }
            if ($model->save(false)) {
                $data['message'] = "Updated";
                $data['id'] = $model->status;
            } else {
                $data['message'] = "Not Updated";
            }
        }
        return $data;
    }
    // [doc:end] actionUpdateStatus


    /**
     * Finds the Vehicals model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Vehicals the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Vehicals::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
        }
    }

    // [doc:start] actionAddPincode - /admin/vehicals/add-pincode - Action to load a tabular form grid.
    /**
     * Action to load a tabular form grid
     * for Pincode
     * @author Yohanes Candrajaya <moo.tensai@gmail.com>
     * @author Jiwantoro Ndaru <jiwanndaru@gmail.com>
     *
     * @return mixed
     */
    public function actionAddPincode()
    {
        if (Yii::$app->request->isAjax) {
            $row = Yii::$app->request->post('Pincode');
            if (!empty($row)) {
                $row = array_values($row);
            }
            if ((Yii::$app->request->post('isNewRecord') && Yii::$app->request->post('_action') == 'load' && empty($row)) || Yii::$app->request->post('_action') == 'add')
                $row[] = [];
            return $this->renderAjax('_formPincode', ['row' => $row]);
        } else {
            throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
        }
    }
    // [doc:end] actionAddPincode
}
