<?php

use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\View;

/* @var $this yii\web\View */
/* @var $points array each ['id', 'lat', 'lng', 'name', 'city'] */
/* @var $totalCustomers int */

$this->title = 'Customer Map';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="card">
    <div class="card-title p-3">
        <h4>Customer Map</h4>
    </div>
    <div class="card-body">
        <p class="text-muted">
            <?= count($points) ?> of <?= (int) $totalCustomers ?> customers have a saved location. Everyone else
            has no address on file yet, so there is nothing to plot for them.
        </p>
        <?php if (empty($points)) : ?>
            <p class="text-muted mb-0">No customer has a saved location yet.</p>
        <?php else : ?>
            <div id="customer-map" class="rb-map" style="height: 70vh;" role="img" aria-label="Map of where customers joined">
                <noscript>The map needs JavaScript.</noscript>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
if (!empty($points)) {
    $this->registerCssFile('https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css');
    $this->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js', ['position' => View::POS_END]);
    $this->registerCssFile('https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/MarkerCluster.css');
    $this->registerCssFile('https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/MarkerCluster.Default.css');
    $this->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/leaflet.markercluster.js', ['position' => View::POS_END, 'depends' => [\yii\web\JqueryAsset::class]]);
    $data = Json::htmlEncode($points);
    $this->registerJs(<<<JS
(function () {
    var el = document.getElementById('customer-map');
    if (!el || typeof L === 'undefined') {
        if (el) { el.className = 'rb-map rb-map-off'; el.textContent = 'The map could not be loaded (no connection to the map service).'; }
        return;
    }
    var points = $data;
    var map = L.map(el).setView([points[0].lat, points[0].lng], 11);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '&copy; OpenStreetMap contributors'}).addTo(map);
    var brand = getComputedStyle(document.body).getPropertyValue('--rb-accent').trim() || '#0f766e';
    var cluster = (typeof L.markerClusterGroup === 'function') ? L.markerClusterGroup() : L.layerGroup();
    var markers = [];
    points.forEach(function (p) {
        var m = L.circleMarker([p.lat, p.lng], {radius: 7, color: '#fff', weight: 2, fillColor: brand, fillOpacity: .9})
            .bindPopup(p.name + (p.city ? ' &middot; ' + p.city : ''));
        cluster.addLayer(m);
        markers.push(m);
    });
    map.addLayer(cluster);
    if (markers.length > 1) {
        map.fitBounds(L.featureGroup(markers).getBounds().pad(.1));
    }
})();
JS, View::POS_END);
}
?>
