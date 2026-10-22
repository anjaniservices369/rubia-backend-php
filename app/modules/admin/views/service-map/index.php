<?php

use app\modules\geo\services\ServiceModules;
use yii\helpers\Html;

/* @var $module int */
/* @var $title string */
/* @var $today array */
/* @var $month array */
/* @var $maps array */
/* @var $cities array */
/* @var $canEdit bool */

$this->title = $title . ' - dashboard';
$this->params['breadcrumbs'][] = $title;
$money = function ($v) {
    return '₹' . number_format((float) $v, 2);
};
$tile = function ($label, $value, $sub = '') {
    return '<div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body">'
        . '<div class="text-muted small">' . Html::encode($label) . '</div>'
        . '<div class="fs-3 fw-semibold">' . $value . '</div>'
        . ($sub !== '' ? '<div class="text-muted small">' . $sub . '</div>' : '') . '</div></div></div>';
};
?>
<div class="service-map-index">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h3 class="mb-0"><?= Html::encode($title) ?></h3>
            <div class="text-muted">Its own maps, pricing, franchises and billing. Nothing here changes the other services.</div>
        </div>
        <div class="d-flex flex-wrap gap-1">
            <?php foreach (ServiceModules::all() as $id => $m): ?>
                <?php if (!empty(Yii::$app->params['serviceDashboard']) && (int) Yii::$app->params['serviceDashboard'] !== $id) { continue; } ?>
                <?= Html::a(Html::encode($m['title']), ['index', 'module' => $id], ['class' => 'btn btn-sm ' . ($id == $module ? 'btn-primary' : 'btn-outline-secondary')]) ?>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <?= $tile('Rides today', (int) $today['rides'], (int) $today['completed'] . ' completed') ?>
        <?= $tile('Waiting for a driver', (int) $today['waiting']) ?>
        <?= $tile('Drivers online', (int) $today['online']) ?>
        <?= $tile('Company share this month', $money($month['company']), 'of ' . $money($month['gross']) . ' billed, ' . (int) $month['rides'] . ' rides') ?>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Maps of <?= Html::encode($title) ?></strong>
            <?= Html::a('Billing of ' . Html::encode($title), ['billing', 'module' => $module], ['class' => 'btn btn-sm btn-outline-primary']) ?>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead><tr><th>City</th><th>Pincodes</th><th>Service area</th><th>Pricing</th><th>Franchise</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($maps as $m): ?>
                    <tr>
                        <td><strong><?= Html::encode($m['name']) ?></strong></td>
                        <td><?= (int) $m['pincodes'] ?></td>
                        <td><?= $m['area'] ? '<span class="badge text-bg-success">saved</span>' : '<span class="badge text-bg-secondary">none</span>' ?></td>
                        <td><?= $m['pricing'] ? (int) $m['pricing'] . ' row(s)' : '<span class="badge text-bg-warning">no pricing</span>' ?></td>
                        <td><?= (int) $m['franchises'] ?: '<span class="text-muted">-</span>' ?></td>
                        <td class="text-end"><?= Html::a('Open map', ['map', 'module' => $module, 'city' => $m['id']], ['class' => 'btn btn-sm btn-primary']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$maps): ?>
                    <tr><td colspan="6" class="text-muted">This service has no map yet. Start one below.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($canEdit): ?>
        <div class="card-footer">
            <?= Html::beginForm(['map'], 'get', ['class' => 'd-flex gap-2 align-items-center flex-wrap']) ?>
            <?= Html::hiddenInput('module', $module) ?>
            <label class="mb-0">Start a map in a city:</label>
            <?= Html::dropDownList('city', '', ['' => 'Choose a city...'] + array_column($cities, 'name', 'id'), ['class' => 'form-select', 'style' => 'max-width:300px', 'required' => true]) ?>
            <?= Html::submitButton('Open', ['class' => 'btn btn-outline-primary']) ?>
            <?= Html::endForm() ?>
        </div>
        <?php endif; ?>
    </div>
</div>
