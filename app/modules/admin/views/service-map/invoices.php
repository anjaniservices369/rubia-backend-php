<?php

use yii\helpers\Html;

/* @var $module int */
/* @var $title string */
/* @var $from string */
/* @var $to string */
/* @var $q string */
/* @var $rows array */
/* @var $count int */

$this->title = $title . ' - invoices';
$this->params['breadcrumbs'][] = ['label' => $title, 'url' => ['index', 'module' => $module]];
$this->params['breadcrumbs'][] = 'Invoices';
$money = function ($v) {
    return '₹' . number_format((float) $v, 2);
};
?>
<div class="card mb-3"><div class="card-body">
    <h4 class="mb-3">Invoices - <?= Html::encode($title) ?></h4>
    <?= Html::beginForm(['invoices'], 'get', ['class' => 'row g-2 align-items-end']) ?>
    <?= Html::hiddenInput('module', $module) ?>
    <div class="col-auto"><label class="form-label">From</label><?= Html::input('date', 'from', $from, ['class' => 'form-control']) ?></div>
    <div class="col-auto"><label class="form-label">To</label><?= Html::input('date', 'to', $to, ['class' => 'form-control']) ?></div>
    <div class="col-auto"><label class="form-label">Invoice no</label><?= Html::textInput('q', $q, ['class' => 'form-control', 'placeholder' => 'DRV/2026-27']) ?></div>
    <div class="col-auto"><?= Html::submitButton('Show', ['class' => 'btn btn-primary']) ?></div>
    <div class="col-auto"><?= Html::a('Download CSV', ['invoices-csv', 'module' => $module, 'from' => $from, 'to' => $to], ['class' => 'btn btn-outline-secondary']) ?></div>
    <div class="col-auto"><?= Html::a('Tax report', ['tax-report', 'module' => $module, 'from' => $from, 'to' => $to], ['class' => 'btn btn-outline-secondary']) ?></div>
    <?= Html::endForm() ?>
</div></div>
<div class="card"><div class="card-body p-0">
    <table class="table table-sm mb-0">
        <thead><tr><th>Invoice no</th><th>Date</th><th>Source</th><th class="text-end">Taxable</th><th class="text-end">CGST</th><th class="text-end">SGST</th><th class="text-end">Total</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><strong><?= Html::encode($r['number']) ?></strong></td>
                <td><?= Html::encode(substr($r['issued_on'], 0, 10)) ?></td>
                <td><?= Html::encode($r['source_type'] . ' #' . $r['source_id']) ?></td>
                <td class="text-end"><?= $money($r['taxable']) ?></td>
                <td class="text-end"><?= $money($r['cgst']) ?></td>
                <td class="text-end"><?= $money($r['sgst']) ?></td>
                <td class="text-end"><?= $money($r['total']) ?></td>
                <td class="text-end"><?= Html::a('Print', ['invoice', 'module' => $module, 'id' => $r['id']], ['target' => '_blank', 'class' => 'btn btn-sm btn-outline-primary']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="8" class="text-muted">No invoices in this period.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php if ($count > count($rows)): ?><div class="card-footer text-muted">Showing the latest <?= count($rows) ?> of <?= (int) $count ?>; narrow the dates or download the CSV for all.</div><?php endif; ?>
</div>
