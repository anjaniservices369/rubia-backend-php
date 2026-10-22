<?php

use app\modules\geo\services\ServiceModules;
use yii\helpers\Html;

/* @var $module int */
/* @var $title string */
/* @var $from string */
/* @var $to string */
/* @var $cityId int|null */
/* @var $totals array */
/* @var $byCity array */
/* @var $byDay array */
/* @var $payouts array|null */
/* @var $wallets array */
/* @var $cities array */

$this->title = $title . ' - billing';
$this->params['breadcrumbs'][] = ['label' => $title, 'url' => ['index', 'module' => $module]];
$this->params['breadcrumbs'][] = 'Billing';
$money = function ($v) {
    return '₹' . number_format((float) $v, 2);
};
?>
<div class="service-map-billing">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h3 class="mb-0">Billing - <?= Html::encode($title) ?></h3>
            <div class="text-muted">Only the money of this service. Other services are billed on their own pages.</div>
        </div>
        <div class="d-flex flex-wrap gap-1">
            <?php foreach (ServiceModules::all() as $id => $m): ?>
                <?= Html::a(Html::encode($m['title']), ['billing', 'module' => $id, 'from' => $from, 'to' => $to], ['class' => 'btn btn-sm ' . ($id == $module ? 'btn-primary' : 'btn-outline-secondary')]) ?>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card mb-3"><div class="card-body">
        <?= Html::beginForm(['billing'], 'get', ['class' => 'row g-2 align-items-end']) ?>
        <?= Html::hiddenInput('module', $module) ?>
        <div class="col-auto"><label class="form-label">From</label><?= Html::input('date', 'from', $from, ['class' => 'form-control']) ?></div>
        <div class="col-auto"><label class="form-label">To</label><?= Html::input('date', 'to', $to, ['class' => 'form-control']) ?></div>
        <div class="col-auto"><label class="form-label">City</label>
            <?= Html::dropDownList('city', $cityId, ['' => 'All cities'] + array_column($cities, 'name', 'id'), ['class' => 'form-select']) ?></div>
        <div class="col-auto"><?= Html::submitButton('Show', ['class' => 'btn btn-primary']) ?></div>
        <div class="col-auto"><?= Html::a('Download CSV', ['billing-csv', 'module' => $module, 'from' => $from, 'to' => $to, 'city' => $cityId], ['class' => 'btn btn-outline-secondary']) ?></div>
        <div class="col-auto"><?= Html::a('Invoices', ['invoices', 'module' => $module, 'from' => $from, 'to' => $to], ['class' => 'btn btn-outline-primary']) ?></div>
        <div class="col-auto"><?= Html::a('Tax report', ['tax-report', 'module' => $module, 'from' => $from, 'to' => $to], ['class' => 'btn btn-outline-primary']) ?></div>
        <div class="col-auto"><?= Html::a('Billing profile', ['billing-profile', 'module' => $module], ['class' => 'btn btn-outline-secondary']) ?></div>
        <?= Html::endForm() ?>
    </div></div>

    <div class="row g-3 mb-3">
        <?php foreach ([['Rides billed', (int) $totals['rides']], ['Billed to customers', $money($totals['gross'])],
            ['Company share', $money($totals['company'])], ['Drivers\' share', $money($totals['driver'])]] as $t): ?>
            <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body">
                <div class="text-muted small"><?= Html::encode($t[0]) ?></div>
                <div class="fs-3 fw-semibold"><?= $t[1] ?></div>
            </div></div></div>
        <?php endforeach; ?>
    </div>
    <div class="card mb-3"><div class="card-body">
        <strong>Wallets of this service</strong> (separate from every other service):
        <?= (int) $wallets['drivers'] ?> driver wallet(s) -
        credits <?= $money($wallets['credit']) ?>, debits <?= $money($wallets['debit']) ?>,
        <strong>held in total <?= $money($wallets['net']) ?></strong>.
    </div></div>
    <?php if ($payouts !== null): ?>
        <p class="text-muted">Payout / withdraw entries of this service's drivers in the period:
            <strong><?= (int) $payouts['entries'] ?></strong> entries, <strong><?= $money($payouts['amount']) ?></strong>
            (see <?= Html::a('payouts', ['/admin/taxi-payout/index', 'vehicle_id' => $module]) ?>).</p>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-6"><div class="card">
            <div class="card-header"><strong>By city</strong></div>
            <div class="card-body p-0"><table class="table table-sm mb-0">
                <thead><tr><th>City</th><th class="text-end">Rides</th><th class="text-end">Billed</th><th class="text-end">Company</th><th class="text-end">Drivers</th></tr></thead>
                <tbody>
                <?php foreach ($byCity as $r): ?>
                    <tr><td><?= Html::encode($r['label']) ?></td><td class="text-end"><?= (int) $r['rides'] ?></td>
                        <td class="text-end"><?= $money($r['gross']) ?></td><td class="text-end"><?= $money($r['company']) ?></td><td class="text-end"><?= $money($r['driver']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$byCity): ?><tr><td colspan="5" class="text-muted">No billed rides in this period.</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </div></div>
        <div class="col-lg-6"><div class="card">
            <div class="card-header"><strong>By day</strong></div>
            <div class="card-body p-0"><table class="table table-sm mb-0">
                <thead><tr><th>Date</th><th class="text-end">Rides</th><th class="text-end">Billed</th><th class="text-end">Company</th><th class="text-end">Drivers</th></tr></thead>
                <tbody>
                <?php foreach ($byDay as $r): ?>
                    <tr><td><?= Html::encode($r['label']) ?></td><td class="text-end"><?= (int) $r['rides'] ?></td>
                        <td class="text-end"><?= $money($r['gross']) ?></td><td class="text-end"><?= $money($r['company']) ?></td><td class="text-end"><?= $money($r['driver']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$byDay): ?><tr><td colspan="5" class="text-muted">No billed rides in this period.</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </div></div>
    </div>
</div>
