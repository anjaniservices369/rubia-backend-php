<?php

/* @var $this yii\web\View */
/* @var $searchModel app\modules\admin\models\search\UserSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

use yii\helpers\Html;
use kartik\export\ExportMenu;
use app\models\User;
use app\modules\admin\models\base\Banner;
use app\modules\admin\models\User as ModelsUser;
use app\modules\admin\models\DriverWallet;
use kartik\grid\GridView;
use yii\debug\models\search\User as SearchUser;
use yii\helpers\Url;

$this->title = Yii::t('app', 'Driver');
$this->params['breadcrumbs'][] = $this->title;
$search = "$('.search-button').click(function(){
    $('.search-form').toggle(1000);
    return false;
});";
$this->registerJs($search);
?>
<div class="user-index">

<div class="card">
       <div class="card-body">
       <?= Html::a('Active Status', ['drivers', 'status' => User::STATUS_ACTIVE], ['class' => 'btn btn-primary']) ?>

<?= Html::a('Blocked Status', ['drivers', 'status' => User::STATUS_BLOCKED], ['class' => 'btn btn-danger']) ?>
         
        
<?= Html::a('Verified Drivers', ['drivers', 'profile_verified' => User::profile_verified_yes], ['class' => 'btn btn-success']) ?>

<?php $gridColumn = [
    ['class' => 'yii\grid\SerialColumn'],

    ['attribute' => 'id', 'visible' => false],

    [
        'class' => 'kartik\grid\ActionColumn',
        'template' => '{view} {update} {delete}',
        'buttons' => [
            'view' => function ($url, $model) {
                $url = Url::toRoute(['users/driver-profile', 'id' => $model->id]);
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
        ],
    ],

    
//     [
//         'attribute' => 'profile_verified',
//         'format' => 'raw',
//         'value' => function ($model) {
//             return $model->profileVerificationBadges();
//         },
//     ],
//     [
//         'attribute' => 'city_Name',
//         'format' => 'raw',
//         'value' => function ($model) {
//             $city = \app\modules\admin\models\base\City::find()->where(['id'=> $model->city_id])->one();
//             return $city->name??'';
//         },
//     ],

//     // 'email:email',

//     'first_name',

//     'contact_no',

//     'gender',

//     [
//         'attribute' => 'profile_image',
//         'format' => 'raw',
//         'value' => function ($model) {
//             return Html::img($model->profile_image, [
//                 'width' => '100px',
//                 'height' => '100px',
//             ]);
//         },
//     ],


//     [


//         'attribute' => 'status',
//         "format" => 'raw',
//         'label' => Yii::t('app', 'Status'),
//         'filter'  =>  (new User())->getStatusesList(),
//         'filterType' => GridView::FILTER_SELECT2,
//         'filterWidgetOptions' => [
//             'pluginOptions' => ['allowClear' => true],
//         ],
//         'filterInputOptions' => ['placeholder' => 'status', 'id' => 'grid-state-search-status'],


//             'attribute' => 'status',
//             'format' => 'raw',
//             'value' => function($model){
//                 return $model->stateBadges();
//             },


//         ],


    

//     // [
//     //         'attribute' => 'status',
//     //         'format' => 'raw',
//     //         'value' => function($model){
//     //             return $model->getStateOptionsBadges();
//     //         },

//     //     ],

   

//     // [
//     //     'attribute' => 'is_paid_joining_fee',
//     //     'format' => 'raw',
//     //     'value' => function ($model) {
//     //         return $model->getPaidJoiningFeeBadges();
//     //     },
//     // ],

//     'Registeration',

//     'average_rating',
//     [
//         'attribute' => 'is_done_police_verification',
//         'format' => 'raw',
//         'value' => function ($model) {
//             return $model->policeVerificationBadges();
//         },
//     ],

//     // [
//     //     'attribute' => 'status',
//     //     'format' => 'raw',
//     //     'value' => function ($model) {
//     //         return $model->stateBadges();
//     //     },
//     // ],
// ]; 

[
    'attribute' => 'profile_verified',
    'format' => 'raw',
    'value' => function ($model) {
        return $model->profileVerificationBadges();
    },
],

[
    'attribute' => 'online_status',
    'format' => 'raw',
    'value' => function ($model) {
        return $model->onlineBadges();
    },
],
[
    'attribute' => 'Wallet Amount',
    'format' => 'raw',
    'value' => function ($model) {
        $wallet = new DriverWallet();
        return $wallet->availableBalance($model->id);
    },
],

[
    // the city is found from the address the partner gave (see Drivers-profile), so a franchise finds its drivers by city
    'attribute' => 'city_id',
    'label' => 'City (from address)',
    'format' => 'raw',
    'filter' => \yii\helpers\ArrayHelper::map((new \yii\db\Query())->select(['id', 'name'])->from('city')->where(['status' => 1])->orderBy('name')->all(), 'id', 'name'),
    'value' => function ($model) {
        $city = \app\modules\admin\models\base\City::find()->where(['id' => $model->city_id])->one();
        return \yii\helpers\Html::encode($model->city_name ?: ($city->name ?? ''));
    },
],
  
[
    'attribute' => 'first_name', // Assuming 'first_name' is the attribute name in your User model
    'label' => 'Full Name', // You can customize the column label if needed
],  
'contact_no',
'gender',

[
    'attribute' => 'status',
    'format' => 'raw',
    'filter'  =>  (new User())->getStatusesList(),
    'filterType' => GridView::FILTER_SELECT2,
    'filterWidgetOptions' => [
        'pluginOptions' => ['allowClear' => true],
    ],
    'filterInputOptions' => ['placeholder' => 'status', 'id' => 'grid-state-search-status'],
    'value' => function($model){
        return $model->stateBadges();
    },
],

[
    // The one-time joining fee: paid (amount and the gateway it was paid through) or not paid yet.
    'attribute' => 'paid_amount',
    'label' => 'Joining fee',
    'format' => 'raw',
    'value' => function ($model) {
        if ((int) $model->is_paid_joining_fee !== 1) {
            return '<span class="badge bg-warning text-dark">Not paid</span>';
        }
        $gateway = (new \yii\db\Query())->select('gateway')->from('payment_order')
            ->where(['user_id' => $model->id, 'purpose' => 'joining_fee', 'status' => 'paid'])->orderBy(['id' => SORT_DESC])->scalar();
        $text = '<span class="badge bg-success">Paid</span> &#8377;' . number_format((float) $model->paid_amount, 2);

        return $text . ($gateway ? ' <small class="text-muted">' . \yii\helpers\Html::encode(ucfirst(str_replace('_', ' ', $gateway))) . '</small>' : '');
    },
],

[
    'attribute' => 'average_rating',
    'label' => 'Average Rating',
    'value' => function ($model) {
        // Get the driver ID
        $driverId = $model->id;

        // Fetch the average rating directly from the driver_booking_ratings table
        $averageRating = \app\modules\admin\models\DriverBookingRatings::find()
            ->where(['driver_id' => $driverId])
            ->average('rating');

        // Check if there are ratings and format the result
        return $averageRating !== null ? number_format($averageRating, 2) : 0;
    },
],


[
    'attribute' => 'is_done_police_verification',
    'format' => 'raw',
    'value' => function ($model) {
        return $model->policeVerificationBadges();
    },
],
  
  'joining_date',

];?>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => $gridColumn,
        'pjax' => true,
        'pjaxSettings' => ['options' => ['id' => 'kv-pjax-container-user']],
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
            '{toggleData}', // Add this line to enable column selection
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