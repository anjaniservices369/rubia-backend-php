<?php
use yii\helpers\Json;

/* @var $model app\models\City */

// Get the boundary data
$boundary = Json::encode($model->boundary);

// Generate the JavaScript code to initialize the map
$js = <<< JS
function initialize() {
    // Create the map
    var map = new google.maps.Map(document.getElementById('map'), {
        center: {lat: 0, lng: 0},
        zoom: 10
    });

    // Create a new polygon
    var polygon = new google.maps.Polygon({
        map: map,
        paths: $boundary, // Use the boundary data
        editable: true,
        draggable: true
    });

    // Update the hidden input field with the polygon path coordinates
    google.maps.event.addListener(polygon.getPath(), 'set_at', function() {
        updateBoundary();
    });

    google.maps.event.addListener(polygon.getPath(), 'insert_at', function() {
        updateBoundary();
    });

    google.maps.event.addListener(polygon.getPath(), 'remove_at', function() {
        updateBoundary();
    });

    function updateBoundary() {
        var coordinates = polygon.getPath().getArray();
        var boundary = coordinates.map(function(coord) {
            return [coord.lat(), coord.lng()];
        });
        document.getElementById('city-boundary').value = JSON.stringify(boundary);
    }
}

// Initialize the map
google.maps.event.addDomListener(window, 'load', initialize);
JS;

// Register the JavaScript code
$this->registerJs($js);
