<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use kartik\grid\GridView;
use app\models\User;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\City */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Cities', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="city-view">
<div class="card">
       <div class="card-body">
    <div class="row">
        <div class="col-sm-9">
            <h2><?= 'City' . ' ' . Html::encode($this->title) ?></h2>
        </div>
        <div class="col-sm-3" style="margin-top: 15px">
            
            <?= Html::a(
                'Update',
                ['update', 'id' => $model->id],
                ['class' => 'btn btn-primary']
            ) ?>
            <?php if (\Yii::$app->user->identity->user_role == User::ROLE_ADMIN || \Yii::$app->user->identity->user_role == User::ROLE_SUBADMIN) { ?>
            <?php foreach (\app\modules\geo\services\ServiceModules::all() as $mid => $mm) {
                echo Html::a('Map: ' . Html::encode($mm['title']), ['/admin/service-map/map', 'module' => $mid, 'city' => $model->id], ['class' => 'btn btn-outline-primary btn-sm']) . ' ';
            } ?>
            <?php } ?>
                          <?php if (
                              \Yii::$app->user->identity->user_role ==
                                  User::ROLE_ADMIN ||
                              \Yii::$app->user->identity->user_role ==
                                  User::ROLE_SUBADMIN
                          ) { ?>
             <?= Html::a(
                 'Delete',
                 ['delete', 'id' => $model->id],
                 [
                     'class' => 'btn btn-danger',
                     'data' => [
                         'confirm' =>
                             'Are you sure you want to delete this item?',
                         'method' => 'post',
                     ],
                 ]
             ) ?>  
             <?php } ?>
        </div>
    </div>
    </div>
    </div>
    <div class="card">
       <div class="card-body">

    <div class="row">
<?php
$gridColumn = [['attribute' => 'id', 'visible' => false], 'name', 'status'];
echo DetailView::widget([
    'model' => $model,
    'attributes' => $gridColumn,
]);
?>
</div>
</div>
    </div>
</div>


<div class="row">
    <div class="col-xs-8 col-sm-8 col-md-8 col-lg-8">
   <div id="map" style="height: 500px;width:800px" ></div>
        
    </div>
     <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
        
    
    </div>
</div>

   <div class="card">
       <div class="card-body">
    <div class="row">
<?php if ($providerCityRateCard->totalCount): ?>
    <div class="row row-cols-1 row-cols-md-3 g-3">
        <?php foreach ($providerCityRateCard->getModels() as $cityRateCard): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <?= $cityRateCard->getTripTypeBadges() ?>
                        - <?= $cityRateCard->getRideTypeBadges() ?>
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li><strong>Base Hour:</strong> <?= $cityRateCard->base_hour ?></li>
                        <li><strong>Base Km:</strong> <?= $cityRateCard->base_km ?></li>
                        <li><strong>Base Fare:</strong> <?= $cityRateCard->base_fare ?></li>
                        <li><strong>Extra Km Charge:</strong> <?= $cityRateCard->extra_km_charge ?></li>
                        <li><strong>Extra Min Charge:</strong> <?= $cityRateCard->extra_min_charge ?></li>
                        <li><strong>Night Charge:</strong> <?= $cityRateCard->night_charge ?></li>
                        <li><strong>Out Station Charge:</strong> <?= $cityRateCard->out_station_charge ?></li>
                        <li><strong>Status:</strong> <?= $cityRateCard->getStateOptionsBadges() ?></li>
                    </ul>
                </div>
                  <div class="card-footer">
                    <?= Html::a(
                        'Edit',
                        [
                            '/admin/city-rate-card/update',
                            'id' => $cityRateCard->id,
                            'city_id' => $model->id,
                            'trip_type' => $cityRateCard->trip_type,
                            'ride_type' => $cityRateCard->ride_type,
                        ],
                        ['class' => 'btn btn-primary']
                    ) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p>No City Rate Card records found.</p>
<?php endif; ?>


</div>
</div>
</div>
  

</div>

<script>
  var map;

  function initMap() {
    map = new google.maps.Map(document.getElementById('map'), {
      zoom: 12 // Set initial zoom level
    });

    // Display existing boundary points on the map during update
    var boundaryData = <?= $model->boundary ? $model->boundary : '[]' ?>;
    var existingBoundary = [];

    try {
      existingBoundary = JSON.parse(boundaryData);
    } catch (error) {
      console.error('Error parsing boundary data:', error);
    }

    if (existingBoundary.length > 0) {
      var boundaryPoints = existingBoundary.map(function (point) {
        var lat = parseFloat(point[0]);
        var lng = parseFloat(point[1]);

        if (!isNaN(lat) && !isNaN(lng)) {
          return { lat: lat, lng: lng };
        }
      });

      var filteredBoundaryPoints = boundaryPoints.filter(function (point) {
        return point !== undefined;
      });

      if (filteredBoundaryPoints.length > 0) {
        var existingPolygon = new google.maps.Polygon({
          paths: filteredBoundaryPoints,
          editable: false, // Set editable to false
          draggable: false, // Set draggable to false
          geodesic: true
        });

        existingPolygon.setMap(map);

        // Calculate the center point of the boundary
        var bounds = new google.maps.LatLngBounds();
        for (var i = 0; i < filteredBoundaryPoints.length; i++) {
          bounds.extend(filteredBoundaryPoints[i]);
        }
        map.fitBounds(bounds);

        // Set the map center to the center point of the boundary
        map.setCenter(bounds.getCenter());
      }
    } else {
      // Set static latitude and longitude as the map center
      var defaultCenter = { lat: 37.7749, lng: -122.4194 };
      map.setCenter(defaultCenter);
    }
  }
</script>

<script src="https://maps.googleapis.com/maps/api/js?key=<?= \app\modules\geo\services\GoogleKeys::browserKey() ?>&callback=initMap&libraries=drawing,places" async defer></script>
