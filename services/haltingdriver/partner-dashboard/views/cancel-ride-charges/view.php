<?php

use app\modules\customerapi\services\CustomerClient;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\base\CancelRideCharges */

$this->title = 'Cancellation charge #' . (int) $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Cancellation charges', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$money = function ($v) {
    return '₹' . number_format((float) $v, 2);
};
$customer = CustomerClient::profileCached($model->user_id);
$company = (float) $model->admin_cancellation_earnings;
$charge = (float) $model->cancel_charge;
?>
<div class="cancel-ride-charges-view">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"><?= Html::encode($this->title) ?></h3>
        <?= Html::a('Back to the list', ['index'], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
    </div>
    <div class="card"><div class="card-body">
        <table class="table table-sm mb-0">
            <tr><th style="width:240px">Booking</th><td><?= Html::a('#' . (int) $model->booking_id, ['/admin/booking/view', 'id' => $model->booking_id]) ?></td></tr>
            <tr><th>Customer</th><td><?= Html::encode($customer['name'] ?: ('#' . (int) $model->user_id)) ?> <?= $customer['phone'] ? '· ' . Html::encode($customer['phone']) : '' ?></td></tr>
            <tr><th>Driver</th><td><?= $model->driver_id ? '#' . (int) $model->driver_id : '-' ?></td></tr>
            <tr><th>Cancelled by</th><td><?= $model->cancel_role === 'user' ? 'Customer' : Html::encode(ucfirst((string) $model->cancel_role)) ?></td></tr>
            <tr><th>Reason</th><td><?= nl2br(Html::encode($model->cancel_reason)) ?></td></tr>
            <tr><th>Charge</th><td><?= $money($charge) ?></td></tr>
            <tr><th>Company share</th><td><?= $money($company) ?></td></tr>
            <tr><th>Driver gets</th><td><?= $money(max(0, $charge - $company)) ?></td></tr>
            <tr><th>Status</th><td><?= Html::encode($model->status) ?><?= $model->status === 'pending' ? ' (collected with the customer\'s next ride)' : '' ?></td></tr>
            <?php if ($model->settlement_booking_id): ?>
                <tr><th>Collected with booking</th><td><?= Html::a('#' . (int) $model->settlement_booking_id, ['/admin/booking/view', 'id' => $model->settlement_booking_id]) ?></td></tr>
            <?php endif; ?>
            <tr><th>Date</th><td><?= Html::encode($model->created_date) ?></td></tr>
        </table>
    </div></div>
</div>
