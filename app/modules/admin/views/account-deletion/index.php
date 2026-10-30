<?php

use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Account deletion audit';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="account-deletion-index">
    <div class="card">
        <div class="card-body">
            <h1><?= Html::encode($this->title) ?></h1>
            <p>Deletion request history stored in the customer service database. Personal names, phone numbers and reasons are not shown here.</p>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'emptyText' => 'No account deletion requests found.',
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],
                    'id',
                    'role',
                    'source',
                    [
                        'attribute' => 'status',
                        'value' => static function ($row) {
                            return (int) $row['status'] === 1 ? 'Completed' : 'Pending';
                        },
                    ],
                    'requested_on',
                    'completed_on',
                ],
            ]) ?>
        </div>
    </div>
</div>
