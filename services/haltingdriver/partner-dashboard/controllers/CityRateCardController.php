<?php

namespace app\modules\admin\controllers;

use Yii;
use app\models\User;
use app\modules\admin\models\City;
use app\modules\admin\models\CityRateCard;
use app\modules\admin\models\search\CityRateCardSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;
use yii\db\Expression;
use yii\data\ActiveDataProvider;


/**
 * CityRateCardController implements the CRUD actions for CityRateCard model.
 */
class CityRateCardController extends Controller
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
                        'actions' => [
                            'index',
                            'view',
                            'create',
                            'update',
                            'delete',
                            'update-status',
                            'get-trip-ride-types',
                        ],
                        'matchCallback' => function () {
                            return User::isAdmin() || User::isSubAdmin();
                        },
                    ],
                    [
                        'allow' => true,
                        'actions' => [
                            'index',
                            'view',
                            'update',
                            'pdf',
                            'update-status',
                        ],
                        'matchCallback' => function () {
                            return User::isManager();
                        },
                    ],
                    [
                        'allow' => true,
                        'actions' => [
                            'index',
                            // 'view',
                            // 'update',
                            // 'pdf',
                            // 'update-status',
                        ],
                        'matchCallback' => function () {
                            return User::isFranchise();
                        },
                    ],
                    [
                        'allow' => false,
                    ],
                ],
            ],
        ];
    }
    // [doc:end] behaviors

    // [doc:start] actionGetTripRideTypes - /admin/city-rate-card/get-trip-ride-types - Get Trip Ride Types
    public function actionGetTripRideTypes($cityId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $tripTypes = [];
        $rideTypes = [];

        // Retrieve the trip types based on the city ID
        if ($cityId) {
            if ($cityRateCard = CityRateCard::findOne(['city_id' => $cityId])) {
                if (
                    $cityRateCard->trip_type ===
                    CityRateCard::TRIP_TYPE_INTERCITY
                ) {
                    $tripTypes = [
                        CityRateCard::TRIP_TYPE_INTERCITY => 'Intercity',
                    ];
                    $rideTypes = [
                        CityRateCard::RIDER_TYPE_ONEWAY => 'One Way',
                        CityRateCard::RIDER_TYPE_ROUNDTRIP => 'Round Trip',
                    ];
                } elseif (
                    $cityRateCard->trip_type ===
                    CityRateCard::TRIP_TYPE_OUTSTATION
                ) {
                    $tripTypes = [
                        CityRateCard::TRIP_TYPE_OUTSTATION => 'Outstation',
                    ];
                    $rideTypes = [
                        CityRateCard::RIDER_TYPE_ONEWAY => 'One Way',
                        CityRateCard::RIDER_TYPE_ROUNDTRIP => 'Round Trip',
                    ];
                }
            }
        }

        return [
            'tripTypes' => $tripTypes,
            'rideTypes' => $rideTypes,
        ];
    }
    // [doc:end] actionGetTripRideTypes

    // [doc:start] actionIndex - /admin/city-rate-card/index - Lists all CityRateCard models.
    /**
     * Lists all CityRateCard models.
     * @return mixed
     */
    public function actionIndex($status = CityRateCard::STATUS_ACTIVE)
    {
        $searchModel = new CityRateCardSearch();
        
        // Set the status filter in the search model
        $searchModel->status = $status;
        $searchModel->status = $status;

        // Define the order for sorting by city name
        // $activeDataProvider = new ActiveDataProvider([
        //     'query' => City::find()->where(['status' => City::STATUS_ACTIVE])->orderBy([
        //         new Expression("CASE WHEN SUBSTRING(name, 1, 1) BETWEEN 'A' AND 'Z' THEN 0 ELSE 1 END"),
        //         'name' => SORT_ASC,
        //     ]),
        // ]);
        
        
        // Use the appropriate search method based on user role
        if (User::isAdmin() || User::isSubAdmin()) {
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        } elseif (User::isManager()) {
            $dataProvider = $searchModel->managersearch(Yii::$app->request->queryParams);
        } elseif (User::isFranchise()) {
            $dataProvider = $searchModel->managersearch(Yii::$app->request->queryParams);
        }

         // Get all distinct city IDs
    $cityIds = CityRateCard::find()->select('city_id')->distinct()->column();

    // Create an associative array with city_id as keys and city names as values
    $cityNames = City::find()->where(['id' => $cityIds])->indexBy('id')->column();

    // Sort city IDs alphabetically based on corresponding city names
    asort($cityNames);

    // Sort the dataProvider using the specified order
    $dataProvider->setSort([
        'attributes' => [
            'city_id' => [
                'asc' => array_keys($cityNames),
                'desc' => array_keys($cityNames),
            ],
            // Add other attributes for sorting if needed
        ],
    ]);

    return $this->render('index', [
        'searchModel' => $searchModel,
        'dataProvider' => $dataProvider,
        'status' => $status, // Pass the status to the view
        'cityNames' => $cityNames, // Pass the sorted city names to the view
    ]);
    }
    // [doc:end] actionIndex

    // [doc:start] actionView - /admin/city-rate-card/view - Displays a single CityRateCard model.
    /**
     * Displays a single CityRateCard model.
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

    // [doc:start] actionCreate - /admin/city-rate-card/create - Creates a new CityRateCard model.
    /**
     * Creates a new CityRateCard model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new CityRateCard();

        $cityId = Yii::$app->request->get('city_id');
        $tripType = Yii::$app->request->get('trip_type');
        $rideType = Yii::$app->request->get('ride_type');

        if ($this->isDuplicateRecord($cityId, $tripType, $rideType)) {
            Yii::$app->session->setFlash(
                'error',
                'A record with the same city, trip type, and ride type already exists.'
            );
            return $this->redirect(['index']); // Redirect to a suitable action or URL
        }

        $model = new CityRateCard();

        if ($model->loadAll(Yii::$app->request->post())) {
            if ($model->saveAll()) {
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                // Handle the case when the record couldn't be saved
                Yii::$app->session->setFlash(
                    'error',
                    'Failed to save the record.'
                );
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }
    // [doc:end] actionCreate
    private function isDuplicateRecord($cityId, $tripType, $rideType)
    {
        $existingRecord = CityRateCard::findOne([
            'city_id' => $cityId,
            'trip_type' => $tripType,
            'ride_type' => $rideType,
        ]);

        return $existingRecord !== null;
    }

    // [doc:start] actionUpdate - /admin/city-rate-card/update - Updates an existing CityRateCard model.
    /**
     * Updates an existing CityRateCard model.
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

    // [doc:start] actionDelete - /admin/city-rate-card/delete - Deletes an existing CityRateCard model.
    /**
     * Deletes an existing CityRateCard model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        if (!empty($model)) {
            $model->status = CityRateCard::STATUS_DELETE;
            $model->save(false);
        }

        return $this->redirect(['index']);
    }
    // [doc:end] actionDelete

    // [doc:start] actionUpdateStatus - /admin/city-rate-card/update-status - Update Status
    public function actionUpdateStatus()
    {
        $data = [];
        $post = \Yii::$app->request->post();
        \Yii::$app->response->format = 'json';
        if (!empty($post['id'])) {
            $model = CityRateCard::find()
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
     * Finds the CityRateCard model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return CityRateCard the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = CityRateCard::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(
                Yii::t('app', 'The requested page does not exist.')
            );
        }
    }
}
