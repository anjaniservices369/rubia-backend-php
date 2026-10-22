<?php

use yii\helpers\Html;

/* @var $module int */
/* @var $title string */
/* @var $from string */
/* @var $to string */
/* @var $rows array */
/* @var $profile array */

$this->title = $title . ' - tax report';
$this->params['breadcrumbs'][] = ['label' => $title, 'url' => ['index', 'module' => $module]];
$this->params['breadcrumbs'][] = 'Tax report';
$money = function ($v) {
    return '₹' . number_format((float) $v, 2);
};
$sum = ['invoices' => 0, 'taxable' => 0, 'cgst' => 0, 'sgst' => 0, 'igst' => 0, 'total' => 0, 'commission' => 0];
foreach ($rows as $r) {
    foreach ($sum as $k => $_) {
        $sum[$k] += (float) $r[$k];
    }
}
?>
<div class="card mb-3"><div class="card-body">
    <h4 class="mb-1">Tax report - <?= Html::encode($title) ?></h4>
    <div class="text-muted mb-3">
        <?= Html::encode($profile['legal_name'] ?: '(legal name not set)') ?> · GSTIN <?= Html::encode($profile['gstin'] ?: 'not set') ?>
        · <?= Html::a('edit billing profile', ['billing-profile', 'module' => $module]) ?>
    </div>
    <?= Html::beginForm(['tax-report'], 'get', ['class' => 'row g-2 align-items-end']) ?>
    <?= Html::hiddenInput('module', $module) ?>
    <div class="col-auto"><label class="form-label">From</label><?= Html::input('date', 'from', $from, ['class' => 'form-control']) ?></div>
    <div class="col-auto"><label class="form-label">To</label><?= Html::input('date', 'to', $to, ['class' => 'form-control']) ?></div>
    <div class="col-auto"><?= Html::submitButton('Show', ['class' => 'btn btn-primary']) ?></div>
    <div class="col-auto"><?= Html::a('Download CSV', ['tax-report-csv', 'module' => $module, 'from' => $from, 'to' => $to], ['class' => 'btn btn-outline-secondary']) ?></div>
    <div class="col-auto"><?= Html::a('Invoices', ['invoices', 'module' => $module, 'from' => $from, 'to' => $to], ['class' => 'btn btn-outline-secondary']) ?></div>
    <?= Html::endForm() ?>
</div></div>
<div class="card"><div class="card-body p-0">
    <table class="table table-sm mb-0">
        <thead><tr><th>Month</th><th class="text-end">Invoices</th><th class="text-end">Taxable value</th><th class="text-end">CGST</th><th class="text-end">SGST</th><th class="text-end">IGST</th><th class="text-end">Total</th><th class="text-end">Company share</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr><td><?= Html::encode($r['month']) ?></td><td class="text-end"><?= (int) $r['invoices'] ?></td>
                <td class="text-end"><?= $money($r['taxable']) ?></td><td class="text-end"><?= $money($r['cgst']) ?></td>
                <td class="text-end"><?= $money($r['sgst']) ?></td><td class="text-end"><?= $money($r['igst']) ?></td>
                <td class="text-end"><?= $money($r['total']) ?></td><td class="text-end"><?= $money($r['commission']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="8" class="text-muted">No invoices in this period.</td></tr><?php endif; ?>
        </tbody>
        <?php if ($rows): ?>
        <tfoot><tr class="fw-semibold"><td>Total</td><td class="text-end"><?= (int) $sum['invoices'] ?></td>
            <td class="text-end"><?= $money($sum['taxable']) ?></td><td class="text-end"><?= $money($sum['cgst']) ?></td>
            <td class="text-end"><?= $money($sum['sgst']) ?></td><td class="text-end"><?= $money($sum['igst']) ?></td>
            <td class="text-end"><?= $money($sum['total']) ?></td><td class="text-end"><?= $money($sum['commission']) ?></td></tr></tfoot>
        <?php endif; ?>
    </table>
</div></div>
