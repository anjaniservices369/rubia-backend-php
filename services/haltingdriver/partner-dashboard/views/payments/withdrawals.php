<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $waiting array */
/* @var $done array */

$this->title = 'Withdrawals';
$this->params['breadcrumbs'][] = ['label' => 'Payments', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$name = function ($r) {
    return trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')) ?: ('#' . $r['driver_id']);
};
?>
<div class="payments-withdrawals">
    <?php foreach (['success' => 'success', 'error' => 'warning'] as $key => $cls): ?>
        <?php if (Yii::$app->session->hasFlash($key)): ?>
            <div class="alert alert-<?= $cls ?>"><?= Html::encode(Yii::$app->session->getFlash($key)) ?></div>
        <?php endif; ?>
    <?php endforeach; ?>

    <p class="text-muted">Each request is paid to the bank account the driver registered in the app. Transfer the amount to that account, then press
        <strong>Mark paid</strong> and enter the bank reference (UTR). <strong>Reject</strong> returns the amount to the driver's balance.</p>

    <div class="card"><div class="card-header"><strong>Waiting to be paid (<?= count($waiting) ?>)</strong></div>
    <div class="table-responsive"><table class="table table-sm table-striped mb-0">
        <thead><tr><th>#</th><th>Driver</th><th class="text-end">Amount</th><th>Pay to (driver's bank details)</th><th>Requested</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($waiting as $w): ?>
            <tr>
                <td><?= (int) $w['id'] ?></td>
                <td><?= Html::encode($name($w)) ?><br><small class="text-muted"><?= Html::encode($w['contact_no']) ?></small></td>
                <td class="text-end">&#8377;<?= number_format((float) $w['amount'], 2) ?></td>
                <td>
                    <?php if (!empty($w['account_number'])): ?>
                        <strong><?= Html::encode($w['account_holder_name']) ?></strong><br>
                        A/C <code><?= Html::encode($w['account_number']) ?></code> &middot; IFSC <code><?= Html::encode($w['ifsc']) ?></code><br>
                        <?= Html::encode($w['bank_name']) ?><?= $w['branch_name'] ? ', ' . Html::encode($w['branch_name']) : '' ?>
                        <?= $w['upi'] ? '<br>UPI <code>' . Html::encode($w['upi']) . '</code>' : '' ?>
                    <?php else: ?>
                        <span class="text-danger">No bank details on file</span>
                    <?php endif; ?>
                </td>
                <td><?= Html::encode($w['created_on']) ?></td>
                <td class="text-nowrap">
                    <form method="post" action="<?= Html::encode(Yii::$app->urlManager->createUrl(['/admin/payments/settle'])) ?>" class="d-inline-flex gap-1">
                        <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">
                        <input type="hidden" name="id" value="<?= (int) $w['id'] ?>">
                        <input type="hidden" name="decision" value="paid">
                        <input name="reference" class="form-control form-control-sm" style="width:11rem" placeholder="Bank reference (UTR)" required minlength="6" maxlength="40" autocomplete="off">
                        <button class="btn btn-sm btn-success">Mark paid</button>
                    </form>
                    <form method="post" action="<?= Html::encode(Yii::$app->urlManager->createUrl(['/admin/payments/settle'])) ?>" class="d-inline"
                          onsubmit="return confirm('Reject this withdrawal? The amount goes back to the driver\'s balance.')">
                        <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">
                        <input type="hidden" name="id" value="<?= (int) $w['id'] ?>">
                        <input type="hidden" name="decision" value="reject">
                        <button class="btn btn-sm btn-outline-danger">Reject</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$waiting): ?><tr><td colspan="6" class="text-muted">No withdrawal is waiting.</td></tr><?php endif; ?>
        </tbody>
    </table></div></div>

    <div class="card mt-3"><div class="card-header"><strong>Recently settled</strong></div>
    <div class="table-responsive"><table class="table table-sm table-striped mb-0">
        <thead><tr><th>#</th><th>Driver</th><th class="text-end">Amount</th><th>Result</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($done as $w): ?>
            <tr><td><?= (int) $w['id'] ?></td><td><?= Html::encode($name($w)) ?></td><td class="text-end">&#8377;<?= number_format((float) $w['amount'], 2) ?></td>
                <td><span class="badge text-bg-<?= (int) $w['status'] === 1 ? 'success' : 'danger' ?>"><?= (int) $w['status'] === 1 ? 'Paid' : 'Rejected' ?></span>
                    <small class="text-muted"><?= Html::encode($w['method_reason']) ?></small></td>
                <td><?= Html::encode($w['updated_on']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$done): ?><tr><td colspan="5" class="text-muted">Nothing yet.</td></tr><?php endif; ?>
        </tbody>
    </table></div></div>
</div>
