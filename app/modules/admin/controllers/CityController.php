<?php

namespace app\modules\admin\controllers;

use Yii;
use app\models\User;
use app\modules\admin\models\City;
use app\modules\admin\models\search\CitySearch;
use app\modules\admin\models\base\States;

use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;


/**
 * CityController implements the CRUD actions for City model.
 */
class CityController extends Controller
{
   
    // [doc:start] behaviors - Behaviors
    public function behaviors()
{
    return [
        'verbs' => [
            'class' => VerbFilter::className(),
            'actions' => [
                'delete' => ['post'],
                'pincode-area' => ['get', 'post'],
            ],
        ],
        'access' => [
            'class' => \yii\filters\AccessControl::className(),
            'rules' => [
                [
                    'allow' => true,
                    'actions' => ['index', 'view', 'create', 'update', 'delete', 'update-status', 'pincode-area'],
                    'matchCallback' => function () {
                        return User::isAdmin() || User::isSubAdmin();
                    },
                ],
                [
                    'allow' => true,
                    'actions' => ['index', 'view', 'update', 'pdf', 'update-status'],
                    'matchCallback' => function () {
                        return User::isManager();
                    },
                ],
                [
                    'allow' => true,
                    'actions' => ['index', 'delete'], // Include 'delete' action here
                    'roles' => ['@'], // Allow any authenticated user
                ],
                [
                    'allow' => true,
                    'actions' => ['deleted-cities'],
                    'matchCallback' => function () {
                        return User::isAdmin() || User::isSubAdmin() || User::isManager();
                    },
                ],
                [
                    'allow' => true,
                    'actions' => ['active-cities'],
                    'matchCallback' => function () {
                        return User::isAdmin() || User::isSubAdmin() || User::isManager();
                    },
                ],
                [
                    'allow' => true,
                    'actions' => ['districts'],
                    'roles' => ['@'], // Allow any authenticated user
                ],
                [
                    'allow' => false,
                ],
            ],
        ],
    ];
}
    // [doc:end] behaviors


    // [doc:start] actionIndex - /admin/city/index - Lists all City models.
    /**
     * Lists all City models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new CitySearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
    
        $statesDataProvider = new ActiveDataProvider([
            'query' => States::find()->orderBy(['id' => SORT_ASC]),
        ]);
    
        return $this->render('index', [
            'searchModel' => $searchModel,
            //'activeDataProvider' => $activeDataProvider,
           // 'deletedDataProvider' => $deletedDataProvider,
            'dataProvider' => $dataProvider,
            'statesDataProvider' => $statesDataProvider,
        ]);
    }
    // [doc:end] actionIndex

    
// [doc:start] actionDistricts - /admin/city/districts - Districts
public function actionDistricts($stateId)
{
    $state = States::findOne($stateId);

    if (!$state) {
        throw new NotFoundHttpException('State not found.');
    }

    $districtsDataProvider = new ActiveDataProvider([
        'query' => City::find()->where(['States_id' => $stateId])->orderBy(['id' => SORT_ASC]),
    ]);

    return $this->render('districts', [
        'state' => $state,
        'districtsDataProvider' => $districtsDataProvider,
    ]);
}
// [doc:end] actionDistricts


    // public function actionIndex()
    // {
    //     $searchModel = new CitySearch();
    //     $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
    
    //     //Separate active and deleted cities in the data provider
    //     $activeDataProvider = new ActiveDataProvider([
    //         'query' => City::find()->where(['status' => City::STATUS_ACTIVE])->orderBy([
    //             new Expression("CASE WHEN SUBSTRING(name, 1, 1) BETWEEN 'A' AND 'Z' THEN 0 ELSE 1 END"),
    //             'name' => SORT_ASC,
    //         ]),
    //     ]);
    
    //     $deletedDataProvider = new ActiveDataProvider([
    //         'query' => City::find()->where(['status' => City::STATUS_DELETE])->orderBy([
    //             new Expression("CASE WHEN SUBSTRING(name, 1, 1) BETWEEN 'A' AND 'Z' THEN 0 ELSE 1 END"),
    //             'name' => SORT_ASC,
    //         ]),
    //     ]);
    
    //     $activeDataProvider->query = City::find()->where(['status' => City::STATUS_ACTIVE])->andFilterWhere(['like', 'name', $searchModel->name])->orderBy([
    //         new Expression("CASE WHEN SUBSTRING(name, 1, 1) BETWEEN 'A' AND 'Z' THEN 0 ELSE 1 END"),
    //         'name' => SORT_ASC,
    //     ]);
        
    //     $deletedDataProvider->query = City::find()->where(['status' => City::STATUS_DELETE])->andFilterWhere(['like', 'name', $searchModel->name])->orderBy([
    //         new Expression("CASE WHEN SUBSTRING(name, 1, 1) BETWEEN 'A' AND 'Z' THEN 0 ELSE 1 END"),
    //         'name' => SORT_ASC,
    //     ]);
        
    
    //     return $this->render('index', [
    //         'searchModel' => $searchModel,
    //         'activeDataProvider' => $activeDataProvider,
    //         'deletedDataProvider' => $deletedDataProvider,
    //        'dataProvider' => $dataProvider,
    //     ]);
    // }
    

   


    // [doc:start] actionView - /admin/city/view - Displays a single City model.
    /**
     * Displays a single City model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $providerBooking = new \yii\data\ArrayDataProvider([
            'allModels' => $model->bookings,
        ]);
        $providerCityRateCard = new \yii\data\ArrayDataProvider([
            'allModels' => $model->cityRateCards,
        ]);
        return $this->render('view', [
            'model' => $this->findModel($id),
            'providerBooking' => $providerBooking,
            'providerCityRateCard' => $providerCityRateCard,
        ]);
    }
    // [doc:end] actionView

    /**
     * Creates a new City model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    // [doc:start] actionPincodeArea - /admin/city/pincode-area - Service area from pincodes: shows the city's pincodes on a map with the outline they make, and (POST) saves.
    /**
     * Service area from pincodes: shows the city's pincodes on a map with the outline they make, and (POST) saves
     * that outline as the city's service area (city.boundary).
     *
     * GET  /admin/city/pincode-area?id=8                   preview (pincodes are looked up on Google once, then kept)
     * POST /admin/city/pincode-area?id=8   refresh=1       look every pincode up again
     * POST /admin/city/pincode-area?id=8   save=1          use the outline as the service area
     * Admins and sub-admins only.
     */
    public function actionPincodeArea($id)
    {
        // Maps are per service module now; this old address opens the driver service's map of the city.
        return $this->redirect(['/admin/service-map/map', 'module' => 1, 'city' => (int) $id]);
    }
    // [doc:end] actionPincodeArea

 // [doc:start] actionCreate - /admin/city/create - Creates a new City model.
   /**
 * Creates a new City model.
 * If creation is successful, the browser will be redirected to the 'view' page.
 * @return mixed
 */public function actionCreate()
{
    $model = new City();
    $statesData = \app\modules\admin\models\States::find()->orderBy('id')->asArray()->all();

    if ($model->load(Yii::$app->request->post())) {
        $model->scenario = 'create';

        // Convert the boundary points array to a string
        $boundary = json_encode($model->boundary);

        // Save the model with the boundary string and States_id
        $model->boundary = $boundary;
        $stateId = Yii::$app->request->post('City')['States_id'];
        $model->States_id = $stateId;

        var_dump('State ID:', $stateId);
        var_dump('Model States_id:', $model->States_id);
        var_dump('Before save:', $model->attributes);
     

        if ($model->validate()) {
            if ($model->save()) {
                var_dump('Saved successfully!');
                
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                var_dump('Save failed!');
                var_dump($model->getErrors());
               
            }
        } else {
            var_dump('Validation failed!');
            var_dump($model->getErrors());
                    }
    }

    return $this->render('create', [
        'model' => $model,
        'statesData' => $statesData,
    ]);
}
 // [doc:end] actionCreate


// [doc:start] actionUpdate - /admin/city/update - Update
public function actionUpdate($id)
{
    $model = $this->findModel($id);
    $model->boundary = json_decode($model->boundary, true);
    $statesData = \app\modules\admin\models\States::find()->orderBy('id')->asArray()->all();

    if ($model->load(Yii::$app->request->post())) {
        $model->boundary = json_encode($model->boundary);
        $stateId = Yii::$app->request->post('City')['States_id'];
        $model->States_id = $stateId;

        if ($model->save(false)) {
            return $this->redirect(['view', 'id' => $model->id]);
        }
    }

    return $this->render('update', [
        'model' => $model,
        'statesData' => $statesData,
    ]);
}
// [doc:end] actionUpdate


    // [doc:start] actionDelete - /admin/city/delete - Deletes an existing City model.
    /**
     * Deletes an existing City model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        if (!empty($model)) {
            $model->status = City::STATUS_DELETE;
            $model->save(false);
        }

        return $this->redirect(['index']);
    }
    // [doc:end] actionDelete

    // [doc:start] actionDeletedCities - /admin/city/deleted-cities - Deleted Cities
    public function actionDeletedCities()
{
    $searchModel = new CitySearch();
    $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
    // Obtain the data provider for deleted cities
    $deletedDataProvider = new ActiveDataProvider([
        'query' => City::find()->where(['status' => City::STATUS_DELETE])->orderBy([
            new Expression("CASE WHEN SUBSTRING(name, 1, 1) BETWEEN 'A' AND 'Z' THEN 0 ELSE 1 END"),
            'name' => SORT_ASC,
        ]),

    ]);
    $deletedDataProvider->query = City::find()->where(['status' => City::STATUS_DELETE])->andFilterWhere(['like', 'name', $searchModel->name])->orderBy([
        new Expression("CASE WHEN SUBSTRING(name, 1, 1) BETWEEN 'A' AND 'Z' THEN 0 ELSE 1 END"),
        'name' => SORT_ASC,
    ]);
    

    return $this->render('deleted-cities', [
        'searchModel' => $searchModel,
        'deletedDataProvider' => $deletedDataProvider,
        'dataProvider' => $dataProvider,
    ]);
}
    // [doc:end] actionDeletedCities
// [doc:start] actionActiveCities - /admin/city/active-cities - Active Cities
public function actionActiveCities()
{
    // Obtain the data provider for deleted cities
    $activeDataProvider = new ActiveDataProvider([
        'query' => City::find()->where(['status' => City::STATUS_ACTIVE])->orderBy([
            new Expression("CASE WHEN SUBSTRING(name, 1, 1) BETWEEN 'A' AND 'Z' THEN 0 ELSE 1 END"),
            'name' => SORT_ASC,
        ]),

    ]);

    return $this->render('active-cities', [
        'activeDataProvider' => $activeDataProvider,
    ]);
}
// [doc:end] actionActiveCities


    
    // [doc:start] actionUpdateStatus - /admin/city/update-status - Update Status
    public function actionUpdateStatus()
    {
        $data = [];
        $post = \Yii::$app->request->post();
        \Yii::$app->response->format = 'json';
        if (!empty($post['id'])) {
            $model = City::find()
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
     * Finds the City model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return City the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = City::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(
                'The requested page does not exist.'
            );
        }
    }
}
