<?php

namespace app\modules\admin\controllers;



use Yii;
use app\modules\admin\models\base\DriverCancelDuplicate;
use app\modules\admin\models\search\DriverCancelDuplicateSearch;
use app\modules\admin\models\Booking;

use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * DriverCancelDuplicateController implements the CRUD actions for DriverCancelDuplicate model.
 */
class DriverCancelDuplicateController extends Controller
{
    // Similar behavior and access rules as in BookingController

    // [doc:start] actionIndex - /admin/driver-cancel-duplicate/index - Lists all DriverCancelDuplicate models.
    /**
     * Lists all DriverCancelDuplicate models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new DriverCancelDuplicateSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
    
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    // [doc:end] actionIndex

    

    // [doc:start] actionView - /admin/driver-cancel-duplicate/view - View
    public function actionView($id)
    {
        $model = $this->findModel($id);

        $providerDriverRequests = new \yii\data\ArrayDataProvider([
            'allModels' => $model->driverRequests,
        ]);

        $providerVehicleVerification = new \yii\data\ArrayDataProvider([
            'allModels' => $model->vehicleVerifications,
        ]);
        return $this->render('_invoice_view', [
            'model' => $this->findModel($id),

            'providerDriverRequests' => $providerDriverRequests,

            'providerVehicleVerification' => $providerVehicleVerification,
        ]);
    }
    // [doc:end] actionView

    // [doc:start] actionCreate - /admin/driver-cancel-duplicate/create - Creates a new Booking model.
    /**
     * Creates a new Booking model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Booking();

        if ($model->loadAll(Yii::$app->request->post()) && $model->saveAll()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }
    // [doc:end] actionCreate

    // [doc:start] actionUpdate - /admin/driver-cancel-duplicate/update - Updates an existing Booking model.
    /**
     * Updates an existing Booking model.
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

    // [doc:start] actionDelete - /admin/driver-cancel-duplicate/delete - Deletes an existing Booking model.
    /**
     * Deletes an existing Booking model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
    

        return $this->redirect(['index']);
    }
    // [doc:end] actionDelete

    // [doc:start] actionUpdateStatus - /admin/driver-cancel-duplicate/update-status - Update Status
    public function actionUpdateStatus()
    {
        $data = [];
        $post = \Yii::$app->request->post();
        \Yii::$app->response->format = 'json';
        if (!empty($post['id'])) {
            $model = Booking::find()
                ->where([
                    'id' => $post['id'],
                ])
                ->one();
            if (!empty($model)) {
                $model->status = $post['val'];
            }
            if ($model->save(false)) {
                $data['message'] = 'Updated';
                $data['id'] = $model->status;
            } else {
                $data['message'] = 'Not Updated';
            }
        }
        return $data;
    }
    // [doc:end] actionUpdateStatus

    /**
     * Finds the Booking model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Booking the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    

    // Add other CRUD actions as needed

    /**
     * Finds the DriverCancelDuplicate model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return DriverCancelDuplicate the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = DriverCancelDuplicate::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
