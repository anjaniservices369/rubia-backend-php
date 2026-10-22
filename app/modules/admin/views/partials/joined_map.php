<?php

use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\View;

/* @var $this yii\web\View */
/* @var $user app\models\User  the driver or customer whose address was located (lat, lng, city_id, city_name) */

// the boundary of the city the person joined in, drawn on the map (franchise operations: where the city starts and ends)
$polygon = [];
if ($user->city_id) {
    $raw = (new \yii\db\Query())->select('boundary')->from('city')->where(['id' => (int) $user->city_id])->scalar();
    try {
        $decoded = $raw ? Json::decode($raw) : null;
        $polygon = is_string($decoded) ? json_decode($decoded, true) : (is_array($decoded) ? $decoded : []);
    } catch (\Throwable $e) {
        $polygon = [];
    }
}
$hasPoint = $user->lat !== null && $user->lat !== '' && $user->lng !== null && $user->lng !== '';
$mapId = 'joined-map-' . (int) $user->id;
// some older records hold latitude and longitude the other way round (India: latitude 6 to 37, longitude 68 to 98): shown corrected
$lat = $hasPoint ? (float) $user->lat : 0.0;
$lng = $hasPoint ? (float) $user->lng : 0.0;
$swapped = $hasPoint && $lat >= 60 && $lat <= 100 && $lng >= 5 && $lng <= 40;
if ($swapped) {
    [$lat, $lng] = [$lng, $lat];
}
// a point the app sent without an address has no city yet: the city whose boundary holds the point is shown (not saved)
$cityName = (string) $user->city_name;
$fromPoint = false;
if ($hasPoint && $cityName === '') {
    $found = \app\modules\geo\services\AddressCity::cityAt($lat, $lng, '');
    if ($found !== null) {
        $cityName = $found[1];
        $fromPoint = true;
        if (!$polygon) {
            $raw = (new \yii\db\Query())->select('boundary')->from('city')->where(['id' => $found[0]])->scalar();
            try {
                $decoded = $raw ? Json::decode($raw) : null;
                $polygon = is_string($decoded) ? json_decode($decoded, true) : (is_array($decoded) ? $decoded : []);
            } catch (\Throwable $e) {
                $polygon = [];
            }
        }
    }
}
?>
<div class="card mb-3"><div class="card-header"><strong>Joined in</strong></div><div class="card-body">
    <?php if ($cityName !== '' || $hasPoint) : ?>
        <p class="mb-2">
            <span class="badge text-bg-primary fs-6"><?= Html::encode($cityName !== '' ? $cityName : 'City not found') ?></span><?= $fromPoint ? ' <span class="text-muted small">(from the point on the map; save an address to keep it)</span>' : '' ?>
            <?php if ($hasPoint) : ?>
                <span class="text-muted ms-2"><?= round($lat, 5) ?>, <?= round($lng, 5) ?><?= $swapped ? ' (stored the other way round, shown corrected)' : '' ?></span>
                <?= Html::a('Open in Google Maps', 'https://www.google.com/maps?q=' . rawurlencode($lat . ',' . $lng), ['target' => '_blank', 'rel' => 'noopener', 'class' => 'ms-2']) ?>
            <?php endif; ?>
        </p>
        <?php if ($hasPoint) : ?>
            <div id="<?= $mapId ?>" class="rb-map" role="img" aria-label="Map of where this person joined"><noscript>The map needs JavaScript.</noscript></div>
        <?php endif; ?>
        <p class="small text-muted mt-2 mb-0">The city is found from the address (the point on the map lies inside the city's boundary, drawn on the map), so a city franchise sees the people who joined in its city.</p>
    <?php else : ?>
        <p class="text-muted mb-0">No location yet. Save the address in the personal details: its point on the map and the city are found from it.</p>
    <?php endif; ?>
</div></div>
<?php
if ($hasPoint) {
    $this->registerCssFile('https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css');
    $this->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js', ['position' => View::POS_END]);
    $point = Json::htmlEncode([$lat, $lng]);
    $poly = Json::htmlEncode(array_values(array_filter($polygon, 'is_array')));
    $this->registerJs(<<<JS
(function () {
    var el = document.getElementById('$mapId');
    if (!el || typeof L === 'undefined') {
        if (el) { el.className = 'rb-map rb-map-off'; el.textContent = 'The map could not be loaded (no connection to the map service).'; }
        return;
    }
    var point = $point, poly = $poly;
    var map = L.map(el, {scrollWheelZoom: false}).setView(point, 12);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '&copy; OpenStreetMap contributors'}).addTo(map);
    var brand = getComputedStyle(document.body).getPropertyValue('--rb-accent').trim() || '#0f766e';
    var layers = [L.circleMarker(point, {radius: 9, color: '#fff', weight: 3, fillColor: brand, fillOpacity: 1}).addTo(map)];
    if (poly.length > 2) {
        layers.push(L.polygon(poly, {color: brand, weight: 2, fillColor: brand, fillOpacity: .08}).addTo(map));
        map.fitBounds(L.featureGroup(layers).getBounds().pad(.1));
    }
})();
JS, View::POS_END);
}
