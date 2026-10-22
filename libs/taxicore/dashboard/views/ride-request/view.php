<?php

use app\modules\admin\models\base\RideRequest;
use app\modules\admin\models\RejectLog;
use kartik\helpers\Html;
use yii\helpers\Url;
?>
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

                    <?php if ($model->status == RideRequest::STATUS_RIDE_COMPLETED_PAID) { ?>
                        <li>
                            <?php echo Html::a('<i class="fas fa-file-export"></i> Invoice', ['print-page', 'ride_id' => $model->id], [
                                'class' => 'btn btn-info btn-sm',
                                'target' => '_blank',
                                'data-bs-toggle' => 'tooltip',
                                'title' => 'Will open the generated PDF file in a new window'
                            ]); ?>
                        </li>
                    <?php  } ?>
                    <li>
                        <h6 class="mb-3"> <strong> Ride Status: <?= $model->getStateOptionsBadges() ?></strong></h6>
                    </li>
                </ul>
                <hr>
                <div class="d-flex flex-wrap align-items-center justify-content-between">


                    <ul class="d-flex nav nav-pills mb-0 text-center profile-tab nav-slider" data-bs-toggle="slider-tab" id="profile-pills-tab" role="tablist" style="width: 33.33%;">

                        <li> <i class="fa fa-map-marker text-primary"></i> <strong> Pickup Address:</strong> &nbsp; <?= $model->pickup_address ?>, <?= $model->pickup_pincode ?> </li>
                    </ul>

                    <ul class="d-flex nav nav-pills mb-0 text-center profile-tab nav-slider justify-content-center" data-bs-toggle="slider-tab" id="profile-pills-tab" role="tablist" style="width: 33.33%;">

                        <li> <strong> <a href=" https://www.google.com/maps/dir/?api=1&origin=<?= $model->pickup_latitude ?>,<?= $model->pickup_longitude ?>&destination=<?= $model->drop_latitude ?>,<?= $model->drop_longitude ?>&travelmode=driving&dir_action=navigate" target="_blank"> <i class="fas fa-location-arrow text-primary" style="font-size: 16px;"></i> View Direction</strong> </a> </li>
                    </ul>
                    <ul class="d-flex nav nav-pills mb-0 text-center profile-tab nav-slider" data-bs-toggle="slider-tab" id="profile-pills-tab" role="tablist" style="width: 33.33%;">

                        <li> <i class="fa fa-map-marker text-primary"></i> <strong> Drop Address:</strong> &nbsp; <?= $model->drop_address ?>, <?= $model->drop_pincode ?> </li>


                    </ul>

                </div>
                <hr>
                <ul class="list-unstyled d-flex justify-content-between">

                    <li>
                        <h6 class="mb-3"> <strong> Ride Status: <?= $model->getStateOptionsBadges() ?></strong></h6>
                    </li>
                    <?php
                    $rejectLog = RejectLog::find()->where(['ride_id' => $model->id])->one();
                    if ($model->status == RideRequest::STATUS_CANCEL_BY_DRIVER || $model->status == RideRequest::STATUS_CANCEL_BY_USER) { ?>
                        <li>
                            <h6 class="mb-3"> <strong> Cancel Reason: <?= isset($rejectLog->reject_reason) ? $rejectLog->reject_reason : ("N/A") ?></strong></h6>
                        </li>

                    <?php } else { ?>
                        <li>
                            <h6 class="mb-3"> <strong> Payment Method: <?= $model->getPaymentMethodBadges() ?></strong></h6>
                        </li>
                        <li>
                            <?php if ($model->ride_start_time == "0000-00-00 00:00:00") { ?>
                                <h6 class="mb-3"> <strong> Ride Start Time: <span class="badge bg-danger">Ride Not Started Yet</span> </strong></h6>
                            <?php } else { ?>
                                <h6 class="mb-3"> <strong> Ride Start Time: <?= $model->ride_start_time ?></strong></h6>
                            <?php } ?>
                            
                    <?php if ($model->ride_end_time != "0000-00-00 00:00:00") { ?>
                        <li>


                            <h6 class="mb-3"> <strong> Ride End Time: <?= $model->ride_end_time ?></strong></h6>


                        </li>
                    <?php } ?>

                        </li>

                    <?php      } ?>



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
                            <div class="mb-1">Name: <?= $model->customer['name'] ?: $model->customer['phone'] ?></div>
                            <div class="mb-1">Email: <?= $model->customer['email'] ?: "Not Set" ?></div>
                            <div class="mb-1">Phone: <?= $model->customer['phone'] ?: "Not Set" ?></div>
                            <div>DOB: <span class="ms-3"><?= isset($model->user->date_of_birth) ? $model->user->date_of_birth : "Not Set" ?></span></div>
                            <div>Gender: <span class="ms-3"><?= isset($model->user->gender) ? $model->user->gender : "Not Set" ?></span></div>
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
                            <?php if (!empty($model->driver)) { ?>
                                <div class="mb-1">Name: <?= isset($model->driver->first_name) ? $model->driver->first_name :  $model->driver->username ?></div>
                                <div class="mb-1">Phone: <?= isset($model->driver->contact_no) ? $model->driver->contact_no :  "Not Set" ?></div>
                                <div>Home Address: <span class="ms-3"><?= isset($model->driverDetail->address) ? $model->driverDetail->address :  "Not Set" ?></span></div>
                                <div>DOB: <span class="ms-3"><?= isset($model->driver->date_of_birth) ? $model->driver->date_of_birth : "Not Set" ?></span></div>
                                <div>Gender: <span class="ms-3"><?= isset($model->driver->gender) ? $model->driver->gender : "Not Set" ?></span></div>
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
                            <div class="mb-1">Vehical Type: <?= isset($model->vehicalType->title) ? $model->vehicalType->title :  "" ?></div>
                            <div class="mb-1">Vehical Number: <?= isset($model->driverDetail->vehical_number) ? $model->driverDetail->vehical_number :  "Not Set" ?></div>
                            <div>RC Number <span class="ms-3"><?= isset($model->driverDetail->rc_number) ? $model->driverDetail->rc_number :  "Not Set" ?></span></div>
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
                                    <h5 class="mb-4">Fare Details</h5>
                                    <table class="table table-responsive table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Estimated Distance</th>
                                                <th>Estimated Time</th>
                                                <th>Estimated Fare</th>
                                                <th>Final Distance</th>
                                                <th>Final Time</th>
                                                <th>Final Fare</th>

                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><?= $model->estimated_distance ?> Km</td>
                                                <td><?= $model->estimated_time ?> Mins</td>
                                                <td> ₹ <?= $model->estimated_ride_fare ?></td>
                                                <td><?= $model->final_distance ?> Km</td>
                                                <td><?= $model->final_time ?> Mins</td>
                                                <td> ₹ <?= $model->final_price ?></td>
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

        <?php if (!empty($rideEarnings)) { ?>
            <div class="profile-content tab-content">
                <div id="profile-feed" class="tab-pane fade active show">
                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between ">
                            <div class="header-title">
                                <div class="d-flex flex-wrap">

                                    <div class="media-support-info mt-2">
                                        <h5 class="mb-4">Earning Details</h5>

                                        <table class="table table-responsive table-striped table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Total Amount</th>
                                                    <th>Admin Commision (%)</th>
                                                    <th>Admin Commision Amount</th>
                                                    <th>Driver Earnings</th>
                                                    <th>Payment Method</th>
                                                    <th>Status</th>

                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td> ₹ <?= $rideEarnings->total_amount ?></td>
                                                    <td><?= $rideEarnings->ride->driverDetail->commission_percent ?>%</td>
                                                    <td> ₹ <?= $rideEarnings->admin_earning ?></td>
                                                    <td> ₹ <?= $rideEarnings->driver_earning ?></td>
                                                    <td><?= $rideEarnings->ride->getPaymentMethodBadges() ?></td>
                                                    <td> <?= $rideEarnings->getStateOptionsBadges() ?></td>
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
        <?php } ?>
        <?php if (!empty($rideReviews)) { ?>
            <div class="profile-content tab-content">
                <div id="profile-feed" class="tab-pane fade active show">
                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between ">
                            <div class="header-title">
                                <div class="d-flex flex-wrap">

                                    <div class="media-support-info mt-2">
                                        <h5 class="mb-4">Reviews</h5>

                                        <table class="table table-responsive table-striped table-bordered w-100">
                                            <thead>
                                                <tr>
                                                    <th>User Name</th>
                                                    <th>Driver Name</th>
                                                    <th>Rating</th>
                                                    <th>Message</th>


                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td> <?= $rideReviews->customer['name'] ?: $rideReviews->customer['phone'] ?></td>
                                                    <td><?= !empty($rideReviews->taxiDriver->first_name) ? $rideReviews->taxiDriver->first_name : $rideReviews->taxiDriver->username ?></td>
                                                    <td> <?= $rideReviews->rating ?></td>
                                                    <td> <?= !empty($rideReviews->user_discription) ? $rideReviews->user_discription : ("N/A") ?></td>


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
        <?php } ?>

    </div>

</div>

<div class="card">
    <div class="card-title p-2">Rating & Reviews</div>
       <div class="card-body">
    <!-- <div class="row"> -->

<?php $ratingsReview = \app\modules\admin\models\base\TaxiRating::find()->where(['ride_id'=> $model->id])->all(); 
    echo '<div class="row row-cols-1 row-cols-md-3 g-3 ">'; // Start card deck container
    echo '<div class="row">'; 
    foreach ($ratingsReview as $model) {
        $user = \app\modules\admin\models\User::find()->where(['id' => $model->user_id])->one();
        echo '<div class="col-md-12 mb-2">'; // Start card container col
        echo '<div class="card ">'; // Start card container
        echo '<div class="card-body">'; // Start card body container

        echo '<h5 class="card-title">User: ' . $user->first_name
             . '</h5>';
        echo '<p class="card-text"> Rating: ' . $model->rating . ' Star</p>';
        echo '<p class="card-text"> Review: ' . $model->user_discription ?? '' . '</p>';
        echo '</div>'; // End card body container
        echo '</div>'; // End card container
        echo '</div>'; // End card container col
    }

    echo '</div>'; // End card deck container
    ?>
</div>
</div>
</div>
</div>




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
</script>