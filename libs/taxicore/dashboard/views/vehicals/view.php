<?php
/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\Vehicals */

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Vehicles', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="vehicals-view">
    <p><?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?></p>
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id', 'title', 'vehicle_type', 'no_of_person', 'base_fare', 'min_distance', 'fare_per_distance',
            'waiting_time_limit', 'charges_per_minute', 'fare_per_minute', 'cancellation_fee',
            'admin_commison_type', 'admin_commison', 'status', 'created_on', 'updated_on',
        ],
    ]) ?>
</div>