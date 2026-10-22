<?php

/* @var $this yii\web\View */
/* @var $searchModel app\modules\admin\models\search\UserSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

use yii\helpers\Html;
use kartik\export\ExportMenu;
use app\models\User;
use app\modules\admin\models\base\Banner;

use kartik\grid\GridView;
use yii\helpers\Url;

$this->title = Yii::t('app', 'Users');
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
<?php $gridColumn = [
    ['class' => 'yii\grid\SerialColumn'],

    ['attribute' => 'id', 'visible' => false],

    [
        'class' => 'kartik\grid\ActionColumn',
        'template' => '{view} {update} {delete}',
        'buttons' => [
            'view' => function ($url, $model) {
                $url = Url::toRoute(['users/driver-view', 'id' => $model->id]);
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

    [
        'attribute' => 'is_done_police_verification',
        'format' => 'raw',
        'value' => function ($model) {
            return $model->policeVerificationBadges();
        },
    ],
    [
        'attribute' => 'profile_verified',
        'format' => 'raw',
        'value' => function ($model) {
            return $model->profileVerificationBadges();
        },
    ],

    // 'email:email',

    'first_name',

    'contact_no',

    'gender',

    [
        'attribute' => 'profile_image',
        'format' => 'raw',
        'value' => function ($model) {
            return Html::img($model->profile_image, [
                'width' => '100px',
                'height' => '100px',
            ]);
        },
    ],

    // [
    //         'attribute' => 'status',
    //         'format' => 'raw',
    //         'value' => function($model){
    //             return $model->getStateOptionsBadges();
    //         },

    //     ],

    [
        'attribute' => 'online_status',
        'format' => 'raw',
        'value' => function ($model) {
            return $model->onlineBadges();
        },
    ], 

    // [
    //     'attribute' => 'is_paid_joining_fee',
    //     'format' => 'raw',
    //     'value' => function ($model) {
    //         return $model->getPaidJoiningFeeBadges();
    //     },
    // ],

    'paid_amount',

    'average_rating',
    [
        'attribute' => 'status',
        'format' => 'raw',
        'value' => function ($model) {
            return $model->stateBadges();
        },
    ],
]; ?>
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