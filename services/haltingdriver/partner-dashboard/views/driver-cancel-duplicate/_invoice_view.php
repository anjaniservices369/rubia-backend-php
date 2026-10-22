<?php
use yii\helpers\Html;
use yii\widgets\DetailView;
use kartik\grid\GridView;
use app\models\User;
use app\modules\admin\models\Booking;
use app\modules\admin\models\DriverEarnings;
?>
<div class="container-fluid">

<div class="conatiner-fluid content-inner  py-0">
		<div class="row">
			<style>
    .row {
        color: #423d3d !important;
    }

    th {
        font-size: 14.5px !important;
    }
</style>



<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <ul class="list-unstyled d-flex justify-content-between">

                    <li>
                        <h6 class="mb-3"> <strong> Ride Id: #<?= $model->id ?></strong></h6>
                    </li>

                                        <li>
                        <h6 class="mb-3"> <strong> Ride Status: <?= $model->getStateOptionsBadges() ?></strong></h6>
                    </li>
                </ul>
                <hr>
                <div class="d-flex flex-wrap align-items-center justify-content-between">


                    <ul class="d-flex nav nav-pills mb-0 text-center profile-tab nav-slider" data-bs-toggle="slider-tab" id="profile-pills-tab" role="tablist" style="width: 33.33%;">

                        <li> <i class="fa fa-map-marker text-primary"></i> <strong> Pickup Address:</strong> <?= $model->pickup_address ?></li>
                    </ul>

                    <?php if (
                        $model->rider_type == Booking::RIDER_TYPE_ONEWAY
                    ) { ?>

                     
                    <ul class="d-flex nav nav-pills mb-0 text-center profile-tab nav-slider justify-content-center" data-bs-toggle="slider-tab" id="profile-pills-tab" role="tablist" style="width: 33.33%;">

                        <li> <strong> <a href=" https://www.google.com/maps/dir/?api=1&amp;origin=<?= $model->pickup_latitude ?>,<?= $model->pickup_longitude ?>&amp;destination=<?= $model->drop_latitude ?>,<?= $model->drop_longitude ?>&amp;travelmode=driving&amp;dir_action=navigate" target="_blank"> <i class="fas fa-location-arrow text-primary" style="font-size: 16px;"></i> View Direction</a></strong>
                        </li>
                    </ul>
                    <ul class="d-flex nav nav-pills mb-0 text-center profile-tab nav-slider" data-bs-toggle="slider-tab" id="profile-pills-tab" role="tablist" style="width: 33.33%;">

                        <li> <i class="fa fa-map-marker text-primary"></i> <strong> Drop Address:</strong> &nbsp; <?= $model->drop_address ?></li>


                    </ul>
 <?php } ?>
                </div>
                <hr>
                <ul class="list-unstyled d-flex justify-content-between">

                    <li>
                        <h6 class="mb-3"> <strong> Ride Status: <?= $model->getStateOptionsBadges() ?></strong></h6>
                    </li>
                                            <li>
                          
                        </li>

                    


                </ul>
            </div>
            <div class="row">
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <div class="header-title">
                                <h4 class="card-title">User Detail</h4>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-1">Name: <?= $model->customer['name'] ?> </div>
                            <div class="mb-1">Email: <?= $model->customer['email'] ?></div>
                            <div class="mb-1">Phone: <?= $model->customer['phone'] ?></div>
                            <div>DOB: <span class="ms-3"><?= $model->customer['dob'] ?></span></div>
                            <div>Gender: <span class="ms-3"><?= $model->customer['gender'] ?></span></div>
                        </div>
                    </div>



                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <div class="header-title">
                                <h4 class="card-title">Driver Detail</h4>
                            </div>
                        </div>
                        <div class="card-body">
                        <?php if (
                            $model->driver_id != null ||
                            !empty($model->driver_id)
                        ) { ?>
                        <div class="mb-1">Name: <?= isset(
                            $model->driver->first_name
                        )
                            ? $model->driver->first_name
                            : '' ?> </div>
                            
                            <div class="mb-1">Phone: <?= isset(
                                $model->driver->contact_no
                            )
                                ? $model->driver->contact_no
                                : '' ?></div>
                            

                       <?php } else { ?>
 <div class="mb-1">(Driver Not Assigned)</div>
                      <?php } ?>
                       

                                                    </div>
                    </div>


                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <div class="header-title">
                                <h4 class="card-title">Vehical Detail</h4>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-1">Vehical Model: <?= isset(
                                $model->car_model
                            )
                                ? $model->car_model
                                : '' ?></div>
                          
                        </div>
                    </div>


                </div>
            </div>
        </div>


    </div>




    <div class="col-lg-12">
        <div class="profile-content tab-content">
            <div id="profile-feed" class="tab-pane fade active show">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between ">
                        <div class="header-title">
                            <div class="d-flex flex-wrap">

                                <div class="media-support-info mt-2">
                                    <h5 class="mb-4">Estimated Fare Details</h5>
                                    <table class="table table-responsive table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Pickup Date</th>
                                                <th>Pickup Time</th>
                                                <th>Estimated Fare(includes coupon discount+nightcharges+additional charges)</th>
                                                <th>Booking Hours</th>
                                              

                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><?= $model->pickup_date ?></td>
                                                <td><?= $model->pickup_time ?></td>
                                                <td> ₹ <?= $model->estimate_price ?></td>
                                                <td><?= $model->usage_hours ?></td>
                                               
                                            </tr>
                                        </tbody>
                                    </table>

                                </div>
                            </div>
                        </div>

                    </div>


                </div>

            </div>


        </div>

                
    </div>
<?php 

if($model->status == Booking::STATUS_RIDE_COMPLETED){ ?>
  <?php
                $price_details = json_decode($model->price_detail, true); // Decode JSON data
          
                // var_dump($price_details);exit;
          
          ?>
     <div class="col-lg-12">
        <div class="profile-content tab-content">
            <div id="profile-feed" class="tab-pane fade active show">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between ">
                        <div class="header-title">
                            <div class="d-flex flex-wrap">

                               <div class="media-support-info mt-2">
    <h5 class="mb-4">Final Fare Details</h5>
    <table class="table table-responsive table-striped table-bordered">
        <thead>
            <tr>
                <th>Pickup Date</th>
                <th>Pickup Time</th>
                <th>Final Fare</th>
                <th>Base Fare</th>
                <th>Base Hour</th>
                <th>Secured Fee</th>
                <th>Platform Charges</th>
                <th>GST</th>
                <th>Base Km</th>
                <th>Extra Km Charge</th>
                <th>Extra Min Charge</th>
                <th>Night Charge</th>
                <th>Outstation Charge</th>
                <th>Additional Charges</th>
                <th>Estimated Distance</th>
                <th>Is Night Charge Applicable</th>
                <th>Usage Hours</th>
            </tr>
        </thead>
        <tbody>
          
            <tr>
                <td><?= $model->pickup_date ?></td>
                <td><?= $model->pickup_time ?></td>
                <td> ₹ <?= $price_details['estimatePrice'] ?></td>
                <td> ₹ <?= $price_details['baseFare'] ?></td>
                <td><?= $price_details['baseHour'] ?></td>
                <td> ₹ <?= $price_details['securedFee'] ?></td>
                <td> ₹ <?= $price_details['platformCharges'] ?></td>
                <td> ₹ <?= $price_details['gst'] ?></td>
                <td><?= $price_details['baseKm'] ?></td>
                <td> ₹ <?= $price_details['extraKmCharge'] ?></td>
                <td> ₹ <?= $price_details['extraMinCharge'] ?></td>
                <td> ₹ <?= $price_details['nightCharge'] ?></td>
                <td> ₹ <?= $price_details['outStationCharge'] ?></td>
                <td> ₹ <?= $price_details['additionalCharges'] ?></td>
                <td><?= $price_details['estimatedDistance'] ?></td>
                <td><?= $price_details['isNightChargeApplicable'] ? 'Yes' : 'No' ?></td>
                <td><?= $price_details['usageHours'] ?></td>
            </tr>
        </tbody>
    </table>
</div>


                            </div>
                        </div>

                    </div>


                </div>

            </div>


        </div>

                
    </div>


     <div class="col-lg-12">
        <div class="profile-content tab-content">
            <div id="profile-feed" class="tab-pane fade active show">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between ">
                        <div class="header-title">
                            <div class="d-flex flex-wrap">

                               <div class="media-support-info mt-2">
    <h5 class="mb-4">Final Ride Details</h5>
    <table class="table table-responsive table-striped table-bordered">
        <thead>
            <tr>
                 <th>Ride Started Time</th>

                 <th>Final Pickup Latitude</th>
                <th>Final Pickup Longitude</th>
                 <th>Ride Ended Time</th>
                 <th>Final Drop Latitude</th>
                <th>Final Drop Longitude</th>
               
            </tr>
        </thead>
        <tbody>
            <?php
                $price_details = json_decode($model->price_detail, true); // Decode JSON data
            ?>
            <tr>
                <td><?= $model->start_time ?></td>

                <td><?= $model->final_pickup_latitude ?></td>
                <td><?= $model->final_pickup_longitude ?></td>
                <td><?= $model->end_time ?></td>
                <td> <?= $model->final_drop_latitude ?></td>
                <td> <?= $model->final_drop_longitude ?></td>
            </tr>
        </tbody>
    </table>
</div>


                            </div>
                        </div>

                    </div>


                </div>

            </div>


        </div>

                
    </div>
    <?php 

    $driverEarnings = DriverEarnings::find()->Where(['booking_id' => $model->id])->one();

?>
      <div class="col-lg-12">
        <div class="profile-content tab-content">
            <div id="profile-feed" class="tab-pane fade active show">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between ">
                        <div class="header-title">
                            <div class="d-flex flex-wrap">

                               <div class="media-support-info mt-2">
    <h5 class="mb-4">Final Earning Details</h5>
    <table class="table table-responsive table-striped table-bordered">
        <thead>
            <tr>
                
                 <th>Total Distance</th>
                <th>Total Amount(Including GST)</th>
                 <th>GST</th>

                 <th>Ride Amount(Excluding GST)</th>
                 <th>Admin Percentage</th>
                 <th>Admin Earning</th>
              
               
            </tr>
        </thead>
        <tbody>
          
            <tr>
                <td><?= $driverEarnings->total_distance_km ?></td>

                <td><?= $driverEarnings->total_amount ?></td>
                <td><?= $price_details['gst']?></td>
                <td><?= $driverEarnings->total_amount - $price_details['gst'] ?></td>
                <td><?= $driverEarnings->admin_per ?></td>
                <td><?= $driverEarnings->admin_earning ?></td>
              
            </tr>
        </tbody>
    </table>
</div>


                            </div>
                        </div>

                    </div>


                </div>

            </div>


        </div>

                
    </div>
<?php } ?>
     <div class="card">
       <div class="card-body">
    <div class="row">
<?php if ($providerDriverRequests->totalCount) {
    echo '<div class="row row-cols-1 row-cols-md-3 g-3 ">'; // Start card deck container

    foreach ($providerDriverRequests->models as $model) {
        echo '<div class="col-md-3 mb-2">'; // Start card container col
        echo '<div class="card ">'; // Start card container
        echo '<div class="card-body">'; // Start card body container

        echo '<h5 class="card-title">Driver: ' . (isset($model->driver) ? $model->driver->username : '') . '</h5>';

            
        echo '<p class="card-text">' . $model->getStateOptionsBadges() . '</p>';

        echo '</div>'; // End card body container
        echo '</div>'; // End card container
        echo '</div>'; // End card container col
    }

    echo '</div>'; // End card deck container
} ?>
</div>
</div>
</div>


</div>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">Vehicle Verification</h5>

        <?php 

        // var_dump($_GET['id']);exit;
        $verifcationImages =  \app\modules\admin\models\VehicleVerification::find()->Where(['booking_id'=> $_GET['id']])->all();
        if ($verifcationImages): ?>
            <div class="row">
                <?php foreach (
                    $verifcationImages
                    as $images
                ): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <img src="<?= $images->image ?>" class="card-img-top" alt="Vehicle Image">
                            <div class="card-body">
                                <h6 class="card-title"><?= $images->getImageTypeBadges() ?></h6>
                                <p class="card-text">Type: <?= $images->getRideTypeBadges() ?></p>
                                <p class="card-text">Status: <?= $images->status ?></p>
                                  <p class="card-text">'Create User: <?= $images->createUser->username ?? 'N/A' ?>'</p>
                                <p class="card-text">'Update User: <?= $images->updateUser->username ?? 'N/A' ?>'</p>
                                <p class="card-text">Created On: <?= $images->created_on ?></p>
                                <p class="card-text">Updated On: <?= $images->updated_on ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No vehicle verification records found.</p>
        <?php endif; ?>
    </div>
</div>
        </div>
<div class="card">
    <div class="card-title p-2">Rating & Reviews</div>
       <div class="card-body">
    <div class="row">

<?php $ratingsReview = \app\modules\admin\models\base\DriverBookingRatings::find()->where(['booking_id'=> $model->booking_id])->all(); if ($providerDriverRequests->totalCount) {
    echo '<div class="row row-cols-1 row-cols-md-3 g-3 ">'; // Start card deck container

    foreach ($ratingsReview as $model) {
        // the customer belongs to the customer app: asked through its API, not read from a local table
        $customerName = \app\modules\customerapi\services\CustomerClient::profileCached($model->user_id)['name'];
        echo '<div class="col-md-12 mb-2">'; // Start card container col
        echo '<div class="card ">'; // Start card container
        echo '<div class="card-body">'; // Start card body container

        echo '<h5 class="card-title">User name: ' . \yii\helpers\Html::encode($customerName)
             . '</h5>';
        echo '<p class="card-text"> Rating: ' . $model->rating . ' Star</p>';
        echo '<p class="card-text"> Review: ' . $model->review ?? '' . '</p>';
        echo '</div>'; // End card body container
        echo '</div>'; // End card container
        echo '</div>'; // End card container col
    }

    echo '</div>'; // End card deck container
} ?>

<?php
$userReviews = \app\modules\admin\models\UserBookingRatings::find()->where(['booking_id' => $model->booking_id])->all();
if (!empty($userReviews)) {
    echo '<div class="row row-cols-1 row-cols-md-3 g-3">'; // Start card deck container

    foreach ($userReviews as $ratingModel) {
        $user = \app\modules\admin\models\User::find()->where(['id' => $ratingModel->driver_id])->one();
        echo '<div class="col-md-12 mb-2">'; // Start card container col
        echo '<div class="card">'; // Start card container
        echo '<div class="card-body">'; // Start card body container

        echo '<h5 class="card-title">Driver Name: ' . $user->first_name . '</h5>';
        echo '<p class="card-text">Rating: ' . $ratingModel->rating . ' Star</p>';
        echo '<p class="card-text">Review: ' . ($ratingModel->review ?? '') . '</p>';
        echo '</div>'; // End card body container
        echo '</div>'; // End card container
        echo '</div>'; // End card container col
    }

    echo '</div>'; // End card deck container
}
?>

</div>
</div>
</div>
<?php
//$price_details = json_decode($model->price_detail, true);
// var_dump( $model->final_drop_longitude);exit;
if(isset($price_details))
if($price_details['coordinates']!=null|| $price_details['coordinates']!="null"){ ?>
<script src="https://maps.googleapis.com/maps/api/js?key=<?= \app\modules\geo\services\GoogleKeys::browserKey() ?>"></script>
<script>
    function initializeMap() {
    var mapOptions = {
        center: { lat: 17.4466109, lng: 78.392397 }, // Center the map on the first coordinate
        zoom: 14, // Adjust the zoom level as needed
    };

    var map = new google.maps.Map(document.getElementById('map'), mapOptions);

    var coordinates = <?= json_encode($price_details['coordinates']) ?>;

    var pathCoordinates = coordinates.map(function(coord) {
        return new google.maps.LatLng(coord.lat, coord.lng);
    });

    var polyline = new google.maps.Polyline({
        path: pathCoordinates,
        geodesic: true,
        strokeColor: '#FF0000',
        strokeOpacity: 1.0,
        strokeWeight: 2,
    });

    polyline.setMap(map);

    // Add markers for each coordinate
    coordinates.forEach(function(coord) {
        var marker = new google.maps.Marker({
            position: { lat: parseFloat(coord.lat), lng: parseFloat(coord.lng) },
            map: map,
            title: 'Coordinate',
        });
    });
}

// Initialize the map
google.maps.event.addDomListener(window, 'load', initializeMap);
</script>
<div id="map" style="height: 500px;"></div>

<?php }
?>






<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<script>
    $(document).on('click', '#verified', function() {
        var id = $(this).attr('data-id');
        var val = $(this).attr('data-status');;
        console.log(id);
        $.ajax({
            type: "POST",

            url: "/easygo_backend/admin/driver-details/update-verify-status",


            data: {
                id: id,
                val: val
            },
            success: function(data) {
                swal("Great!", "Status Successfully Changed!", "success");
                location.reload();
            }

        });
    });
</script>		</div>
	</div> 
</div>
