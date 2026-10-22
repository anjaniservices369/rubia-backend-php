<?php

/* @var $this yii\web\View */
/* @var $searchModel app\modules\admin\models\search\RideEarningsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

use yii\helpers\Html;
use kartik\export\ExportMenu;
use app\models\User;
use app\modules\admin\models\base\Banner;
use app\modules\admin\models\base\RideEarnings;
use app\modules\admin\models\RideRequest;
use app\modules\admin\models\WebSetting;
use kartik\grid\GridView;

$this->title = Yii::t('app', 'Ride Earnings');
$this->params['breadcrumbs'][] = $this->title;
$search = "$('.search-button').click(function(){
	$('.search-form').toggle(1000);
	return false;
});";
$this->registerJs($search);


?>
<div class="ride-earnings-index">
    <div class="card">
        <div class="card-body">

            <?php // echo $this->render('_search', ['model' => $searchModel]); 
            ?>

            <p>
              

            </p>
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

                [
                    'attribute' => 'ride_id',
                    'label' => Yii::t('app', 'Ride'),
                    'value' => function ($model) {
                        return $model->ride->id ?? "Added Manually";
                    },
                    'filterType' => GridView::FILTER_SELECT2,
                    'filter' => \yii\helpers\ArrayHelper::map(\app\modules\admin\models\RideRequest::find()->asArray()->all(), 'id', 'id'),
                    'filterWidgetOptions' => [
                        'pluginOptions' => ['allowClear' => true],
                    ],
                    'filterInputOptions' => ['placeholder' => 'Ride request', 'id' => 'grid-ride-earnings-search-ride_id']
                ],

                [
                    'attribute' => 'driver_id',
                    'label' => Yii::t('app', 'Driver'),
                    'value' => function ($model) {
                        return $model->driver->first_name ?? '';
                    },
                    'filterType' => GridView::FILTER_SELECT2,
                    'filter' => \yii\helpers\ArrayHelper::map(\app\models\User::find()->where(['user_role' => User::ROLE_TAXI])->asArray()->all(), 'id', 'first_name'),
                    'filterWidgetOptions' => [
                        'pluginOptions' => ['allowClear' => true],
                    ],
                    'filterInputOptions' => ['placeholder' => 'Driver', 'id' => 'grid-ride-earnings-search-driver_id']
                ],

                'total_ditance_km',
                [
                    'attribute' => 'gst',
                    'label' => Yii::t('app', 'GST'),

                    'value' => function ($model) {


                        $settings = new WebSetting();
                        $cgst = $settings->getSettingBykey('cgst');
                        $sgst = $settings->getSettingBykey('sgst');

                        $totalGst =  $cgst + $sgst;
                        $gst = ($model->driver_earning * $totalGst) / 100;
                        return round($gst, 2);
                    },
                ],

                [
                    'attribute' => 'admin_earning',
                    'label' => Yii::t('app', 'Admin Earnings'),

                    'value' => function ($model) {
                        $settings = new WebSetting();
                        $cgst = $settings->getSettingBykey('cgst');
                        $sgst = $settings->getSettingBykey('sgst');

                        $totalGst =  $cgst + $sgst;
                        $gst = ($model->driver_earning * $totalGst) / 100;
                        return round($model->admin_earning - $gst, 2);
                    },
                    'footer' => RideEarnings::getTotal($dataProvider->models, 'admin_earning'),

                ],



                [
                    'attribute' => 'driver_earning',
                    'label' => Yii::t('app', 'Driver Earnings'),

                    'footer' => RideEarnings::getTotal($dataProvider->models, 'driver_earning'),
                ],


                [
                    'attribute' => 'status',
                    'format' => 'raw',
                    'value' => function ($model) {
                        return $model->getStateOptionsBadges();
                    },


                ],
                'created_on',

                [
                    'class' => 'kartik\grid\ActionColumn',
                    'template' => '{view} ',
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
                'showFooter' => true,
                'filterModel' => $searchModel,
                'columns' => $gridColumn,
                'bordered' => false,
                'class' => 'table table-striped mb-0',
                'pjax' => true,
                'pjaxSettings' => ['options' => ['id' => 'kv-pjax-container-ride-earnings']],
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