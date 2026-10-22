<?php

use app\modules\geo\services\GoogleKeys;
use app\modules\geo\services\ServiceModules;
use yii\helpers\Html;
use yii\helpers\Json;

/* @var $module int */
/* @var $title string */
/* @var $city array */
/* @var $areas array result of PincodeAreas::forCity() */
/* @var $map array result of MapDesigner::snapshot() */
/* @var $current array|null the saved outline of this module in this city */
/* @var $canEdit bool */

$this->title = $title . ' map - ' . $city['name'];
$this->params['breadcrumbs'][] = ['label' => $title, 'url' => ['index', 'module' => $module]];
$this->params['breadcrumbs'][] = $city['name'];

$currentPoints = array_map(function ($p) {
    return ['lat' => (float) $p[0], 'lng' => (float) $p[1]];
}, $current ?: []);
$outline = array_map(function ($p) {
    return ['lat' => (float) $p[0], 'lng' => (float) $p[1]];
}, $areas['hull']);

$statusText = function ($p) {
    if (!$p['asked']) {
        return '<span class="badge text-bg-secondary">not looked up (Google key?)</span>';
    }
    if (!$p['found']) {
        return '<span class="badge text-bg-danger">not found on the map</span>';
    }

    return $p['outlier']
        ? '<span class="badge text-bg-warning">far from the others - maybe the wrong city</span>'
        : '<span class="badge text-bg-success">placed</span>';
};
$located = [];
foreach ($areas['pincodes'] as $p) {
    $located[$p['pincode']] = $p;
}
$actUrl = ['act', 'module' => $module, 'city' => (int) $city['id']];
$act = function ($op, array $fields, $label, $class, $confirm = null) use ($actUrl) {
    $html = Html::beginForm($actUrl, 'post', ['class' => 'd-inline', 'onsubmit' => $confirm ? 'return confirm(' . json_encode($confirm) . ');' : null]);
    $html .= Html::hiddenInput('op', $op);
    foreach ($fields as $k => $v) {
        $html .= Html::hiddenInput($k, $v);
    }

    return $html . Html::submitButton($label, ['class' => 'btn btn-sm ' . $class]) . Html::endForm();
};
$cityOptions = ['' => 'Move to...'];
foreach ($map['cities'] as $c) {
    $cityOptions[$c['id']] = $c['name'];
}
?>
<div class="service-map-map">

    <?php foreach (['success', 'error'] as $kind): ?>
        <?php if ($msg = Yii::$app->session->getFlash($kind)): ?>
            <div class="alert alert-<?= $kind === 'error' ? 'danger' : 'success' ?>"><?= Html::encode($msg) ?></div>
        <?php endif; ?>
    <?php endforeach; ?>

    <div class="card">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h3 class="mb-1"><?= Html::encode($title) ?> - <?= Html::encode($city['name']) ?></h3>
                <div class="text-muted">
                    This map belongs to the <strong><?= Html::encode($title) ?></strong> only: its pincodes, outline, pricing and franchise
                    do not touch the other services.
                    <?= (int) $areas['found'] ?> pincode(s) placed, <?= (int) $areas['missing'] ?> not found,
                    <?= (int) $areas['outliers'] ?> far from the others.
                    Green outline = what these pincodes make; blue = the service area saved today.
                </div>
            </div>
            <?php if ($canEdit): ?>
            <div class="d-flex gap-2">
                <?= Html::beginForm(['map', 'module' => $module, 'city' => (int) $city['id']], 'post', ['class' => 'd-inline']) ?>
                <?= Html::hiddenInput('refresh', 1) ?>
                <?= Html::submitButton('Look the pincodes up again', ['class' => 'btn btn-outline-secondary']) ?>
                <?= Html::endForm() ?>
                <?= Html::beginForm(['map', 'module' => $module, 'city' => (int) $city['id']], 'post', [
                    'class' => 'd-inline',
                    'onsubmit' => 'return confirm("Use the green outline as the service area of ' . Html::encode(addslashes($title)) . ' in ' . Html::encode(addslashes($city['name'])) . '? Customers outside it will not be able to book this service.");',
                ]) ?>
                <?= Html::hiddenInput('save', 1) ?>
                <?= Html::submitButton('Use this as the service area', ['class' => 'btn btn-primary', 'disabled' => count($outline) < 3]) ?>
                <?= Html::endForm() ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($areas['outliers'] > 0): ?>
        <div class="alert alert-warning">
            Some pincodes are far from the others of this city. They are <strong>left out</strong> of the green outline;
            they are probably filed under the wrong city - move them below.
        </div>
    <?php endif; ?>

    <div class="card"><div class="card-body p-0"><div id="map" style="height: 480px; width: 100%;"></div></div></div>

    <div class="card">
        <div class="card-header"><strong>1. Pincodes</strong>
            <span class="text-muted">- this is how the <?= Html::encode($title) ?> city is designed</span></div>
        <div class="card-body">
            <?php if ($canEdit): ?>
            <?= Html::beginForm($actUrl, 'post', ['class' => 'row g-2 align-items-end mb-3']) ?>
            <?= Html::hiddenInput('op', 'pincode-add') ?>
            <div class="col-lg-5">
                <label class="form-label">Add pincodes (6 digits, separated by commas or spaces)</label>
                <?= Html::textInput('pincodes', '', ['class' => 'form-control', 'placeholder' => '500081, 500072, 503001', 'required' => true]) ?>
            </div>
            <div class="col-lg-5">
                <?php if ($map['taxi']): ?>
                    <label class="form-label d-block">For which vehicles</label>
                    <?php foreach ($map['vehicles'] as $vid => $vt): ?>
                        <label class="me-3"><?= Html::checkbox('vehicles[]', true, ['value' => $vid]) ?> <?= Html::encode($vt) ?></label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="form-text">The driver service is sold for the whole pincode.</div>
                <?php endif; ?>
            </div>
            <div class="col-lg-2"><?= Html::submitButton('Add to this map', ['class' => 'btn btn-primary w-100']) ?></div>
            <?= Html::endForm() ?>
            <?php endif; ?>

            <table class="table table-sm mb-0">
                <thead><tr><th>Pincode</th><?php if ($map['taxi']): ?><th>Vehicles</th><?php endif; ?><th>Place Google found</th><th>On the map</th><?php if ($canEdit): ?><th style="width:380px">Change</th><?php endif; ?></tr></thead>
                <tbody>
                <?php foreach ($map['pincodes'] as $code => $info): $loc = $located[$code] ?? null; ?>
                    <tr>
                        <td><strong><?= Html::encode($code) ?></strong></td>
                        <?php if ($map['taxi']): ?>
                            <td><?php foreach ($info['vehicles'] as $v) { echo '<span class="badge text-bg-light border me-1">' . Html::encode($v) . '</span>'; } ?></td>
                        <?php endif; ?>
                        <td><?= Html::encode($loc['address'] ?? '') ?></td>
                        <td><?= $loc ? $statusText($loc) : '<span class="badge text-bg-secondary">inactive</span>' ?></td>
                        <?php if ($canEdit): ?>
                        <td>
                            <?= Html::beginForm($actUrl, 'post', ['class' => 'd-inline-flex gap-1',
                                'onsubmit' => 'return confirm("Move pincode ' . Html::encode($code) . '? For the ' . Html::encode(addslashes($title)) . ' its pricing, commission and franchise become those of the new city.");']) ?>
                            <?= Html::hiddenInput('op', 'pincode-move') ?>
                            <?= Html::hiddenInput('pincode', $code) ?>
                            <?= Html::dropDownList('to_city', '', $cityOptions, ['class' => 'form-select form-select-sm', 'required' => true, 'style' => 'width:170px']) ?>
                            <?= Html::submitButton('Move', ['class' => 'btn btn-sm btn-outline-primary']) ?>
                            <?= Html::endForm() ?>
                            <?= $act('pincode-remove', ['pincode' => $code], 'Remove', 'btn-outline-danger', 'Remove pincode ' . $code . ' from this map?') ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$map['pincodes']): ?>
                    <tr><td colspan="5" class="text-muted">No pincodes yet. Add the ones this service should cover in this city.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>2. Pricing of this map</strong>
            <span class="text-muted">- <?= Html::encode($title) ?> rides picked up here are priced from this only</span></div>
        <div class="card-body">
            <?php if ($map['pricing']): ?>
                <?php if ($map['taxi']): ?>
                    <table class="table table-sm mb-2">
                        <thead><tr><th>Base fare</th><th>Min km</th><th>Per km</th><th>Per minute</th><th>Commission</th></tr></thead>
                        <tbody>
                        <?php foreach ($map['pricing'] as $r): ?>
                            <tr><td>₹<?= Html::encode($r['base_fare']) ?></td><td><?= Html::encode($r['min_distance']) ?></td>
                                <td>₹<?= Html::encode($r['fare_per_distance']) ?></td><td>₹<?= Html::encode($r['fare_per_minute']) ?></td>
                                <td><?= Html::encode($r['commision']) ?>%</td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?= Html::a('Edit these fares', ['/admin/ride-commision/index', 'vehicle_id' => $module], ['class' => 'btn btn-sm btn-outline-primary']) ?>
                <?php else: ?>
                    <table class="table table-sm mb-2">
                        <thead><tr><th>Trip</th><th>Base hours</th><th>Base km</th><th>Base fare</th><th>Extra / km</th><th>Extra / min</th><th>Night</th></tr></thead>
                        <tbody>
                        <?php foreach ($map['pricing'] as $r): ?>
                            <tr><td><?= $r['trip_type'] == 1 ? 'Inner city' : 'Outstation' ?> · <?= $r['ride_type'] == 1 ? 'One way' : 'Round trip' ?></td>
                                <td><?= Html::encode($r['base_hour']) ?></td><td><?= Html::encode($r['base_km']) ?></td>
                                <td>₹<?= Html::encode($r['base_fare']) ?></td><td>₹<?= Html::encode($r['extra_km_charge']) ?></td>
                                <td>₹<?= Html::encode($r['extra_min_charge']) ?></td><td>₹<?= Html::encode($r['night_charge']) ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?= Html::a('Edit the rate card', ['/admin/city-rate-card/index'], ['class' => 'btn btn-sm btn-outline-primary']) ?>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-warning mb-3">
                    <?= Html::encode($title) ?> has <strong>no pricing</strong> in <?= Html::encode($city['name']) ?>, so a customer here would get no fare.
                    Start from another city's <?= Html::encode($title) ?> pricing and then edit it.
                </div>
                <?php if ($canEdit && $map['pricingSources']): ?>
                    <?= Html::beginForm($actUrl, 'post', ['class' => 'd-flex gap-2',
                        'onsubmit' => 'return confirm("Copy that city\'s ' . Html::encode(addslashes($title)) . ' pricing into ' . Html::encode(addslashes($city['name'])) . '?");']) ?>
                    <?= Html::hiddenInput('op', 'pricing-copy') ?>
                    <?= Html::dropDownList('from_city', '', ['' => 'Copy pricing from...'] + array_column($map['pricingSources'], 'name', 'id'),
                        ['class' => 'form-select', 'style' => 'max-width:320px', 'required' => true]) ?>
                    <?= Html::submitButton('Copy pricing', ['class' => 'btn btn-primary']) ?>
                    <?= Html::endForm() ?>
                <?php elseif ($canEdit): ?>
                    <div class="text-muted">No other city has <?= Html::encode($title) ?> pricing to copy yet; create it from the fares page.</div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>3. Franchise of this map</strong>
            <span class="text-muted">- sees only the <?= Html::encode($title) ?> of <?= Html::encode($city['name']) ?></span></div>
        <div class="card-body">
            <table class="table table-sm">
                <tbody>
                <?php foreach ($map['franchises'] as $f): ?>
                    <tr>
                        <td><?= Html::encode(trim($f->first_name . ' ' . $f->last_name) ?: $f->username) ?></td>
                        <td><?= Html::encode($f->contact_no) ?></td>
                        <td><?= (int) $f->status === 10 ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-danger">Blocked</span>' ?></td>
                        <?php if ($canEdit): ?>
                        <td class="text-end"><?= $act('franchise-remove', ['user_id' => $f->id], 'Remove from this map', 'btn-outline-danger', 'This franchise will no longer run this map. Continue?') ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$map['franchises']): ?>
                    <tr><td class="text-muted">No franchise runs this map yet. Only admins see it.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
            <?php if ($canEdit && $map['availableFranchises']): ?>
                <?= Html::beginForm($actUrl, 'post', ['class' => 'd-flex gap-2']) ?>
                <?= Html::hiddenInput('op', 'franchise-assign') ?>
                <?php
                $opts = ['' => 'Choose a franchise account...'];
                foreach ($map['availableFranchises'] as $f) {
                    $opts[$f->id] = (trim($f->first_name . ' ' . $f->last_name) ?: $f->username) . ' · ' . $f->contact_no
                        . ($f->city_id ? ' (now runs another map)' : ' (no map yet)');
                }
                ?>
                <?= Html::dropDownList('user_id', '', $opts, ['class' => 'form-select', 'style' => 'max-width:420px', 'required' => true]) ?>
                <?= Html::submitButton('Let this franchise run this map', ['class' => 'btn btn-primary']) ?>
                <?= Html::endForm() ?>
            <?php endif; ?>
            <div class="form-text mt-2">New franchise accounts are created under <?= Html::a('Franchise', ['/admin/users/franchise']) ?>.</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>4. Billing</strong></div>
        <div class="card-body">
            The <?= Html::encode($title) ?> is billed on its own:
            <?= Html::a('open the billing of ' . Html::encode($title), ['billing', 'module' => $module, 'city' => (int) $city['id']]) ?>.
        </div>
    </div>

    <?php if ($map['log']): ?>
    <div class="card">
        <div class="card-header"><strong>Recent changes to this map</strong></div>
        <div class="card-body p-0"><table class="table table-sm mb-0"><tbody>
            <?php foreach ($map['log'] as $l): ?>
                <tr><td><?= Html::encode($l['detail']) ?></td><td class="text-muted text-nowrap"><?= Html::encode($l['created_on']) ?></td></tr>
            <?php endforeach; ?>
        </tbody></table></div>
    </div>
    <?php endif; ?>
</div>

<script>
var PIN_DATA = <?= Json::htmlEncode([
    'pins' => array_values(array_filter($areas['pincodes'], function ($p) { return $p['found']; })),
    'outline' => $outline,
    'current' => $currentPoints,
]) ?>;

function initPincodeMap() {
    var map = new google.maps.Map(document.getElementById('map'), {zoom: 6, center: {lat: 17.4, lng: 78.4}, mapTypeId: 'roadmap'});
    var bounds = new google.maps.LatLngBounds();
    var info = new google.maps.InfoWindow();
    PIN_DATA.pins.forEach(function (p) {
        var pos = {lat: p.lat, lng: p.lng};
        var marker = new google.maps.Marker({position: pos, map: map, title: p.pincode,
            icon: 'http://maps.google.com/mapfiles/ms/icons/' + (p.outlier ? 'orange' : 'green') + '-dot.png'});
        marker.addListener('click', function () {
            info.setContent('<strong>' + p.pincode + '</strong><br>' + (p.address || '') + (p.outlier ? '<br><em>far from the others</em>' : ''));
            info.open(map, marker);
        });
        if (p.box) {
            new google.maps.Rectangle({map: map, strokeColor: p.outlier ? '#fd7e14' : '#198754', strokeOpacity: 0.7, strokeWeight: 1,
                fillColor: p.outlier ? '#fd7e14' : '#198754', fillOpacity: 0.12,
                bounds: {south: p.box[0], west: p.box[1], north: p.box[2], east: p.box[3]}});
            bounds.extend({lat: p.box[0], lng: p.box[1]});
            bounds.extend({lat: p.box[2], lng: p.box[3]});
        }
        bounds.extend(pos);
    });
    if (PIN_DATA.current.length > 2) {
        new google.maps.Polygon({map: map, paths: PIN_DATA.current, strokeColor: '#0d6efd', strokeWeight: 2, fillColor: '#0d6efd', fillOpacity: 0.05});
        PIN_DATA.current.forEach(function (pt) { bounds.extend(pt); });
    }
    if (PIN_DATA.outline.length > 2) {
        new google.maps.Polygon({map: map, paths: PIN_DATA.outline, strokeColor: '#198754', strokeWeight: 3, fillColor: '#198754', fillOpacity: 0.08});
    }
    if (!bounds.isEmpty()) { map.fitBounds(bounds); }
}
</script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=<?= Html::encode(GoogleKeys::browserKey()) ?>&callback=initPincodeMap"></script>
