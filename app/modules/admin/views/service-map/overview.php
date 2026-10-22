<?php

use yii\helpers\Html;

/* @var $rows array */
/* @var $customer array */
/* @var $from string */
/* @var $to string */

$this->title = 'All services - super admin';
$this->params['breadcrumbs'][] = 'All services';
$money = function ($v) {
    return '₹' . number_format((float) $v, 2);
};
$sum = ['rides' => 0, 'gross' => 0, 'company' => 0, 'held' => 0, 'tax' => 0, 'invoices' => 0];
foreach ($rows as $r) {
    if (!empty($r['error'])) {
        continue;
    }
    $sum['rides'] += $r['money']['rides'];
    $sum['gross'] += $r['money']['gross'];
    $sum['company'] += $r['money']['company'];
    $sum['held'] += $r['partner_wallets']['held'];
    $sum['tax'] += $r['invoices']['tax'];
    $sum['invoices'] += $r['invoices']['count'];
}
?>
<div class="service-map-overview">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h3 class="mb-0">All services</h3>
            <div class="text-muted">One place for the owner: every service with its own database, side by side. <?= Html::encode($from) ?> to <?= Html::encode($to) ?>.</div>
        </div>
        <form class="d-flex gap-2" method="get">
            <input type="date" name="from" value="<?= Html::encode($from) ?>" class="form-control form-control-sm">
            <input type="date" name="to" value="<?= Html::encode($to) ?>" class="form-control form-control-sm">
            <button class="btn btn-sm btn-primary">Show</button>
        </form>
    </div>

    <div class="row g-3 mb-3">
        <?php foreach ([['Rides billed', (int) $sum['rides']], ['Billed in total', $money($sum['gross'])], ['Company share', $money($sum['company'])],
            ['Held in partner wallets', $money($sum['held'])], ['Invoices / tax', (int) $sum['invoices'] . ' / ' . $money($sum['tax'])]] as $t): ?>
            <div class="col-6 col-lg"><div class="card h-100"><div class="card-body">
                <div class="text-muted small"><?= Html::encode($t[0]) ?></div>
                <div class="fs-4 fw-semibold"><?= $t[1] ?></div>
            </div></div></div>
        <?php endforeach; ?>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Customer app</strong>
            <span>
                <?= ($customer['source'] ?? '') === 'remote' ? '<span class="badge text-bg-success">own service and database</span>' : '<span class="badge text-bg-secondary">shared process</span>' ?>
                <?php if (!empty($customer['database'])): ?><span class="text-muted small"><?= Html::encode($customer['database']) ?></span><?php endif; ?>
                <?php if (!empty($customer['dashboard'])): ?><?= Html::a('Open its dashboard', $customer['dashboard'], ['class' => 'btn btn-sm btn-outline-primary ms-2', 'target' => '_blank', 'rel' => 'noopener']) ?><?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <?php if (!empty($customer['error'])): ?>
                <span class="text-danger"><?= Html::encode($customer['error']) ?></span>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ([['Customers', (int) $customer['customers']['total']], ['Active', (int) $customer['customers']['active']], ['Blocked', (int) $customer['customers']['blocked']],
                        ['New in period', (int) $customer['customers']['new_in_period']], ['Logins (sessions)', (int) $customer['logins']], ['Notifications', (int) $customer['notifications']]] as $t): ?>
                        <div class="col-6 col-lg-2"><div class="text-muted small"><?= Html::encode($t[0]) ?></div><div class="fs-5 fw-semibold"><?= $t[1] ?></div></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead><tr>
                    <th>Service</th><th>Runs on</th><th class="text-end">Rides today</th><th class="text-end">Waiting</th><th class="text-end">Partners online / total</th>
                    <th class="text-end">Rides billed</th><th class="text-end">Billed</th><th class="text-end">Company share</th>
                    <th class="text-end">Partner wallets held</th><th class="text-end">Invoices</th><th class="text-end">Tax</th><th></th>
                </tr></thead>
                <tbody>
                <?php foreach ($rows as $id => $r): ?>
                    <tr>
                        <td><strong><?= Html::encode($r['title']) ?></strong><?= empty($r['partnerApp']) ? ' <span class="badge text-bg-warning">partner app not built yet</span>' : '' ?></td>
                        <?php if (!empty($r['error'])): ?>
                            <td colspan="10"><span class="badge text-bg-danger">remote</span> <?= Html::encode($r['error']) ?></td>
                        <?php else: ?>
                            <td>
                                <?= $r['source'] === 'remote' ? '<span class="badge text-bg-success">own database</span>' : '<span class="badge text-bg-secondary">shared database</span>' ?>
                                <span class="text-muted small"><?= Html::encode($r['database']) ?></span>
                            </td>
                            <td class="text-end"><?= (int) $r['today']['rides'] ?> (<?= (int) $r['today']['completed'] ?> done)</td>
                            <td class="text-end"><?= (int) $r['today']['waiting'] ?></td>
                            <td class="text-end"><?= (int) $r['partners']['online'] ?> / <?= (int) $r['partners']['total'] ?></td>
                            <td class="text-end"><?= (int) $r['money']['rides'] ?></td>
                            <td class="text-end"><?= $money($r['money']['gross']) ?></td>
                            <td class="text-end"><?= $money($r['money']['company']) ?></td>
                            <td class="text-end"><?= $money($r['partner_wallets']['held']) ?></td>
                            <td class="text-end"><?= (int) $r['invoices']['count'] ?></td>
                            <td class="text-end"><?= $money($r['invoices']['tax']) ?></td>
                        <?php endif; ?>
                        <td class="text-end text-nowrap">
                            <?php if ($r['source'] === 'local'): ?>
                                <?= Html::a('Open', ['index', 'module' => $id], ['class' => 'btn btn-sm btn-outline-primary']) ?>
                            <?php elseif (!empty($r['dashboard'])): ?>
                                <?= Html::a('Open its dashboard', $r['dashboard'], ['class' => 'btn btn-sm btn-outline-primary', 'target' => '_blank', 'rel' => 'noopener']) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <p class="text-muted small mt-2">A service on its own database is read through its signed reporting endpoint; this page never opens its database.</p>
</div>
