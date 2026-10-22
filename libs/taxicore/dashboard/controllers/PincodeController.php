<?php

namespace app\modules\admin\controllers;

use Yii;
use app\models\User;
use app\modules\admin\models\Pincode;
use app\modules\admin\models\search\PincodeSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * PincodeController implements the CRUD actions for Pincode model.
 */
class PincodeController extends Controller
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
                        'actions' => ['index', 'view', 'create', 'update', 'delete', 'update-status'],
                        'matchCallback' => function () {
                            return User::isAdmin() || User::isSubAdmin();
                        }

                    ],
                    [
                        'allow' => true,
                        'actions' => ['index', 'view', 'update', 'pdf', 'update-status'],
                        'matchCallback' => function () {
                            return User::isManager();
                        }
                    ],
                    [
                        'allow' => true,
                        'actions' => ['index', 'view', 'create', 'update', 'delete', 'update-status'],
                        'matchCallback' => function () {
                            return User::isFranchise();
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

    // [doc:start] actionIndex - /admin/pincode/index - Lists all Pincode models.
    /**
     * Lists all Pincode models.
     * @return mixed
     */
    public function actionIndex()
    {
        $vehicle_id = Yii::$app->request->get('vehicle_id');
        $searchModel = new PincodeSearch();
        if (\Yii::$app->user->identity->user_role == User::ROLE_ADMIN || \Yii::$app->user->identity->user_role == User::ROLE_SUBADMIN) {
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams,$vehicle_id,'');
        } else if (\Yii::$app->user->identity->user_role == User::ROLE_MANAGER ) {
            $dataProvider = $searchModel->managersearch(Yii::$app->request->queryParams);
        }
        else if (\Yii::$app->user->identity->user_role == User::ROLE_FRANCHISE) {
            $dataProvider = $searchModel->searchManager(Yii::$app->request->queryParams,$vehicle_id,\Yii::$app->user->identity->city_id);
        }
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    // [doc:end] actionIndex

    // [doc:start] actionView - /admin/pincode/view - Displays a single Pincode model.
    /**
     * Displays a single Pincode model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        Pincode::find(['id' => $id])->one();
        return $this->render('view', [
            // 'model' => Pincode::find(['id' => $id])->one(),
            'model' => Pincode::find()->where(['id' => $id])->one(),
        ]);
    }
    // [doc:end] actionView

    // [doc:start] actionCreate - /admin/pincode/create - Creates a new Pincode model.
    /**
     * Creates a new Pincode model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Pincode();
        $post = Yii::$app->request->post();
        if (!empty($post)) {
            $postPincode = $post["Pincode"];
            $postVehicle = $postPincode["vehical_id"];

            // foreach ($postVehicle as $pv) {
                $model = new Pincode();
                if ($model->loadAll(Yii::$app->request->post())) {
                    $model->vehical_id = (int)$postVehicle;
                    // $model->vehical_id = (int)$pv;
                    $model->save(false);
                // }
            }
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
        if ($model->save(false)) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            print_r($model->getErrors());
            exit;
        }
    }
    // [doc:end] actionCreate

    // [doc:start] actionUpdate - /admin/pincode/update - Updates an existing Pincode model.
    /**
     * Updates an existing Pincode model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->loadAll(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }
    // [doc:end] actionUpdate

    // [doc:start] actionDelete - /admin/pincode/delete - Deletes an existing Pincode model.
    /**
     * Deletes an existing Pincode model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {

        $model = $this->findModel($id);
        if (!empty($model)) {
            $model->status = Pincode::STATUS_DELETE;
            $model->save(false);
        }

        return $this->redirect(['index']);
    }
    // [doc:end] actionDelete

    // [doc:start] actionUpdateStatus - /admin/pincode/update-status - Update Status
    public function actionUpdateStatus()
    {
        $data = [];
        $post = \Yii::$app->request->post();
        \Yii::$app->response->format = 'json';
        if (!empty($post['id'])) {
            $model = Pincode::find()->where([
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
     * Finds the Pincode model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Pincode the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Pincode::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
        }
    }
}
