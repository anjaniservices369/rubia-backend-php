<?php
// die('dddd');
use app\modules\admin\models\UserEmergencyContacts;
use yii\helpers\Html;
use yii\widgets\DetailView;
use kartik\grid\GridView;
use app\modules\admin\models\base\Booking;
use app\modules\admin\models\User;
use yii\data\ActiveDataProvider;
/* @var $this yii\web\View */
/* @var $model app\models\Users */

//$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Users'), 'url' => ['index']];
//$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-view">
<div class="card">
    <div class="card-body">
    <div class="row">
        <div class="col-sm-9">
            <h2><?= Yii::t('app', 'Users').' '. Html::encode($this->title) ?></h2>
        </div>
        <div class="col-sm-3" style="margin-top: 15px">   
            <?= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
           
        </div>
    </div>
    <div class="row">
        <?php 
            $gridColumn = [
                ['attribute' => 'id', 'visible' => false],
                'username',
                'email',
                'first_name',
                'last_name',
                'contact_no',
                'user_role'
            ];
            echo DetailView::widget([
                'model' => $model,
                'attributes' => $gridColumn
            ]);
        ?>
    </div>
    </div>
    </div>
    </div>
    <?php 
    $emergencyContacts = UserEmergencyContacts::find()
        ->where(['user_id' => $model->id])
        ->orderBy(['id' => SORT_ASC])
        ->all();
    ?>

    <div class="user-view">
        <div class="card">
            <div class="card-body">
            <div class="row">
                <div class="col-sm-9">
                    <h2><?= Yii::t('app', 'Users').' '. Html::encode('Emergancy Contact') ?></h2>
                </div>
                <!--  -->
            </div>
            <?php
            if (!empty($emergencyContacts))
            { ?>
            <div class="row">
                <?php 
                    $gridColumn = [
                        ['attribute' => 'id', 'visible' => false],
                        'name',
                        'phone_number',
                        'relationship',
                        'status',
                    ];
                    foreach ($emergencyContacts as $emergencyContact) {
                        echo DetailView::widget([
                            'model' => $emergencyContact,
                            'attributes' => $gridColumn
                        ]);
                    }
                ?>
            </div>
            <?php }?>
            </div>
            </div>
    </div>
     <div class="card">
            <div class="card-body"> 
    <?php 
// Assuming $model->id contains the specific user's ID
$userBookingDataProvider = new ActiveDataProvider([
    'query' => Booking::find()->where(['user_id' => $model->id]),
    'pagination' => [
        'pageSize' => 20,   // Adjust as needed
    ],
]);

 $gridColumn = [
    ['class' => 'yii\grid\SerialColumn'],

    ['attribute' => 'id', 'visible' => false],

    [
        'attribute' => 'user_id',
        'label' => 'User',
        'value' => function ($model) {
            return $model->user->username ?? '';
        },
        
    ],

    [
        'attribute' => 'rider_type',
        'label' => 'Ride Type',
        'format' => 'raw',
        'value' => function ($model) {
            return $model->getRideTypeBadges();
        },
        'filterType' => GridView::FILTER_SELECT2,
        // 'filter' => function ($model) {
        //     return $model->getTripType();
        // },
        'filter' => Booking::geRideType(),

        'filterWidgetOptions' => [
            'pluginOptions' => ['allowClear' => true],
        ],
        'filterInputOptions' => [
            'placeholder' => 'Ride Type',
            'id' => 'grid-booking-search-rider_type',
        ],
    ],
    [
        'attribute' => 'trip_type',
        'label' => 'Trip Type',
        'format' => 'raw',
        'value' => function ($model) {
            return $model->getTripTypeBadges();
        },
        'filterType' => GridView::FILTER_SELECT2,
        // 'filter' => function ($model) {
        //     return $model->getTripType();
        // },
        'filter' => Booking::getTripType(),

        'filterWidgetOptions' => [
            'pluginOptions' => ['allowClear' => true],
        ],
        'filterInputOptions' => [
            'placeholder' => 'Trip Type',
            'id' => 'grid-booking-search-trip_type',
        ],
    ],

    // 'pickup_latitude',

    // 'pickup_longitude',

    // 'drop_latitude',

    // 'drop_longitude',

    // 'car_type',

    'car_model',

    'pickup_date',

    'pickup_time',

    'estimate_price',

    'usage_hours',

    'coupon_code',

    // 'payment_method',

    [


        'attribute' => 'status',
        "format" => 'raw',
        'label' => Yii::t('app', 'Status'),
        'filter'  =>  (new Booking())->getStateOptions(),
        'filterType' => GridView::FILTER_SELECT2,
        'filterWidgetOptions' => [
            'pluginOptions' => ['allowClear' => true],
        ],
        'filterInputOptions' => ['placeholder' => 'status', 'id' => 'grid-state-search-status'],


            'attribute' => 'status',
            'format' => 'raw',
            'value' => function($model){
                return $model->getStateOptionsBadges();
            },


        ],

    // 'created_on',

    // 'updated_on',

    // 'create_user_id',

    // 'update_user_id',
   
]; 
// Render GridView with the data provider
echo GridView::widget([
    'dataProvider' => $userBookingDataProvider,
    'columns' => $gridColumn,   // Columns configuration
    // ... other GridView settings
]);

?>

  </div>
    </div>