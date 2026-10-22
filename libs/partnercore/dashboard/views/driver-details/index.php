<?php
/* @var $this yii\web\View */
/* @var $searchModel app\modules\admin\models\search\DriverDetailsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

use yii\helpers\Html;
use kartik\export\ExportMenu;
use app\models\User;
use app\modules\admin\models\DriverDetails;
use app\modules\admin\models\search\DriverDetailsSearch;
use kartik\grid\GridView;

$this->title = Yii::t('app', 'Rider Details');
$this->params['breadcrumbs'][] = $this->title;

$search = "$('.search-button').click(function(){
    $('.search-form').toggle(1000);
    return false;
});";
$this->registerJs($search);

?>
<div class="driver-details-index">
    <div class="card">
        <div class="card-body">
            <?php // echo $this->render('_search', ['model' => $searchModel]); ?>
            <p></p>
            <div class="search-form" style="display:none">
                <?= $this->render('_search', ['model' => $searchModel]); ?>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <?php
            $gridColumn = [
                ['class' => 'kartik\grid\SerialColumn'],
                ['attribute' => 'id', 'visible' => false],
                [
                    'class' => 'kartik\grid\ActionColumn',
                    'template' => '{view} {update} ',
                    'buttons' => [
                        'view' => function ($url, $model) {
                            if (\Yii::$app->user->identity->user_role == User::ROLE_ADMIN) {
                                return Html::a('<span class="fas fa-eye" aria-hidden="true"></span>', $url);
                            }
                        },
                        'update' => function ($url, $model) {
                            if (\Yii::$app->user->identity->user_role == User::ROLE_ADMIN) {
                                return Html::a('<span class="fas fa-pencil-alt" aria-hidden="true"></span>', $url);
                            }
                        },
                        'delete' => function ($url, $model) {
                            if (\Yii::$app->user->identity->user_role == User::ROLE_ADMIN) {
                                return Html::a('<span class="fas fa-trash-alt" aria-hidden="true"></span>', $url, [
                                    'data' => [
                                        'method' => 'post',
                                        'confirm' => 'Are you sure?',
                                    ],
                                ]);
                            }
                        },
                    ],
                ],
                [
                    'attribute' => 'is_verified',
                    'label' => Yii::t('app', 'Profile verified'),
                    'format' => 'raw',
                    'value' => function ($model) {
                        return $model->getVerifyOptionsBadges();
                    },
                    'filter'  => (new DriverDetails)->getVerifyOptions(),
                ],
                [
                    'attribute' => 'online_status',
                    'format' => 'raw',
                    'label' => Yii::t('app', 'Online Status'),
                    'value' => function ($model) {
                        if (!empty($model->user->online_status ?? '')) {
                            return $model->user ? $model->user->getOnlineStatusBadges() : '';
                        } else {
                            return "";
                        }
                    },
                    'filter'  =>  \app\models\User::getOnlineStatus(),
                ],
                [
                    'attribute' => 'city_id',
                    'label' => Yii::t('app', 'City Name'),
                    'format' => 'raw',
                    'value' => function ($model) {
                        $city = \app\modules\admin\models\base\City::find()->where(['id'=> $model->city_id])->one();
                        return $city->name ?? '';
                    },
                ],
                [
                    'attribute' => 'user_id',
                    'label' => Yii::t('app', 'Bike Rider'),
                    'value' => function ($model) {
                        $first_name  = $model->user->first_name ?? '';
                        $last_name  = $model->user->last_name ?? '';
                        return $first_name . ' ' . $last_name;
                    },
                ],
                [
                    'attribute' => 'user_id',
                    'format' => 'raw',
                    'label' => Yii::t('app', 'Phone number'),
                    'value' => function ($model) {
                        $contact_no = $model->user->contact_no ?? '';
                        return  $contact_no;
                    },
                ],
                'license_no',
                'rc_number',
                'vehical_owner',
                'vehical_type',
                [
                    'attribute' => 'vehical_id',
                    'label' => Yii::t('app', 'Vehicle'),
                    'value' => function ($model) {
                        return $model->vehical->title ?? '';
                    },
                ],
                'Pan_Number',
                [
                    'attribute' => 'user_id',
                    'format' => 'raw',
                    'label' => Yii::t('app', 'Bank name'),
                    'value' => function ($model) {
                        $bank_name = $model->bankDetail->bank_name ?? '';
                        return $bank_name;
                    },
                ],
                [
                    'attribute' => 'user_id',
                    'format' => 'raw',
                    'label' => Yii::t('app', 'Account number'),
                    'value' => function ($model) {
                        $account_number = $model->bankDetail->account_number ?? '';
                        return  $account_number;
                    },
                ],
                [
                    'attribute' => 'user_id',
                    'format' => 'raw',
                    'label' => Yii::t('app', 'IFSC code'),
                    'value' => function ($model) {
                        $ifsc_code = $model->bankDetail->ifsc_code ?? '';
                        return  $ifsc_code;
                    },
                ],
                'created_on',
            ];
            ?>
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'columns' => $gridColumn,
                'pjax' => true,
                'pjaxSettings' => ['options' => ['id' => 'kv-pjax-container-city']],
                'panel' => [
                    'type' => 'light',
                    'heading' => '<span class="glyphicon glyphicon-book"></span>  ' . Html::encode($this->title),
                ],
                'export' => false,
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
