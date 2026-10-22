<?php

/* @var $this yii\web\View */
/* @var $searchModel app\modules\admin\models\search\PincodeSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

use yii\helpers\Html;
use kartik\export\ExportMenu;
use app\models\User;
use app\modules\admin\models\base\Banner;

use kartik\grid\GridView;

$this->title = Yii::t('app', 'Pincodes');
$this->params['breadcrumbs'][] = $this->title;
$search = "$('.search-button').click(function(){
	$('.search-form').toggle(1000);
	return false;
});";
$this->registerJs($search);


?>
<div class="pincode-index">
    <div class="card">
        <div class="card-body">

            <?php // echo $this->render('_search', ['model' => $searchModel]); 
            ?>

            <p>
                <?php if (\Yii::$app->user->identity->user_role == User::ROLE_ADMIN || \Yii::$app->user->identity->user_role == User::ROLE_SUBADMIN) { ?>
                    <?= Html::a(Yii::t('app', 'Create Pincode'), ['create'], ['class' => 'btn btn-success']) ?>
                <?php  } ?>
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
                    'attribute' => 'city_id',
                    'label' => Yii::t('app', 'City'),
                    'value' => function ($model) {
                        if ($model->city) {
                            return $model->city->name;
                        } else {
                            return NULL;
                        }
                    },
                    'filterType' => GridView::FILTER_SELECT2,
                    'filter' => \yii\helpers\ArrayHelper::map(\app\modules\admin\models\City::find()->asArray()->all(), 'id', 'name'),
                    'filterWidgetOptions' => [
                        'pluginOptions' => ['allowClear' => true],
                    ],
                    'filterInputOptions' => ['placeholder' => 'City', 'id' => 'grid-pincode-search-city_id']
                ],

                'name',

                [
                    'attribute' => 'vehical_id',
                    'label' => Yii::t('app', 'Vehical'),
                    'value' => function ($model) {
                        return $model->vehical->title ?? '';
                    },
                    'filterType' => GridView::FILTER_SELECT2,
                    'filter' => \yii\helpers\ArrayHelper::map(\app\modules\admin\models\Vehicals::find()->asArray()->all(), 'id', 'title'),
                    'filterWidgetOptions' => [
                        'pluginOptions' => ['allowClear' => true],
                    ],
                    'filterInputOptions' => ['placeholder' => 'Vehicles ', 'id' => 'grid-pincode-search-vehical_id']
                ],


                [
                    'attribute' => 'status',
                    'format' => 'raw',
                    'value' => function ($model) {
                        return $model->getStateOptionsBadges();
                    },


                ],
                [
                    'class' => 'kartik\grid\ActionColumn',
                    'template' => '{view} {update} {delete}',
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
                'headerContainer' => ['class' => 'border-0', 'style' => 'border-bottom:none !important; border-top:0 !important;'],
                'pjaxSettings' => ['options' => ['id' => 'kv-pjax-container-city']],

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