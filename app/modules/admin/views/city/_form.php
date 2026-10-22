<?php

use yii\helpers\Html;
use kartik\form\ActiveForm;
use app\models\User;
use kartik\widgets\Select2;


/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\City */
/* @var $form yii\widgets\ActiveForm */

?>

<div class="city-form">   
    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
    <?php
$statesData = \app\modules\admin\models\States::find()->orderBy('id')->asArray()->all();
?>

<?= $form->field($model, 'States_id')->label('Select State')->widget(\kartik\widgets\Select2::classname(), [
    'data' => \yii\helpers\ArrayHelper::map($statesData, 'id', 'state_name'),
    'options' => ['placeholder' => Yii::t('app', 'Select State')],
    'pluginOptions' => [
        'allowClear' => true,
    ],
]); ?>
    <input type="text" id="search-input" placeholder="Search for a location">

    <div id="map" style="height: 600px;"></div>

    <?= $form->field($model, 'boundary')->hiddenInput()->label(false) ?>

   

   
    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<script>
    var map;

    function initMap() {
        map = new google.maps.Map(document.getElementById('map'), {
            zoom: 12 // Set initial zoom level
        });

        // Add search bar to map
        var searchInput = document.getElementById('search-input');
        map.controls[google.maps.ControlPosition.TOP_LEFT].push(searchInput);

        // Create a search box if the Places API is available
        if (google.maps.places && google.maps.places.SearchBox) {
            var searchBox = new google.maps.places.SearchBox(searchInput);

            // Listen for the 'places_changed' event when a place is selected from the search results
            searchBox.addListener('places_changed', function () {
                var places = searchBox.getPlaces();
                if (places.length === 0) {
                    return;
                }

                // Get the selected place
                var selectedPlace = places[0];

                // Set the map center to the selected place
                map.setCenter(selectedPlace.geometry.location);
                map.setZoom(12); // You can adjust the zoom level as per your requirement

                // Optionally, you can update the form inputs with the selected place details
                // For example, if you have separate fields for latitude and longitude:
                // document.getElementById('pickup-lat').value = selectedPlace.geometry.location.lat();
                // document.getElementById('pickup-lng').value = selectedPlace.geometry.location.lng();
            });
        } else {
            console.log('Places API is not available. Search functionality will not work.');
        }

        // Display existing boundary points on the map during update
        var existingBoundary = <?= json_encode(json_decode($model->boundary ?: '[]')) ?>;
        if (existingBoundary.length > 0) {
            var boundaryPoints = [];
            for (var i = 0; i < existingBoundary.length; i++) {
                boundaryPoints.push(new google.maps.LatLng(existingBoundary[i][0], existingBoundary[i][1]));
            }

            var existingPolygon = new google.maps.Polygon({
                paths: boundaryPoints,
                editable: true,
                draggable: true,
                geodesic: true
            });

            existingPolygon.setMap(map);

            // Calculate the center point of the boundary
            var bounds = new google.maps.LatLngBounds();
            for (var i = 0; i < boundaryPoints.length; i++) {
                bounds.extend(boundaryPoints[i]);
            }
            map.fitBounds(bounds);

            // Set the map center to the center point of the boundary
            map.setCenter(bounds.getCenter());
        } else {
            // Set static latitude and longitude as the map center
            var defaultCenter = { lat: 37.7749, lng: -122.4194 };
            map.setCenter(defaultCenter);
        }

        // Initialize Drawing Manager
        var drawingManager = new google.maps.drawing.DrawingManager({
            drawingControl: true, // Show drawing control buttons
            drawingControlOptions: {
                position: google.maps.ControlPosition.TOP_CENTER,
                drawingModes: [google.maps.drawing.OverlayType.POLYGON]
            },
            polygonOptions: {
                editable: true // Allow polygon editing
            }
        });

        // Enable drawing mode on the map
        drawingManager.setMap(map);

        // Add this function to update the hidden input with States_id
        function getSelectedStatesId() {
            var statesDropdown = document.getElementById('city-states_id');
            return statesDropdown.value;
        }
        // Listen for polygon completion event
        google.maps.event.addListener(drawingManager, 'overlaycomplete', function (event) {
            if (event.type === google.maps.drawing.OverlayType.POLYGON) {
                var polygon = event.overlay;
                var boundaryPoints = polygon.getPath().getArray();

                // Convert boundary points to a format compatible with Yii2's geometry data type
                var boundaryData = [];
                for (var i = 0; i < boundaryPoints.length; i++) {
                    boundaryData.push([boundaryPoints[i].lat(), boundaryPoints[i].lng()]);
                }

                // Set the hidden input field value to the boundary data
                document.getElementById('city-boundary').value = JSON.stringify(boundaryData);

                var selectedStatesId = getSelectedStatesId(); // Implement this function accordingly

            // Update the hidden input with States_id
            updateStatesId(selectedStatesId);
            }
        });

        function getSelectedStatesId() {
    var statesDropdown = document.getElementById('city-states_id');
    return statesDropdown.value;
}

function updateStatesId(statesId) {
    // Set the hidden input field value to the States_id
    document.getElementById('city-states_id').value = statesId;
}

    }
</script>

<script src="https://maps.googleapis.com/maps/api/js?key=<?= \app\modules\geo\services\GoogleKeys::browserKey() ?>&callback=initMap&libraries=drawing,places" async defer></script>
