<?php

use app\models\User;
use app\modules\admin\models\RideEarnings;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\Pjax;
use kartik\grid\GridView;
use app\modules\admin\widgets\BoxGridView;
use app\modules\admin\widgets\LinkedColumn;
use app\modules\admin\Module as AdminModule;
use kartik\switchinput\SwitchInput;
use kartik\export\ExportMenu; // Import the ExportMenu class

/* @var $this yii\web\View */
/* @var $searchModel app\models\UserSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Users';
$this->params['breadcrumbs'][] = $this->title;

$js = <<< JS
    // ... your existing JS code
JS;

$this->registerJs($js, \yii\web\View::POS_READY);

?>
<?php $this->beginBlock('content-title'); ?>
    <?= Html::a('Add New', ['create'], ['class' => 'btn btn-sm btn-success']) ?>
    <!-- Add ExportMenu widget below -->
    <?= ExportMenu::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'id',
            'first_name',
            'contact_no',
            // ... add more columns as needed
        ],
        'filename' => 'Users_' . date('YmdHis'),
        'showConfirmAlert' => false,
        'target' => ExportMenu::TARGET_SELF,
        'pjaxContainerId' => 'kv-pjax-container',
        'exportConfig' => [
            ExportMenu::FORMAT_CSV => false,
            ExportMenu::FORMAT_HTML => false,
            ExportMenu::FORMAT_TEXT => false,
            ExportMenu::FORMAT_PDF => [
                'pdfConfig' => [
                    'methods' => [
                        'SetTitle' => 'Users',
                        'SetSubject' => 'Users',
                        'SetHeader' => ['Users||Generated On: ' . date("r")],
                        'SetFooter' => ['|Page {PAGENO}|'],
                        'SetAuthor' => 'Your Name',
                    ]
                ]
            ],
        ],
        'dropdownOptions' => [
            'label' => 'Export',
            'class' => 'btn btn-secondary text-white',
            'itemsBefore' => [
                '<div class="dropdown-header">Export All Data</div>',
            ],
        ],
    ]);
    ?>
<?php $this->endBlock(); ?>

<div class="user-index">

    <div class="card">
        <div class="card-body">

            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel'  => $searchModel,
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],
                    'id',
                    [
                        'class' => LinkedColumn::class,
                        'header' => '<a href="#">Name</a>',
                        'attribute' => 'first_name',
                        'value' => 'first_name',
                    ],
                    'contact_no',
                    [
                        'attribute' => 'availabe_amount',
                        'value' => function ($model) {
                            return (new RideEarnings())->availableAmount($model->id);
                        },
                        'pageSummary' => true,
                    ],
                    // ... more columns ...
                    [
                        'class' => 'kartik\grid\ActionColumn',
                        'template' => '{view}',
                    ],
                ],
            ]); ?>

        </div>
    </div>
</div>
