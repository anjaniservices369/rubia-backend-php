<?php
use kartik\grid\GridView;
use yii\data\ArrayDataProvider;

    $dataProvider = new ArrayDataProvider([
        'allModels' => $model->subscriptions,
        'key' => 'id'
    ]);
    $gridColumns = [
        ['class' => 'yii\grid\SerialColumn'],
        ['attribute' => 'id', 'visible' => false],
        'delivery_date',
        'order_id',
        //'vacation_mode',
        [
            'attribute' => 'vacation_mode',
            'format' => 'raw',
            'value' => function ($model) {
                return $model->getVacationTypeBadges();
            }
        ],
        //'start_date',
        //'end_date',
        //'status',
        [
            'attribute' => 'status',
            'format' => 'raw',
            'value' => function ($model) {
                return $model->getOrderStatusBadges();
            }
        ],
        //'created_on',
        //'updated_on',
        [
            'class' => 'kartik\grid\ActionColumn',
            'controller' => 'subscription'
        ],
    ];
    
    echo GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => $gridColumns,
        'containerOptions' => ['style' => 'overflow: auto'],
        'pjax' => true,
        'beforeHeader' => [
            [
                'options' => ['class' => 'skip-export']
            ]
        ],
        'export' => [
            'fontAwesome' => true
        ],
        'bordered' => true,
        'striped' => true,
        'condensed' => true,
        'responsive' => true,
        'hover' => true,
        'showPageSummary' => false,
        'persistResize' => false,
    ]);
