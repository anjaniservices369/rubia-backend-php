<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use kartik\grid\GridView;
use app\models\User;
use app\modules\admin\models\DriverDetails;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\DriverDetails */

$this->title = "Taxi Driver Detail";
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Taxi Driver Details'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>


<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between">
                    <div class="d-flex flex-wrap align-items-center">
                        <div class="profile-img position-relative me-3 mb-3 mb-lg-0 profile-logo profile-logo1">
                            <img src="<?= !empty($model->user->profile_image) ? $model->user->profile_image : "https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460__340.png" ?>" alt="User-Profile" class=" img-fluid rounded-pill avatar-100" width="100px" height="100px">

                        </div>
                        <div class="d-flex flex-wrap align-items-center mb-3 mb-sm-0">
                            <h4 class="me-2 h4">
                                <?php if (!empty($model->user->first_name ?? '')) { ?>
                                    <?= $model->user->first_name ?? '(not set)' ?> <?= $model->user->last_name ?? '' ?></h4>
                            <!-- <span> - Web Developer</span> -->
                        <?php } else { ?>
                            <?= $model->user->username ?? '' ?>
                        <?php } ?>
                        </div>
                    </div>

                    <ul class="d-flex nav nav-pills mb-0 text-center profile-tab nav-slider" data-bs-toggle="slider-tab" id="profile-pills-tab" role="tablist">
                        <li>Driver Status: &nbsp; <?= $model->user ? $model->user->stateBadges() : '' ?> |&nbsp; </li>
                        <li class="ms-2"> Verification Status: &nbsp; <?= $model->getVerifyOptionsBadges() ?></li>
                    </ul>

                    <ul class="d-flex nav nav-pills mb-0 text-center profile-tab nav-slider" data-bs-toggle="slider-tab" id="profile-pills-tab" role="tablist">
                        <li>
                            <ul class="d-flex nav nav-pills mb-0 text-center profile-tab nav-slider">
                                <?php if ($model->is_verified == DriverDetails::PENDING) { ?>
                                    <a href="#" id="verified" data-status="1" data-id="<?= $model->id ?>" >
                                        <li class="badge bg-success pt-2 pb-2">
                                            <i class="fa fa-check"> </i> Verify
                                        </li>
                                    </a>
                                    <a href="#" id="verified-change" data-bs-toggle="modal" data-bs-target="#exampleModalCenter">
                                        <li class="badge bg-danger pt-2 pb-2" style="margin-left:10px">
                                            <i class="fa fa-times"> </i> Reject
                                        </li>
                                    </a>
                                <?php }
                                if ($model->is_verified == DriverDetails::VERIFIED) { ?>
                                    <a href="#" id="verified-change" data-bs-toggle="modal" data-bs-target="#exampleModalCenter">
                                        <li class="badge bg-danger pt-2 pb-2" style="margin-left:10px">
                                            <i class="fa fa-times"> </i> Reject
                                        </li>
                                    </a>
                                <?php }
                                if ($model->is_verified == DriverDetails::REJECTED) { ?>
                                    <a href="#" id="verified" data-status="1" data-id="<?= $model->id ?>">
                                        <li class="badge bg-success pt-2 pb-2">
                                            <i class="fa fa-check"> </i> Verify
                                        </li>
                                    </a>
                                <?php } ?>
                            </ul>
                        </li>

                    </ul>

                </div>
            </div>
        </div>
    </div>


    <div class="col-lg-3">
        <div class="card">
            <div class="card-header">
                <div class="header-title">
                    <h4 class="card-title">About</h4>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-1">Email: <?= isset($model->user->email) ? $model->user->email : "(not set)" ?></div>
                <div class="mb-1">Phone: <?= isset($model->user->contact_no) ? $model->user->contact_no : "(not set)" ?></div>
                <div>Home Address: <span class="ms-3"><?= isset($model->address) ? $model->address : "(not set)" ?></span></div>
                <div>DOB: <span class="ms-3"><?= isset($model->user->date_of_birth) ? $model->user->date_of_birth : "(not set)" ?></span></div>
                <div>Gender: <span class="ms-3"><?= isset($model->user->gender) ? $model->user->gender : "(not set)" ?></span></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="header-title">
                    <h4 class="card-title">Vehicle</h4>
                </div>
            </div>
            <div class="card-body">
                <ul class="list-inline m-0 p-0">
                    <li class="d-flex mb-4 align-items-center active">
                        <img src=" <?= isset($model->vehical->image) ? $model->vehical->image : "(not set)" ?> " alt="story-img" class="rounded-pill avatar-70 p-1 border bg-soft-light img-fluid">
                        <div class="ms-3">
                            <h5><?= isset($model->vehical->title) ? $model->vehical->title : "(not set)" ?></h5>
                            <p class="mb-0"><?= isset($model->vehical_type) ? $model->vehical_type : "(not set)" ?></p>
                            <p class="mb-0"><?= isset($model->vehical_owner) ? $model->vehical_owner : "(not set)" ?></p>
                        </div>
                    </li>

                    <li><b>Model:-   </b><?= $model->model_name ?></li>

                </ul>
            </div>
        </div>


        <div class="card">
            <div class="card-header">
                <div class="header-title">
                    <h4 class="card-title">Bank Details</h4>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-1">Bank Name: <?= isset($model->bankDetail->bank_name) ? $model->bankDetail->bank_name : "(not set)" ?></div>
                <div class="mb-1">Account Number: <?= isset($model->bankDetail->account_number) ? $model->bankDetail->account_number : "(not set)" ?></div>
                <div>IFSC: <span class="ms-3"><?= isset($model->bankDetail->ifsc_code) ? $model->bankDetail->ifsc_code : "(not set)" ?></span></div>
                <div>UPI Id: <span class="ms-3"><?= isset($model->bankDetail->upi_id) ? $model->bankDetail->upi_id : "(not set)" ?></span></div>
            </div>
        </div>

    </div>
    <div class="col-lg-9">
        <div class="profile-content tab-content">
            <div id="profile-feed" class="tab-pane fade active show">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between ">
                        <div class="header-title">
                            <div class="d-flex flex-wrap">

                                <div class="media-support-info mt-2">
                                    <h5 class="mb-4">Driving License</h5>
                                    <h6>License No. <?= isset($model->license_no) ? $model->license_no : "(not set)" ?></h6>

                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="card-body p-0">
                        <div class="user-post">

                            <div class="row p-4">

                                <div class="col-lg-6 ">
                                    <h6>License Front</h6>
                                    <img src="<?= isset($model->proof_of_license) ? $model->proof_of_license : "(not set)" ?>" alt="post-image" class="img-fluid rounded" style="width: 300px;     height: 200px;">

                                </div>
                                <div class="col-lg-6">
                                    <h6>License Back</h6>

                                    <img src="<?= isset($model->proof_of_license_back) ? $model->proof_of_license_back : "(not set)" ?>" alt="post-image" class="img-fluid rounded" style="width: 300px;     height: 200px;">

                                </div>
                            </div>

                        </div>

                    </div>

                    <div class="card-body p-0">
                        <div class="user-post">

                            <div class="row p-4">
                            <h6>Chasis No. <?= isset($model->chassis_number) ? $model->chassis_number : "(not set)" ?></h6><br>
                                <div class="col-lg-6 ">
                                    <h6>Chasis image</h6>
                                    <img src="<?= isset($model->chassis_image) ? $model->chassis_image : "(not set)" ?>" alt="post-image" class="img-fluid rounded" style="width: 300px;     height: 200px;">

                                </div>
                               
                            </div>

                        </div>

                    </div>

                </div>

            </div>


        </div>

        <div class="profile-content tab-content">
            <div id="profile-feed" class="tab-pane fade active show">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between ">
                        <div class="header-title">
                            <div class="d-flex flex-wrap">

                                <div class="media-support-info mt-2">
                                    <h5 class="mb-4">Vehicle Registrarion</h5>
                                    <h6>RC No. <?= isset($model->rc_number) ? $model->rc_number : "(not set)" ?></h6>

                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="card-body p-0">
                        <div class="user-post">

                            <div class="row p-4">

                                <div class="col-lg-6 ">
                                    <h6>RC Front</h6>
                                    <img src="<?= isset($model->rc_proof) ? $model->rc_proof : "(not set)" ?>" alt="post-image" class="img-fluid rounded" style="width: 300px;     height: 200px;">

                                </div>
                                <div class="col-lg-6">
                                    <h6>RC Back</h6>

                                    <img src="<?= isset($model->rc_proof_back) ? $model->rc_proof_back : "(not set)" ?>" alt="post-image" class="img-fluid rounded" style="width: 300px;     height: 200px;">

                                </div>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


        </div>


        <div class="profile-content tab-content">
            <div id="profile-feed" class="tab-pane fade active show">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between ">
                        <div class="header-title">
                            <div class="d-flex flex-wrap">

                                <div class="media-support-info mt-2">
                                    <h5 class="mb-4">Aadhar</h5>
                                    <h6>Aadhar No. <?= isset($model->adhaar_number) ? $model->adhaar_number : "(not set)" ?></h6>

                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="card-body p-0">
                        <div class="user-post">

                            <div class="row p-4">

                                <div class="col-lg-6 ">
                                    <h6>Aadhar Front</h6>
                                    <img src="<?= isset($model->adhaar_front) ? $model->adhaar_front : "(not set)" ?>" alt="post-image" class="img-fluid rounded" style="width: 300px;     height: 200px;">

                                </div>
                                <div class="col-lg-6">
                                    <h6>Aadhar Back</h6>

                                    <img src="<?= isset($model->adhaar_back) ? $model->adhaar_back : "(not set)" ?>" alt="post-image" class="img-fluid rounded" style="width: 300px;     height: 200px;">

                                </div>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


        </div>
        <?php if (!empty($model->pan_number)) { ?>
            <div class="profile-content tab-content">
                <div id="profile-feed" class="tab-pane fade active show">
                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between ">
                            <div class="header-title">
                                <div class="d-flex flex-wrap">

                                    <div class="media-support-info mt-2">
                                        <h5 class="mb-4">Pan Card</h5>
                                        <h6>Pan No. <?= isset($model->pan_number) ? $model->pan_number : "(not set)" ?></h6>

                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="card-body p-0">
                            <div class="user-post">

                                <div class="row p-4">

                                    <div class="col-lg-6 ">
                                        <h6>Pan Front</h6>
                                        <img src="<?= isset($model->pan_front) ? $model->pan_front : "(not set)" ?>" alt="post-image" class="img-fluid rounded" style="width: 300px;     height: 200px;">

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

<div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLongTitle">Rejection Reason</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <textarea class="form-control" id="rejection-reason" name="rejection_reason" required></textarea> 
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-primary" id="verified" data-bs-dismiss="modal" data-status="3" data-id="<?= $model->id ?>">Submit</button>
      </div>
    </div>
  </div>
</div>





<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<script>
    $(document).on('click', '#verified', function() {
        var rejectionReason = $('#rejection-reason').val()??null;
        var id = $(this).attr('data-id');
        var val = $(this).attr('data-status');;
        console.log(id);
        $.ajax({
            type: "POST",

            url: "<?= Url::toRoute(['driver-details/update-verify-status']) ?>",


            data: {
                id: id,
                val: val,
                rejectionReason:rejectionReason
            },
            success: function(data) {
                swal("Great!", "Status Successfully Changed!", "success");
                location.reload();
            }

        });
    });
</script>