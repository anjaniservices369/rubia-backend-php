<?php
/* @var $this yii\web\View */
/* @var $searchModel app\modules\admin\models\search\VehicalsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Vehicles';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="vehicals-index">
    <p><?= Html::a('Create Vehicle', ['create'], ['class' => 'btn btn-success']) ?></p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            'id',
            'title',
            'base_fare',
            'min_distance',
            'fare_per_distance',
            'fare_per_minute',
            'cancellation_fee',
            'admin_commison',
            'no_of_person',
            [
                'attribute' => 'status',
                'value' => function ($m) {
                    return (int) $m->status === 1 ? 'Active' : 'Inactive';
                },
                'filter' => [1 => 'Active', 2 => 'Inactive'],
            ],
            ['class' => 'yii\grid\ActionColumn', 'template' => '{view} {update}'],
        ],
    ]) ?>
</div>