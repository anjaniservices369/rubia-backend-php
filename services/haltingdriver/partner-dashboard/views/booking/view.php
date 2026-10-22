<?php

use app\models\User;
use app\modules\admin\models\base\Booking as BaseBooking;
use app\modules\admin\models\base\BookingLogs;
use app\modules\admin\models\base\DriverRequests;
use app\modules\dispatch\services\BookingAdmin;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\Booking */

$this->title = !empty($model->booking_number) ? $model->booking_number : 'Booking ' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Bookings', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$canManage = User::isAdmin() || User::isSubAdmin();
$customer = $model->user;
$driver = $model->driver;
$open = BookingAdmin::isOpen($model);

$bookingId = $model->id;
$who = function ($u) use ($canManage, $bookingId) {
    if (!$u) {
        return '<span class="text-muted">none</span>';
    }
    $name = trim($u->first_name . ' ' . $u->last_name);
    $name = $name !== '' ? $name : $u->username;
    $state = (int) $u->status === User::STATUS_ACTIVE
        ? '<span class="badge text-bg-success">Active</span>'
        : '<span class="badge text-bg-danger">Blocked</span>';
    $out = Html::encode($name) . ' · ' . Html::encode($u->contact_no) . ' ' . $state;
    if ($canManage) {
        $block = (int) $u->status === User::STATUS_ACTIVE;
        $out .= ' ' . Html::beginForm(['users/update-status'], 'post', ['class' => 'd-inline', 'onsubmit' => 'return confirm(' . json_encode($block ? 'Block this account? It will lose app access immediately.' : 'Unblock this account?') . ');'])
            . Html::hiddenInput('id', $u->id)
            . Html::hiddenInput('return_booking', $bookingId)
            . Html::hiddenInput('val', $block ? User::STATUS_BLOCKED : User::STATUS_ACTIVE)
            . Html::submitButton($block ? 'Block' : 'Unblock', ['class' => 'btn btn-sm ' . ($block ? 'btn-outline-danger' : 'btn-outline-success')])
            . Html::endForm();
    }

    return $out;
};

$row = function ($label, $value) {
    return '<tr><th style="width:220px">' . Html::encode($label) . '</th><td>' . $value . '</td></tr>';
};
?>
<div class="booking-view">

    <?php foreach (['success', 'error'] as $kind): ?>
        <?php if ($msg = Yii::$app->session->getFlash($kind)): ?>
            <div class="alert alert-<?= $kind === 'error' ? 'danger' : 'success' ?>"><?= Html::encode($msg) ?></div>
        <?php endif; ?>
    <?php endforeach; ?>

    <div class="card"><div class="card-body d-flex justify-content-between align-items-center">
        <div>
            <h3 class="mb-1"><?= Html::encode($this->title) ?> <?= $model->getStateOptionsBadges() ?></h3>
            <div class="text-muted">Booking #<?= (int) $model->id ?> · created <?= Html::encode($model->created_on) ?></div>
        </div>
        <div>
            <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
            <?php if ($canManage): ?>
                <?= Html::a('Delete', ['delete', 'id' => $model->id], ['class' => 'btn btn-danger',
                    'data' => ['confirm' => 'Are you sure you want to delete this item?', 'method' => 'post']]) ?>
            <?php endif; ?>
        </div>
    </div></div>

    <?php if ($canManage): ?>
    <div class="card">
        <div class="card-header"><strong>Manage this booking</strong></div>
        <div class="card-body">
            <?php if (!$open): ?>
                <p class="text-muted mb-0">This booking is closed (<?= strip_tags($model->getStateOptionsBadges()) ?>), so it can no longer be changed.</p>
            <?php else: ?>
            <div class="row g-3">
                <div class="col-lg-6">
                    <?= Html::beginForm(['manage', 'id' => $model->id], 'post', ['onsubmit' => 'return confirm("Cancel this booking? The customer and driver are notified.");']) ?>
                    <?= Html::hiddenInput('op', 'cancel') ?>
                    <label class="form-label">Cancel booking</label>
                    <div class="input-group">
                        <?= Html::textInput('reason', '', ['class' => 'form-control', 'placeholder' => 'Reason shown to the customer', 'required' => true, 'maxlength' => 200]) ?>
                        <?= Html::submitButton('Cancel booking', ['class' => 'btn btn-danger']) ?>
                    </div>
                    <?= Html::endForm() ?>
                </div>
                <div class="col-lg-6">
                    <?php if ((int) $model->status !== BaseBooking::STATUS_START_RIDE): ?>
                    <?= Html::beginForm(['manage', 'id' => $model->id], 'post', ['onsubmit' => 'return confirm("Send this booking back to drivers?");']) ?>
                    <?= Html::hiddenInput('op', 'reopen') ?>
                    <label class="form-label">Offer to drivers again</label><br>
                    <?= Html::submitButton('Send back to drivers', ['class' => 'btn btn-warning']) ?>
                    <?= Html::endForm() ?>
                    <?php endif; ?>
                </div>
                <?php if ((int) $model->status !== BaseBooking::STATUS_START_RIDE): ?>
                <div class="col-lg-6">
                    <?= Html::beginForm(['manage', 'id' => $model->id], 'post') ?>
                    <?= Html::hiddenInput('op', 'assign') ?>
                    <label class="form-label">Assign a driver</label>
                    <div class="input-group">
                        <?= Html::textInput('driver', '', ['class' => 'form-control', 'placeholder' => 'Driver id or mobile number', 'required' => true, 'maxlength' => 20]) ?>
                        <?= Html::submitButton('Assign', ['class' => 'btn btn-primary']) ?>
                    </div>
                    <?= Html::endForm() ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($model->driver_id)): ?>
                <div class="col-lg-6">
                    <?= Html::beginForm(['manage', 'id' => $model->id], 'post') ?>
                    <?= Html::hiddenInput('op', 'progress') ?>
                    <label class="form-label">Set ride state</label>
                    <div class="input-group">
                        <?= Html::dropDownList('state', $model->status, BookingAdmin::progressStates(), ['class' => 'form-select']) ?>
                        <?= Html::submitButton('Set', ['class' => 'btn btn-secondary']) ?>
                    </div>
                    <div class="form-text">Completing a ride is done by the driver in the app, so fare and commission are recorded.</div>
                    <?= Html::endForm() ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header"><strong>People</strong></div>
        <div class="card-body"><table class="table table-sm mb-0"><tbody>
            <?= $row('Customer', $who($customer)) ?>
            <?= $row('Driver', $who($driver)) ?>
            <?php if (!empty($model->cancel_reason)) { echo $row('Cancel reason', Html::encode($model->cancel_reason)); } ?>
        </tbody></table></div>
    </div>

    <?php
    $logs = BookingLogs::find()->where(['booking_id' => $model->id])->orderBy(['id' => SORT_DESC])->limit(30)->all();
    ?>
    <div class="card">
        <div class="card-header"><strong>History</strong></div>
        <div class="card-body p-0"><table class="table table-sm mb-0">
            <thead><tr><th>What</th><th>When</th></tr></thead><tbody>
            <?php foreach ($logs as $l): ?>
                <tr><td><?= strip_tags((string) $l->description) ?></td><td><?= Html::encode($l->created_on) ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$logs): ?><tr><td colspan="2" class="text-muted">No history yet.</td></tr><?php endif; ?>
        </tbody></table></div>
    </div>

    <?= $this->render('_invoice_view', [
        'model' => $model,
        'providerDriverRequests' => $providerDriverRequests,
        'providerVehicleVerification' => $providerVehicleVerification,
    ]) ?>
</div>
