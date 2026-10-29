<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $keyId string */
/* @var $secretSet bool */
/* @var $webhookSet bool */
/* @var $fromEnv bool */
/* @var $mode string */
/* @var $fee float */
/* @var $enforce bool */
/* @var $webhookUrl string */
/* @var $orders array */

$this->title = 'Payments';
$this->params['breadcrumbs'][] = $this->title;
$set = function ($ok) {
    return $ok ? '<span class="badge text-bg-success">set</span>' : '<span class="badge text-bg-warning">not set</span>';
};
$badge = ['paid' => 'success', 'created' => 'secondary', 'failed' => 'danger', 'review' => 'warning', 'rejected' => 'danger'];
?>
<div class="payments-index">
    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <div class="alert alert-success"><?= Html::encode(Yii::$app->session->getFlash('success')) ?></div>
    <?php endif; ?>

    <div class="rb-split">
    <div class="rb-main">
    </div>
    <div class="rb-side">
    <div class="card"><div class="card-body">
        <h5>Razorpay</h5>
        <p class="text-muted">The Razorpay keys, webhook address and connection test now live in their own module:
            <?= Html::a('Integrations &rarr; Razorpay', ['/admin/integrations/view', 'id' => 'razorpay']) ?>.
            Mode: <strong><?= $mode ? Html::encode($mode) : 'not configured' ?></strong><?= $fromEnv ? ' (secret from the server environment)' : '' ?>.</p>

        <h5>Partner onboarding</h5>
        <p class="text-muted">Joining fee: <strong>&#8377;<?= Html::encode($fee) ?></strong> &middot; onboarding is <strong><?= $enforce ? 'enforced' : 'not enforced' ?></strong>.
            Change them in <?= Html::a('Integrations &rarr; Driver settings', ['/admin/integrations/view', 'id' => 'driver_rules']) ?>.</p>
    </div></div>
    </div>
    </div>


    <div class="card mt-3"><div class="card-header"><strong>Latest payments</strong></div>
    <div class="table-responsive"><table class="table table-sm table-striped mb-0">
        <thead><tr><th>#</th><th>Partner</th><th>For</th><th class="text-end">Amount</th><th>Gateway</th><th>Status</th><th>Gateway order</th><th>Payment</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
            <tr><td><?= (int) $o['id'] ?></td><td>#<?= (int) $o['user_id'] ?></td><td><?= Html::encode(str_replace('_', ' ', $o['purpose'])) ?></td>
                <td class="text-end">&#8377;<?= number_format((float) $o['amount'], 2) ?></td>
                <td><?= Html::encode(ucfirst(str_replace('_', ' ', (string) $o['gateway']))) ?></td>
                <td><span class="badge text-bg-<?= $badge[$o['status']] ?? 'light' ?>"><?= Html::encode($o['status']) ?></span></td>
                <td><?= Html::encode($o['gateway_order_id']) ?></td><td><?= Html::encode($o['gateway_payment_id']) ?></td>
                <td><?= Html::encode($o['paid_on'] ?: $o['created_on']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$orders): ?><tr><td colspan="9" class="text-muted">No payments yet.</td></tr><?php endif; ?>
        </tbody>
    </table></div></div>
</div>
