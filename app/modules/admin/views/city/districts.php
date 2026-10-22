<?php

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

use yii\helpers\Html;
use kartik\export\ExportMenu;
use app\models\User;
use app\modules\admin\models\base\Banner;
use app\modules\admin\models\base\City;
use app\modules\admin\models\base\CityRateCard;
use app\modules\admin\models\search\CitySearch;

use kartik\grid\GridView;
use yii\helpers\Url;

$this->title = 'Cities';
$this->params['breadcrumbs'][] = $this->title;
$search = "$('.search-button').click(function(){
	$('.search-form').toggle(1000);
	return false;
});";
$this->registerJs($search);
?>
<div class="city-index">
<div class="card">
       <div class="card-body">
    

    <p>
    <?php if (
        \Yii::$app->user->identity->user_role == User::ROLE_ADMIN ||
        \Yii::$app->user->identity->user_role == User::ROLE_SUBADMIN
    ) { ?>
        <?= Html::a(
            'Create City',
            ['create'],
            ['class' => 'btn btn-success']
        ) ?>
        <?php } ?>
    </p>
    </div>
    </div>
    <div class="card">
       <div class="card-body">
       <div class="btn-group">

       <style>
    /* Custom styles for the button */
.custom-btn {
    background-color: #3498db; /* Change this to your desired background color */
    color: #ffffff; /* Change this to your desired text color */
    /* Add any additional styles you want */
}

/* Optional: Hover styles for the button */
.custom-btn:hover {
    background-color: #2980b9; /* Change this to your desired hover background color */
    color: #ffffff; /* Change this to your desired hover text color */
}
    </style>

   
    
   


</div>


<?php $gridColumn = [
    ['class' => 'yii\grid\SerialColumn'],

    ['attribute' => 'id', 'visible' => false],

    'name',

    [
        'label' => 'Inner city Rate card',
        'format' => 'raw',
        'value' => function ($model) {
            $cityId = $model->id;
            $createUrlInterCityOneWay = Url::to([
                '/admin/city-rate-card/create',
                'city_id' => $cityId,
                'trip_type' => CityRateCard::TRIP_TYPE_INTERCITY,
                'ride_type' => CityRateCard::RIDER_TYPE_ONEWAY,
            ]);
            $createUrlInterCityRoundTrip = Url::to([
                '/admin/city-rate-card/create',
                'city_id' => $cityId,
                'trip_type' => CityRateCard::TRIP_TYPE_INTERCITY,
                'ride_type' => CityRateCard::RIDER_TYPE_ROUNDTRIP,
            ]);

            return '<div class="btn-group">' .
                '<div class="btn-group-row">' .
                '<a href="' .
                $createUrlInterCityOneWay .
                '" class="btn btn-primary">One way</a>' .
                '<a href="' .
                $createUrlInterCityRoundTrip .
                '" class="btn btn-secondary">Round Trip</a>' .
                '</div>';
        },
    ],
    [
        'label' => 'Out Station Rate card',
        'format' => 'raw',
        'value' => function ($model) {
            $cityId = $model->id;
            $createUrlOutstationOneWay = Url::to([
                '/admin/city-rate-card/create',
                'city_id' => $cityId,
                'trip_type' => CityRateCard::TRIP_TYPE_OUTSTATION,
                'ride_type' => CityRateCard::RIDER_TYPE_ONEWAY,
            ]);
            $createUrlOutstationRoundTrip = Url::to([
                '/admin/city-rate-card/create',
                'city_id' => $cityId,
                'trip_type' => CityRateCard::TRIP_TYPE_OUTSTATION,
                'ride_type' => CityRateCard::RIDER_TYPE_ROUNDTRIP,
            ]);

            return '<div class="btn-group">' .
                '<div class="btn-group-row">' .
                '<a href="' .
                $createUrlOutstationOneWay .
                '" class="btn btn-primary">One way</a>' .
                '<a href="' .
                $createUrlOutstationRoundTrip .
                '" class="btn btn-secondary">Round Trip</a>' .
                '</div>';
        },
    ],

    // 'boundary',

    [


        'attribute' => 'status',
        "format" => 'raw',
        'label' => Yii::t('app', 'Status'),
        'filter'  =>  (new City())->getStateOptions(),
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

    [
        'class' => 'kartik\grid\ActionColumn',
        'template' => '{view} {update} {delete} {addRateCard}',
        'buttons' => [
            'view' => function ($url, $model) {
                if (
                    \Yii::$app->user->identity->user_role == User::ROLE_ADMIN ||
                    \Yii::$app->user->identity->user_role ==
                        User::ROLE_SUBADMIN ||
                    \Yii::$app->user->identity->user_role == User::ROLE_MANAGER
                ) {
                    return Html::a(
                        '<span class="fas fa-eye" aria-hidden="true"></span>',
                        $url
                    );
                }
            },
            'update' => function ($url, $model) {
                if (
                    \Yii::$app->user->identity->user_role == User::ROLE_ADMIN ||
                    \Yii::$app->user->identity->user_role ==
                        User::ROLE_SUBADMIN ||
                    \Yii::$app->user->identity->user_role == User::ROLE_MANAGER
                ) {
                    return Html::a(
                        '<span class="fas fa-pencil-alt" aria-hidden="true"></span>',
                        $url
                    );
                }
            },
            'delete' => function ($url, $model) {
                if (
                    \Yii::$app->user->identity->user_role == User::ROLE_ADMIN ||
                    \Yii::$app->user->identity->user_role == User::ROLE_SUBADMIN
                ) {
                    return Html::a(
                        '<span class="fas fa-trash-alt" aria-hidden="true"></span>',
                        $url,
                        [
                            'data' => [
                                'method' => 'post',
                                // use it if you want to confirm the action
                                'confirm' => 'Are you sure?',
                            ],
                        ]
                    );
                }
            },

            'addRateCard' => function ($url, $model) {
                $url = Url::toRoute([
                    '/admin/city-rate-card/create',
                    'city_id' => $model->id,
                ]);
                if (
                    \Yii::$app->user->identity->user_role == User::ROLE_ADMIN ||
                    \Yii::$app->user->identity->user_role ==
                        User::ROLE_SUBADMIN ||
                    \Yii::$app->user->identity->user_role == User::ROLE_MANAGER
                ) {
                    return Html::a(
                        '<span class="fas fa-plus" aria-hidden="true"></span>',
                        $url
                    );
                }
            },
        ],
    ],
]; ?>


<?php // echo $this->render('_search', ['model' => $searchModel]); ?>
 <div class="card">
        <div class="card-body">

    <?= GridView::widget([
        'dataProvider' => $districtsDataProvider,
        'columns' => $gridColumn,
        'pjax' => true,
        'pjaxSettings' => ['options' => ['id' => 'kv-pjax-container-city']],
        'panel' => [
            'type' => GridView::TYPE_PRIMARY,
            'heading' => '<span class="glyphicon glyphicon-book"></span> Active Cities',
             //   Html::encode($this->title),
        ],
        'export' => false,
        // your toolbar can include the additional full export menu
        'toolbar' => [
            '{export}',
            ]
        ]); ?>
     </div>
    </div>
    
    <!-- <div class="card">
        <div class="card-body">
                <h2>Deleted Cities</h2>   
             <?//=GridView::widget([
                // 'dataProvider' => $deletedDataProvider,
                // 'columns' => $gridColumn,
                // 'pjax' => true,
                // 'pjaxSettings' => ['options' => ['id' => 'kv-pjax-container-city-deleted']],
                // 'panel' => [
                //     'type' => GridView::TYPE_PRIMARY,
                //     'heading' => '<span class="glyphicon glyphicon-book"></span> Deleted Cities',
                // ],
                // 'export' => false,
           // ]) ?> --
        </div>
    </div> -->
</div>
</div>
</div>
</div>

<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<script>
$(document).on('change','select[id^=status_list_]',function(){
var id=$(this).attr('data-id');
var val=$(this).val();

$.ajax({
	  type: "POST",
	 
      url: "/rubia_backend/gii/default/status-change",
     
 
      data: {id:id,val:val},
	  success: function(data){
		  swal("Good job!", "Status Successfully Changed!", "success");
	  }
	});
});


</script>