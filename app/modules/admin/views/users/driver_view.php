<?php

use app\models\User;
use app\modules\admin\models\Booking;
use app\modules\admin\models\DriverAadharCard;
use app\modules\admin\models\DriverBankDetails;
use app\modules\admin\models\DriverPanCards;
use app\modules\admin\models\DriverVehicleTypes;
use app\modules\admin\models\DriverWallet;
use app\modules\admin\models\DrivingLicense;
use app\modules\admin\models\VehicleTypes;
use app\modules\admin\models\base\DriverEmergencyContacts;
use yii\helpers\Html;
use yii\widgets\DetailView;
use kartik\grid\GridView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Users */

//$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Users'), 'url' => ['index']];
//$this->params['breadcrumbs'][] = $this->title;
$driver_aadhar_card = DriverAadharCard::find()->where(['driver_id'=>$model->id])->one();
$driver_pan_cards = DriverPanCards::find()->where(['driver_id'=>$model->id])->one();
$driver_bank_details = DriverBankDetails::find()->where(['driver_id'=>$model->id])->one();
$driving_license = DrivingLicense::find()->where(['driver_id'=>$model->id])->one();
$TotalRaids = Booking::find()->where(['driver_id'=>$model->id])->count();
$driver_vehicle_types = DriverVehicleTypes::find()->where(['driver_id'=>$model->id])->all();
$booking = Booking::find()->where(['driver_id'=>$model->id])->count();
$booking_completed = Booking::find()->where(['driver_id'=>$model->id])->andWhere(['status'=>Booking::STATUS_RIDE_COMPLETED])->count();

?>



<div class="conatiner-fluid content-inner mt-n5 py-0">

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between">
                    <div class="d-flex flex-wrap align-items-center">

                    <ul class="d-flex nav nav-pills mb-0 text-center profile-tab nav-slider" data-bs-toggle="slider-tab" id="profile-pills-tab" role="tablist">
                        <li>Driver Status: &nbsp;<?= $model->stateBadges() ?>|&nbsp; </li>
                    </ul>

                    <ul class="d-flex nav nav-pills mb-0 text-center profile-tab nav-slider" data-bs-toggle="slider-tab" id="profile-pills-tab" role="tablist">
                        <li>Driver Online Status: &nbsp;<?= $model->getOnlineStatusBadges() ?>|&nbsp; </li>
                    </ul>


                    <ul class="d-flex nav nav-pills mb-0 text-center profile-tab nav-slider" data-bs-toggle="slider-tab" id="profile-pills-tab" role="tablist">
                        <li>Police Verification: &nbsp;<?= $model->policeVerificationBadges() ?>|&nbsp; </li>
                        <li class="ms-2"> Profile Verification: &nbsp; <?= $model->profileVerificationBadges() ?></li>
                    </ul>


                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
			
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between">
                    <div class="d-flex flex-wrap align-items-center">
                        <div class="profile-img position-relative me-3 mb-3 mb-lg-0 profile-logo profile-logo1">
                            <img src="<?= !empty($model->profile_image) ? $model->profile_image : '../../web/images/avatar.png' ?>"  alt="User-Profile" class=" img-fluid rounded-pill avatar-100" width="100px">

                        </div>
                        <div class="d-flex flex-wrap align-items-center mb-3 mb-sm-0">
                             <span class="driver-id">Driver ID: <?= $model->id ?> / <?= $model->first_name.' '.$model->last_name ?></span>
                            </div>

                            <style>
                                .driver-id {
                                    color: #1b2a49; /* Set your desired color for the User ID */
                                    font-weight: bold;
                                    font-size: 25px; /* Set your desired font size for the User ID */
                                    /* Add any other styling you want for the User ID */
                                }
                            </style>



                    </div>

                 

                    

                    <ul class="d-flex nav nav-pills mb-0 text-center profile-tab nav-slider" data-bs-toggle="slider-tab" id="profile-pills-tab" role="tablist">
                        <li>
                            <ul class="d-flex nav nav-pills mb-0 text-center profile-tab nav-slider">
                           <?php if ($model->profile_verified == User::profile_verified_yes): ?>
    <a href="#" type="button" id="verified" data-bs-toggle="modal" data-bs-target="#exampleModalCenter">
        <li class="badge bg-danger pt-2 pb-2" style="margin-left: 10px">
            <i class="fa fa-times"></i> Reject
        </li>
    </a>
<?php else: ?>
    <a href="#" type="button" id="verified" onclick='userVerify("<?= $model->id ?>", <?= User::profile_verified_yes ?>)'>
        <li class="badge bg-success pt-2 pb-2">
            <i class="fa fa-check"></i> Verify
        </li>
    </a>
<?php endif; ?>

                                   
                                                            </ul>
                        </li>

                    </ul>
                    

                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">

    <div class="col-lg-3">


        <div class="card">
            <div class="card-header">
                <div class="header-title">
                    <h4 class="card-title">About</h4>
                </div>
            </div>

        <div class="card-body">
                <div class="mb-1">Email: <?= $model->email ?></div>
                <div class="mb-1">Phone:  <?= $model->contact_no ?></div>
                <div>Home Address: <span class="ms-3"><?= $model->address ?></span></div>
                <div>DOB: <span class="ms-3"><?= $model->date_of_birth ?></span></div>
                <div>Gender: <span class="ms-3"><?= $model->gender ?></span></div>
            </div>
        </div>

     
       


        <div class="card">
            <div class="card-header">
                <div class="header-title">
                    <h4 class="card-title">Admin Per:
                        <form id="set-admin-per"> 
                            <input type="number" name="admin_per"  class="form-control" value="<?= $model->admin_per ?>" >
                            <input type="hidden" name="user_id"  value="<?= $model->id ?>">
                    <button  class="btn btn-success btn-sm" id="submit-admin-per" type="button">SET</button>
                 </form></h4>
                </div>
            </div>

 
        </div>


 

        <div class="card">
            <div class="card-header">
                <div class="header-title">
                    <h4 class="card-title">Wallet: <?= DriverWallet::availableBalance($model->id); ?>/-</h4>
                </div>
            </div>

 
        </div>



        <div class="card">
            <div class="card-header">
                <div class="header-title">
                    <h4 class="card-title">Bookings: <?= $booking; ?></h4>
                </div>
            </div>

  


            <div class="card-header">
                <div class="header-title">
                    <h4 class="card-title">Completed Bookings: <?= $booking_completed; ?></h4>
                </div>
            </div>


          
        </div>









      <div class="card">
    <div class="card-header">
        <div class="header-title">
            <h4 class="card-title">Vehicles</h4>
        </div>
    </div>
    
    <form id="vehicle_edit"> 
        <h5>Automatic:</h5>
        <?php
        if (!empty($driver_vehicle_types)) {
            
            $automatic_vehicles = VehicleTypes::find()->where(['type'=>1])->all();

            foreach($automatic_vehicles as $automaticvehicles){
                $vehicle_id =$automaticvehicles->id;
                $checked = "";
                
                foreach($driver_vehicle_types as $drivervehicletypes){
                    $selected_vehicle_id = $drivervehicletypes->vehicle_type_id;
                    if($selected_vehicle_id == $vehicle_id ){
                        $checked = "checked";
                    }
                } ?>
                <input type="checkbox" name="vehicle_types[]" value = "<?=$automaticvehicles->id ?>" <?= $checked ?> ><?=$automaticvehicles->title ?><br>
                
        <?php   }?>

        <h5>Manual: </h5>

       <?php  $manual_vehicles = VehicleTypes::find()->where(['type'=>2])->all();

            foreach($manual_vehicles as $manualvehicles){
            $vehicle_id =$manualvehicles->id;
            $checked = "";
            
            foreach($driver_vehicle_types as $drivervehicletypes){
                $selected_vehicle_id = $drivervehicletypes->vehicle_type_id;
                if($selected_vehicle_id == $vehicle_id ){
                    $checked = "checked";
                }
            } ?>
            <input type="checkbox" name="vehicle_types[]" value = "<?=$manualvehicles->id ?>" <?= $checked ?> ><?=$manualvehicles->title ?><br>
            
    <?php   }?>
 
    


        <input type="hidden" name="user_id"  value="<?= $model->id ?>">
        
        <button class="btn btn-success btn-sm" id="vehicle_submit" type="button">Edit</button>
    </form>

    <?php   }?>
</div>


        <div class="card">
            <div class="card-header">
                <div class="header-title">
                    <h4 class="card-title">Bank Details</h4>
                </div>
            </div>
            <?php if(!empty($driver_bank_details)){ ?>

            <div class="card-body">
                <div class="mb-1">Bank Name: <?= $driver_bank_details->account_holder_name ?></div>
                <div class="mb-1">Account Number:  <?= $driver_bank_details->account_number ?></div>
                <div>IFSC: <span class="ms-3"><?= $driver_bank_details->ifsc ?></span></div>
                <div>Bank name: <span class="ms-3"><?= $driver_bank_details->bank_name ?></span></div>
                <div>Upi: <span class="ms-3"><?= $driver_bank_details->upi ?></span></div>

            </div>
            <?php } ?>

            
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
                                    <h6>License No. <?= !empty($driving_license->driving_license_number) ? $driving_license->driving_license_number : '' ?></h6>
                                   <h6>Issued Date. <?= !empty($driving_license->issued_date) ? $driving_license->issued_date : '' ?></h6>
                                   <h6>Expire Date. <?= !empty($driving_license->expire_date) ? $driving_license->expire_date : '' ?></h6>

                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="card-body p-0">
                        <div class="user-post">
                            <?php if(!empty($driving_license->driving_license_front)&& !empty($driving_license->driving_license_back)) { ?>
                            <div class="row p-4">

                                <div class="col-lg-6 ">
                                    <h6>License Front</h6>
                                    <?php if (strpos($driving_license->driving_license_front, 'uploads') !== false) { 
                                            $driving_license_front = Yii::getAlias('@web') . '/' .  $driving_license->driving_license_front;
                                        } else {
                                            $driving_license_front =  $driving_license->driving_license_front;
                                        }
                                        ?>
                                    <img src="<?= $driving_license_front ?>" alt="post-image" class="img-fluid rounded" style="width: 300px;     height: 200px;">
                                   

                                </div>
                                <div class="col-lg-6">
                                    <h6>License Back</h6>
                                    <?php if (strpos($driving_license->driving_license_back, 'uploads') !== false) { 
                                            $driving_license_back = Yii::getAlias('@web') . '/' .  $driving_license->driving_license_back;
                                        } else {
                                            $driving_license_back =  $driving_license->driving_license_back;
                                        }
                                        ?>

                                    <img src="<?= $driving_license_back ?>" alt="post-image" class="img-fluid rounded" style="width: 300px;     height: 200px;">


                                   
                                </div>
                            </div>
                            <?php } ?>

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
                                    <h6>Aadhar No. <?=  !empty($driver_aadhar_card->aadhar_number) ? $driver_aadhar_card->aadhar_number : '' ?></h6>

                                </div>
                            </div>
                        </div>
 
                    </div>
                    <div class="card-body p-0">
                        <div class="user-post">
                        <?php  if(!empty($driver_aadhar_card->aadhar_card_front) && !empty($driver_aadhar_card->aadhar_card_back)) { ?>
                            <div class="row p-4">

                                <div class="col-lg-6 ">
                                    <h6>Aadhar Front</h6>
                                    <?php if (strpos($driver_aadhar_card->aadhar_card_front, 'uploads') !== false) { 
                                            $aadhar_card_front = Yii::getAlias('@web') . '/' .  $driver_aadhar_card->aadhar_card_front;
                                        } else {
                                            $aadhar_card_front =  $driver_aadhar_card->aadhar_card_front;
                                        }
                                        ?>
                                    <img src="<?= $aadhar_card_front ?>" alt="post-image" class="img-fluid rounded" style="width: 300px;     height: 200px;">
                                  
                                </div>
                                <div class="col-lg-6">
                                    <h6>Aadhar Back</h6>
                                    <?php if (strpos($driver_aadhar_card->aadhar_card_back, 'uploads') !== false) { 
                                            $aadhar_card_back = Yii::getAlias('@web') . '/' .  $driver_aadhar_card->aadhar_card_back;
                                        } else {
                                            $aadhar_card_back =  $driver_aadhar_card->aadhar_card_back;
                                        }
                                        ?>

                                    <img src="<?= $aadhar_card_back ?>" alt="post-image" class="img-fluid rounded" style="width: 300px;     height: 200px;">
                                    

                                </div>
                            </div>
                            <?php } ?>
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
                                        <h5 class="mb-4">Pan Card</h5>
                                        <h6>Pan No. <?= !empty($driver_pan_cards->pan_number) ? $driver_pan_cards->pan_number : '' ?></h6>

                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="card-body p-0">
                            <div class="user-post">
                                <?php if(!empty($driver_pan_cards->pan_card)) { ?>
                                <div class="row p-4">

                                    <div class="col-lg-6 ">
                                        <h6>Pan Front</h6>
                                        <?php if (strpos($driver_pan_cards->pan_card, 'uploads') !== false) { 
                                            $pan_card_path = Yii::getAlias('@web') . '/' .  $driver_pan_cards->pan_card;
                                        } else {
                                            $pan_card_path =  $driver_pan_cards->pan_card;
                                        }
                                        ?>
                                        <img src="<?= $pan_card_path ?>" alt="post-image" class="img-fluid rounded" style="width: 300px; height: 200px;">
                                       

                                    </div>

                                </div>
                                <?php } ?>





                            </div>

                        </div>

                    </div>

                </div>


            </div>
            </div>

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
        <button class="btn btn-primary" data-bs-dismiss="modal" onclick='userVerify("<?= $model->id ?>",<?= User::profile_verified_rejected ?>)'>Submit</button>
      </div>
    </div>
  </div>
</div>






    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>


    <script>
        let baseUrl = "<?= Url::base() ?>"
        function userVerify(user_id,status){
            var rejectionReason = $('#rejection-reason').val()?? null;
            console.log(rejectionReason);
            console.log(user_id);
            $.ajax({
                url:baseUrl+'/admin/users/user-verify-profile',
                type:'POST',
                data:{
                    user_id:user_id,
                    status:status,
                    rejectionReason:rejectionReason
                },
                success:function(response){
                       swal("Good job!", "Status Successfully Changed!", "success");
                       location.reload();

                }
            })
      
 
        }

$("#submit-admin-per").on("click",function(){

    let form_data = $('#set-admin-per').serialize();
    $.ajax({
        url:"<?=Url::to(['/admin/users/admin-per-set'])?>",
        type:'post',
        data:form_data,
        success:function(res){
            swal("Good job!", "Status Successfully Changed!", "success");
            location.reload();
        }
    })

})

$("#vehicle_submit").on("click", function() {
    //$("input[type=checkbox][name='field_name[]']:not(:checked)").siblings("input[type=hidden]").remove();

    let form_data = $('#vehicle_edit').serialize();
    $.ajax({
        url: "<?= Url::to(['/admin/users/vehicle-edit']) ?>",
        type: 'post',
        data: form_data,
        success: function(res) {
            swal("Good job!", "Status Successfully Changed!", "success");
             location.reload();
        }
    });
});

    </script>