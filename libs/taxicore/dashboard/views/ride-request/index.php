<?php

/* @var $this yii\web\View */
/* @var $searchModel app\modules\admin\models\search\RideRequestSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

use yii\helpers\Html;
use kartik\export\ExportMenu;
use app\models\User;
use app\modules\admin\models\base\Banner;
use app\modules\admin\models\base\RideRequest;
use app\modules\admin\models\RideRequest as ModelsRideRequest;
use kartik\grid\GridView;

$this->title = Yii::t('app', 'Ride Requests');
$this->params['breadcrumbs'][] = $this->title;
$search = "$('.search-button').click(function(){
	$('.search-form').toggle(1000);
	return false;
});";
$this->registerJs($search);


?>
<div class="ride-request-index">
    <div class="card">
        <div class="card-body">

            <?php // echo $this->render('_search', ['model' => $searchModel]); 
            ?>

            <div class="search-form" style="display:none">
                <?= $this->render('_search', ['model' => $searchModel]); ?>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <?php
            $gridColumn = [
                ['class' => 'yii\grid\SerialColumn'],

                ['attribute' => 'id', 'visible' => false],

                'id',
                [
                    'attribute' => 'created_on',
                    'label' => 'Ride Date',
                    'value' => function ($model, $key, $index, $widget) {
                        return date("Y-m-d", strtotime($model->created_on));
                    },
                    'filterType' => GridView::FILTER_DATE,
                    'filterWidgetOptions' => ([
                        'attribute' => 'created_on',
                        //   'presetDropdown' => false,
                        //   'convertFormat' => false,
                        'pluginOptions' => [
                            'separator' => ' - ',
                            'format' => 'yyyy-mm-dd',
                            'locale' => [
                                'format' => 'yyyy-mm-dd',
                            ],

                        ],
                        'pluginEvents' => [
                            "apply.daterangepicker" => "function() { apply_filter('created_on') }",
                        ],
                    ]),

                ],
                [
                    'attribute' => 'user_id',
                    'format' => 'raw',
                    'value' => function ($model) {
                        return $model->customer['name'] ?: $model->customer['phone'];
                    },


                ],





                'pickup_pincode',




                'drop_pincode',
                [
                    'attribute' => 'vehical_type',
                    'format' => 'raw',
                    'value' => function ($model) {
                        return $model->vehicalType->title ?? '';
                    },


                ],

                'final_price',

                'ride_start_time',

                'ride_end_time',
              


                
                [


                    'attribute' => 'status',
                    "format" => 'raw',
                    'label' => Yii::t('app', 'Status'),
                    'filter'  =>  (new ModelsRideRequest())->getStateOptions(),
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
                    'template' => '{view} {invoice}',
                    'buttons' => [
                        'view' => function ($url, $model) {
                            if (\Yii::$app->user->identity->user_role == User::ROLE_ADMIN || \Yii::$app->user->identity->user_role == User::ROLE_SUBADMIN || \Yii::$app->user->identity->user_role == User::ROLE_MANAGER) {
                                return Html::a('<span class="fas fa-eye" aria-hidden="true"></span>', $url);
                            }
                        },
                        'update' => function ($url, $model) {
                            if (\Yii::$app->user->identity->user_role == User::ROLE_ADMIN || \Yii::$app->user->identity->user_role == User::ROLE_SUBADMIN || \Yii::$app->user->identity->user_role == User::ROLE_MANAGER) {
                                return Html::a('<span class="fas fa-pencil-alt" aria-hidden="true"></span>', $url);
                            }
                        },
                        'delete' => function ($url, $model) {
                            if (\Yii::$app->user->identity->user_role == User::ROLE_ADMIN || \Yii::$app->user->identity->user_role == User::ROLE_SUBADMIN) {
                                return Html::a('<span class="fas fa-trash-alt" aria-hidden="true"></span>', $url, [
                                    'data' => [
                                        'method' => 'post',
                                        // use it if you want to confirm the action
                                        'confirm' => 'Are you sure?',
                                    ],
                                ]);
                            }
                        },





                    ]



                ],
            ];
            ?>
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'columns' => $gridColumn,
                'bordered' => false,
                'class' => 'table table-striped mb-0',
                'pjax' => true,
                'pjaxSettings' => ['options' => ['id' => 'kv-pjax-container-ride-request']],
                'panel' => [
                    'type' => 'light',
                    'heading' => '<span class="glyphicon glyphicon-book"></span>  ' . Html::encode($this->title),
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
                            ExportMenu::FORMAT_PDF => false
                        ]
                    ]),
                ],
            ]); ?>
        </div>
    </div>
</div>

<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<script>
    $(document).on('change', 'select[id^=status_list_]', function() {
        var id = $(this).attr('data-id');
        var val = $(this).val();

        $.ajax({
            type: "POST",

            url: "/easygo_backend/gii/default/status-change",


            data: {
                id: id,
                val: val
            },
            success: function(data) {
                swal("Good job!", "Status Successfully Changed!", "success");
            }
        });
    });
</script>