<?php

use app\modules\admin\models\base\CancelRideCharges;
use app\modules\customerapi\services\CustomerClient;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel app\modules\admin\models\search\CancelRideChargesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $totals array */

$this->title = 'Cancellation charges';
$this->params['breadcrumbs'][] = $this->title;
$money = function ($v) {
    return '₹' . number_format((float) $v, 2);
};
$badge = function ($status) {
    $c = ['pending' => 'warning', 'completed' => 'success', 'rejected' => 'secondary'][$status] ?? 'light';

    return '<span class="badge text-bg-' . $c . '">' . Html::encode($status) . '</span>';
};
?>
<div class="cancel-ride-charges-index">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h3 class="mb-0">Cancellation charges</h3>
            <div class="text-muted">A booking cancelled after a driver accepted it. The charge is recorded against the customer, part goes to the
                driver at once, and the rest is collected with the customer's next ride.</div>
        </div>
        <?= Html::a('Download CSV', array_merge(['csv'], Yii::$app->request->queryParams), ['class' => 'btn btn-sm btn-outline-secondary']) ?>
    </div>

    <div class="row g-3 mb-3">
        <?php foreach (['pending' => 'Waiting to be collected', 'completed' => 'Collected', 'rejected' => 'Rejected'] as $key => $label):
            $t = $totals[$key] ?? ['count' => 0, 'charge' => 0, 'company' => 0]; ?>
            <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body">
                <div class="text-muted small"><?= Html::encode($label) ?></div>
                <div class="fs-4 fw-semibold"><?= $money($t['charge']) ?></div>
                <div class="text-muted small"><?= (int) $t['count'] ?> charge(s), company share <?= $money($t['company']) ?></div>
            </div></div></div>
        <?php endforeach; ?>
    </div>

    <form method="get" class="row g-2 align-items-end mb-3">
        <div class="col-6 col-md-2"><label class="form-label small mb-0">Status</label>
            <?= Html::activeDropDownList($searchModel, 'status', ['' => 'All', 'pending' => 'Pending', 'completed' => 'Collected', 'rejected' => 'Rejected'], ['class' => 'form-select form-select-sm']) ?></div>
        <div class="col-6 col-md-2"><label class="form-label small mb-0">Cancelled by</label>
            <?= Html::activeDropDownList($searchModel, 'cancel_role', ['' => 'All', 'user' => 'Customer', 'driver' => 'Driver'], ['class' => 'form-select form-select-sm']) ?></div>
        <div class="col-6 col-md-2"><label class="form-label small mb-0">Booking</label>
            <?= Html::activeTextInput($searchModel, 'booking_id', ['class' => 'form-control form-control-sm']) ?></div>
        <div class="col-6 col-md-2"><label class="form-label small mb-0">Customer id</label>
            <?= Html::activeTextInput($searchModel, 'user_id', ['class' => 'form-control form-control-sm']) ?></div>
        <div class="col-6 col-md-2"><label class="form-label small mb-0">Driver id</label>
            <?= Html::activeTextInput($searchModel, 'driver_id', ['class' => 'form-control form-control-sm']) ?></div>
        <div class="col-6 col-md-2"><label class="form-label small mb-0">From</label>
            <?= Html::activeInput('date', $searchModel, 'from', ['class' => 'form-control form-control-sm']) ?></div>
        <div class="col-6 col-md-2"><label class="form-label small mb-0">To</label>
            <?= Html::activeInput('date', $searchModel, 'to', ['class' => 'form-control form-control-sm']) ?></div>
        <div class="col-auto"><button class="btn btn-sm btn-primary">Filter</button>
            <?= Html::a('Clear', ['index'], ['class' => 'btn btn-sm btn-outline-secondary']) ?></div>
    </form>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-sm table-striped align-middle'],
        'layout' => "{items}\n{summary}\n{pager}",
        'columns' => [
            'id',
            ['attribute' => 'booking_id', 'label' => 'Booking', 'format' => 'raw', 'value' => function ($m) {
                return Html::a('#' . (int) $m->booking_id, ['/admin/booking/view', 'id' => $m->booking_id]);
            }],
            ['label' => 'Customer', 'value' => function ($m) {
                // the customer belongs to the customer app: asked through its API
                $p = CustomerClient::profileCached($m->user_id);

                return ($p['name'] ?: ('#' . (int) $m->user_id)) . ($p['phone'] ? ' · ' . $p['phone'] : '');
            }],
            ['attribute' => 'driver_id', 'label' => 'Driver', 'value' => function ($m) {
                return $m->driver_id ? '#' . (int) $m->driver_id : '-';
            }],
            ['attribute' => 'cancel_role', 'label' => 'Cancelled by', 'value' => function ($m) {
                return $m->cancel_role === 'user' ? 'Customer' : ucfirst((string) $m->cancel_role);
            }],
            ['attribute' => 'cancel_reason', 'label' => 'Reason'],
            ['attribute' => 'cancel_charge', 'label' => 'Charge', 'contentOptions' => ['class' => 'text-end'], 'value' => function ($m) use ($money) {
                return $money($m->cancel_charge);
            }],
            ['attribute' => 'admin_cancellation_earnings', 'label' => 'Company share', 'contentOptions' => ['class' => 'text-end'], 'value' => function ($m) use ($money) {
                return $money($m->admin_cancellation_earnings);
            }],
            ['label' => 'Driver gets', 'contentOptions' => ['class' => 'text-end'], 'value' => function ($m) use ($money) {
                return $money(max(0, (float) $m->cancel_charge - (float) $m->admin_cancellation_earnings));
            }],
            ['attribute' => 'status', 'format' => 'raw', 'value' => function ($m) use ($badge) {
                return $badge($m->status);
            }],
            ['attribute' => 'created_date', 'label' => 'Date'],
            ['class' => 'yii\grid\ActionColumn', 'template' => '{view}'],
        ],
    ]); ?>
</div>
