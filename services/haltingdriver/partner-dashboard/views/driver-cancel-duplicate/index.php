<?php

/* @var $this yii\web\View */
/* @var $searchModel app\modules\admin\models\search\BookingSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

use yii\helpers\Html;
use kartik\export\ExportMenu;
use app\models\User;
use app\modules\admin\models\base\Banner;
use app\modules\admin\models\base\Booking;
use app\modules\admin\models\Booking as ModelsBooking;
use kartik\grid\GridView;
use app\modules\admin\models\base\DriverCancelDuplicate;

$this->title = 'Bookings';
$this->params['breadcrumbs'][] = $this->title;
$search = "$('.search-button').click(function(){
	$('.search-form').toggle(1000);
	return false;
});";
$this->registerJs($search);
?>
<div class="booking-index">
<div class="card">
       <div class="card-body">
    
    <?php
// echo $this->render('_search', ['model' => $searchModel]);
?>

    <p>
    <?php if (
        \Yii::$app->user->identity->user_role == User::ROLE_ADMIN ||
        \Yii::$app->user->identity->user_role == User::ROLE_SUBADMIN
    ) { ?>
      
        <?php } ?>
        <?= Html::a('Advance Search', '#', [
            'class' => 'btn btn-info search-button',
        ]) ?>
    </p>
    <div class="search-form" style="display:none">
        <?= $this->render('_search', ['model' => $searchModel]) ?>
    </div>
        </div>
    </div>
    <div class="card">
       <div class="card-body">
<?php $gridColumn = [
    ['class' => 'yii\grid\SerialColumn'],

    ['attribute' => 'id', 'visible' => false],

    [
        'attribute' => 'user_id',
        'label' => 'User',
        'value' => function ($model) {
            return $model->customer['name'] ?: $model->customer['phone'];
        },
        'filterInputOptions' => [
            'placeholder' => 'User id',
            'id' => 'grid-booking-search-user_id',
        ],
    ],

    [
        'attribute' => 'rider_type',
        'label' => 'Ride Type',
        'format' => 'raw',
        'value' => function ($model) {
            return $model->getRideTypeBadges();
        },
        'filterType' => GridView::FILTER_SELECT2,
        'filter' => DriverCancelDuplicate::geRideType(), // Change Booking to DriverCancelDuplicate
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
        'filter' => DriverCancelDuplicate::getTripType(), // Change Booking to DriverCancelDuplicate
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
        'label' => Yii::t('app', 'Booking Status'),
        'filter'  =>  (new ModelsBooking())->getStateOptions(),
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
    [
        'class' => 'kartik\grid\ActionColumn',
        'template' => '{view} {update} {delete}',
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
            // 'delete' => function ($url, $model) {
            //     if (
            //         \Yii::$app->user->identity->user_role == User::ROLE_ADMIN ||
            //         \Yii::$app->user->identity->user_role == User::ROLE_SUBADMIN
            //     ) {
            //         return Html::a(
            //             '<span class="fas fa-trash-alt" aria-hidden="true"></span>',
            //             $url,
            //             [
            //                 'data' => [
            //                     'method' => 'post',
            //                     // use it if you want to confirm the action
            //                     'confirm' => 'Are you sure?',
            //                 ],
            //             ]
            //         );
            //     }
            // },
        ],
    ],
]; ?>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => $gridColumn,
        'pjax' => true,
        'pjaxSettings' => ['options' => ['id' => 'kv-pjax-container-booking']],
        'panel' => [
            'type' => GridView::TYPE_PRIMARY,
            'heading' =>
                '<span class="glyphicon glyphicon-book"></span>  ' .
                Html::encode($this->title),
        ],
        'export' => false,
        // your toolbar can include the additional full export menu
        'toolbar' => [
            '{export}',
            ExportMenu::widget([
                'dataProvider' => $dataProvider,
                'columns' => $gridColumn,
                'target' => ExportMenu::TARGET_BLANK,
                'fontAwesome' => true,
                'dropdownOptions' => [
                    'label' => 'Full',
                    'class' => 'btn btn-default',
                    'itemsBefore' => [
                        '<li class="dropdown-header">Export All Data</li>',
                    ],
                ],
                'exportConfig' => [
                    ExportMenu::FORMAT_PDF => false,
                ],
            ]),
        ],
    ]) ?>
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