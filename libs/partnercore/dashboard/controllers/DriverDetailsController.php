<?php

namespace app\modules\admin\controllers;

use Yii;
use app\models\User;
use app\modules\admin\models\DriverDetails;
use app\modules\admin\models\search\DriverDetailsSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * DriverDetailsController implements the CRUD actions for DriverDetails model.
 */
class DriverDetailsController extends Controller
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
                        'actions' => ['index', 'view', 'create', 'update', 'delete', 'update-status', 'update-verify-status'],
                        'matchCallback' => function () {
                            return User::isAdmin();
                        }

                    ],
                    [
                        'allow' => true,
                        'actions' => ['index', 'view', 'create', 'update', 'delete', 'update-status', 'update-verify-status'],
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

    // [doc:start] actionIndex - /admin/driver-details/index - Lists all DriverDetails models.
    /**
     * Lists all DriverDetails models.
     * @return mixed
     */
    public function actionIndex()
    {
        $vehicle_id = Yii::$app->request->get('vehicle_id');
        $searchModel = new DriverDetailsSearch();
        
        // print_r(Yii::$app->user->identity->user_role );exit;
        
        if (\Yii::$app->user->identity->user_role == User::ROLE_ADMIN || \Yii::$app->user->identity->user_role == User::ROLE_SUBADMIN) {
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams,$vehicle_id);
        } else if (\Yii::$app->user->identity->user_role == User::ROLE_MANAGER) {
            $dataProvider = $searchModel->managersearch(Yii::$app->request->queryParams);
        } else if (\Yii::$app->user->identity->user_role == User::ROLE_FRANCHISE) {
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams,$vehicle_id,\Yii::$app->user->identity->city_id);
        }
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    // [doc:end] actionIndex

    

    // [doc:start] actionView - /admin/driver-details/view - Displays a single DriverDetails model.
    /**
     * Displays a single DriverDetails model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }
    // [doc:end] actionView

    // [doc:start] actionCreate - /admin/driver-details/create - Creates a new DriverDetails model.
    /**
     * Creates a new DriverDetails model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new DriverDetails();

        if ($model->loadAll(Yii::$app->request->post()) && $model->saveAll()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }
    // [doc:end] actionCreate

    // [doc:start] actionUpdate - /admin/driver-details/update - Updates an existing DriverDetails model.
    /**
     * Updates an existing DriverDetails model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    { 
$post = Yii::$app->request->post();
        // var_dump($post['DriverDetails']['first_name']);exit;
            $model = $this->findModel($id);

            $proof_of_license = $model->proof_of_license;
            $proof_of_license_back = $model->proof_of_license_back;
            $rc_proof = $model->rc_proof;
            $rc_proof_back = $model->rc_proof_back;
            $adhaar_front = $model->adhaar_front;
            $adhaar_back = $model->adhaar_back;
            $adhaar_back = $model->adhaar_back;
            $pan_front = $model->pan_front;
            $chassis_image = $model->chassis_image;

            if ($model->loadAll(Yii::$app->request->post())) {

                $upload_image_license_front = \yii\web\UploadedFile::getInstance($model, 'proof_of_license');

                if (!empty($upload_image_license_front)) {
                    $image = \app\modules\filestorage\services\MediaStore::saveUploadedFile($upload_image_license_front);
                    $model->proof_of_license = $image['url'];
                } else {
                    $model->proof_of_license = $proof_of_license;
                }


                $upload_image_license_back = \yii\web\UploadedFile::getInstance($model, 'proof_of_license_back');
                if (!empty($upload_image_license_back)) {
                    $image = \app\modules\filestorage\services\MediaStore::saveUploadedFile($upload_image_license_back);
                    $model->proof_of_license_back = $image['url'];
                } else {
                    $model->proof_of_license_back = $proof_of_license_back;
                }


                $upload_image_rc_proof_front = \yii\web\UploadedFile::getInstance($model, 'rc_proof');
                if (!empty($upload_image_rc_proof_front)) {
                    $image = \app\modules\filestorage\services\MediaStore::saveUploadedFile($upload_image_rc_proof_front);
                    $model->rc_proof = $image['url'];
                } else {
                    $model->rc_proof = $rc_proof;
                }
                $upload_image_rc_proof_back = \yii\web\UploadedFile::getInstance($model, 'rc_proof_back');
                if (!empty($upload_image_rc_proof_back)) {
                    $image = \app\modules\filestorage\services\MediaStore::saveUploadedFile($upload_image_rc_proof_back);
                    $model->rc_proof_back = $image['url'];
                } else {
                    $model->rc_proof_back = $rc_proof_back;
                }

                $upload_image_adhaar_front = \yii\web\UploadedFile::getInstance($model, 'adhaar_front');
                if (!empty($upload_image_adhaar_front)) {
                    $image = \app\modules\filestorage\services\MediaStore::saveUploadedFile($upload_image_adhaar_front);
                    $model->adhaar_front = $image['url'];
                } else {
                    $model->adhaar_front = $adhaar_front;
                }
                $upload_image_adhaar_back = \yii\web\UploadedFile::getInstance($model, 'adhaar_back');
                if (!empty($upload_image_adhaar_back)) {
                    $image = \app\modules\filestorage\services\MediaStore::saveUploadedFile($upload_image_adhaar_back);
                    $model->adhaar_back = $image['url'];
                } else {
                    $model->adhaar_back = $adhaar_back;
                }

                $upload_image_pan_front = \yii\web\UploadedFile::getInstance($model, 'pan_front');
                if (!empty($upload_image_pan_front)) {
                    $image = \app\modules\filestorage\services\MediaStore::saveUploadedFile($upload_image_pan_front);
                    $model->pan_front = $image['url'];
                } else {
                    $model->pan_front = $pan_front;
                }
                $chassis_image_url = \yii\web\UploadedFile::getInstance($model, 'chassis_image');
                if (!empty($upload_image_pan_front)) {
                    $image = \app\modules\filestorage\services\MediaStore::saveUploadedFile($chassis_image_url);
                    $model->chassis_image = $image['url'];
                } else {
                    $model->chassis_image = $chassis_image;
                }
                $user = User::find()->where(['id'=>$model->user_id])->one();
                if(!empty($user)){
                    $user->first_name = $post['DriverDetails']['first_name'];
                    $user->last_name = $post['DriverDetails']['last_name'];
                    $user->email = $post['DriverDetails']['email'];
                    $user->gender = $post['DriverDetails']['gender'];
                    $user->date_of_birth = $post['DriverDetails']['date_of_birth'];
                    $user->save(false);
                }

                $model->save(false);

                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                return $this->render('update', [
                    'model' => $model,
                ]);
            }
        
    }
    // [doc:end] actionUpdate

    // [doc:start] actionDelete - /admin/driver-details/delete - Deletes an existing DriverDetails model.
    /**
     * Deletes an existing DriverDetails model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {

        $model = $this->findModel($id);
        if (!empty($model)) {
            $model->status = DriverDetails::STATUS_DELETE;
            $model->save(false);
        }

        return $this->redirect(['index']);
    }
    // [doc:end] actionDelete

    // [doc:start] actionUpdateStatus - /admin/driver-details/update-status - Update Status
    public function actionUpdateStatus()
    {
        $data = [];
        $post = \Yii::$app->request->post();
        \Yii::$app->response->format = 'json';
        if (!empty($post['id'])) {
            $model = DriverDetails::find()->where([
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
     * Finds the DriverDetails model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return DriverDetails the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = DriverDetails::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
        }
    }

    // [doc:start] actionUpdateVerifyStatus - /admin/driver-details/update-verify-status - Update Verify Status
    public function actionUpdateVerifyStatus()
    {
        $post = Yii::$app->request->post();
        $id = (int)$post['id'];
        $status = (int)$post['val'];
        $rejection_reason = $post['rejectionReason'];
        $driverDetail = DriverDetails::find()->where(['id' => $id])->one();
        if (!empty($driverDetail)) {
            $driverDetail->is_verified = $status;
            $driverDetail->rejection_reason = $rejection_reason;
            if ($driverDetail->save(false)) {
                return "Data Saved";
            }
        }
    }
    // [doc:end] actionUpdateVerifyStatus
}
