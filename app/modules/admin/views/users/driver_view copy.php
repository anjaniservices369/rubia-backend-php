<?php

use app\modules\admin\models\Booking;
use app\modules\admin\models\DriverAadharCard;
use app\modules\admin\models\DriverBankDetails;
use app\modules\admin\models\DriverPanCards;
use app\modules\admin\models\DrivingLicense;
use yii\helpers\Html;
use yii\widgets\DetailView;
use kartik\grid\GridView;

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


?>
<section class="content">
<div class="container-fluid">
<div class="row">
<div class="col-md-3">

<div class="card card-primary card-outline">
<div class="card-body box-profile">
<div class="text-center">
<img class="profile-user-img img-fluid img-circle" src="<?= !empty($model->profile_image) ? $model->profile_image : '../../web/images/avatar.png' ?>" alt="User profile picture">
</div>
<h3 class="profile-username text-center"><?= $model->first_name.' '.$model->last_name ?></h3>
<p class="text-muted text-center"><?= $model->user_role ?></p>
<ul class="list-group list-group-unbordered mb-3">

<li class="list-group-item">
<b>Registration Date</b> <a class="float-end"><?= date('d-M-Y',$model->created_at) ?></a>
</li>


<li class="list-group-item">
<b>Online Status</b> <a class="float-end"><?= $model->onlineBadges() ?></a>
</li>



<li class="list-group-item">
<b>Referral code</b> <a class="float-end"><?= $model->referal_code ?></a>
</li>


<li class="list-group-item">
<b>Rating</b> <a class="float-end"><?= $model->average_rating ?></a>
</li>

<li class="list-group-item">
<b>Total Orders </b> <a class="float-end">1,322</a>
</li>
<li class="list-group-item">
<b>Total Earnings</b> <a class="float-end">543</a>
</li>
<li class="list-group-item">
<b>Wallet Balance</b> <a class="float-end">13,287</a>
</li>

 


<li class="list-group-item">
<b>Profile Verification</b> <a class="float-end"><?= $model->profileVerificationBadges() ?></a>
</li>





<li class="list-group-item">
<b>Police Verification</b> <a class="float-end"><?= $model->policeVerificationBadges() ?></a>
</li>


<li class="list-group-item">
<b>Paid Joining Fee</b> <a class="float-end"><?= $model->paid_amount ?></a>
</li>




</ul>
</div>

</div>


<div class="card card-primary">
<div class="card-header">
<h3 class="card-title">Bank Details</h3>
</div>

<div class="card-body">

<?php if(!empty($driver_bank_details)) { ?>


<p class="fst-italic mb-1">Account Holder Name : <?= $driver_bank_details->account_holder_name ?></p>
                <p class="fst-italic mb-1">Account Number : <?= $driver_bank_details->account_number ?></p>
                <p class="fst-italic mb-1">IFSC : <?= $driver_bank_details->ifsc ?></p>
                <p class="fst-italic mb-1">Bank Name : <?= $driver_bank_details->bank_name ?></p>
                <p class="fst-italic mb-1">Upi : <?= $driver_bank_details->upi ?></p>
                <?php } ?>



</div>

</div>

</div>






<div class="col-md-9">
<div class="card">
<div class="card-header p-2">
<ul class="nav nav-pills">
<li class="nav-item"><a class="nav-link active" href="#activity" data-bs-toggle="tab">Aadhar  Card</a></li>
<li class="nav-item"><a class="nav-link" href="#timeline" data-bs-toggle="tab">Pan Card</a></li>
<li class="nav-item"><a class="nav-link" href="#settings" data-bs-toggle="tab">Driving License</a></li>
</ul>
</div>
<div class="card-body">
<div class="tab-content">
<div class="active tab-pane" id="activity">

<?php
if(!empty($driver_aadhar_card)) { ?>

<div class="row">
<div class="col-sm-6">
<img class="img-fluid mb-3" src="<?= $driver_aadhar_card->aadhar_card_front ?>" alt="Photo">
</div>


<div class="col-sm-6">
<img class="img-fluid mb-3" src="<?= $driver_aadhar_card->aadhar_card_back ?>" alt="Photo">
</div>

</div>


<?php  } ?>
 

 

</div>

<div class="tab-pane" id="timeline">





<?php
if(!empty($driver_pan_cards)) { ?>

<div class="row">
<div class="col-sm-6">
<img class="img-fluid mb-3" src="<?= $driver_pan_cards->pan_card ?>" alt="Photo">
</div>

</div>


<?php  } ?>



</div>

<div class="tab-pane" id="settings">

<?php
if(!empty($driving_license)) { ?>

<div class="row">
<div class="col-sm-6">
<img class="img-fluid mb-3" src="<?= $driving_license->driving_license_front ?>" alt="Photo">
</div>

<div class="col-sm-6">
<img class="img-fluid mb-3" src="<?= $driving_license->driving_license_back ?>" alt="Photo">
</div>


</div>


<?php  } ?>

</div>

</div>

</div>
</div>

</div>










</div>

<div class="row">




<div class="col-md-12">
<div class="card">
<div class="card-header p-2">
<ul class="nav nav-pills">
<li class="nav-item"><a class="nav-link active" href="#Bookings" data-bs-toggle="tab">Bookings</a></li>
<li class="nav-item"><a class="nav-link" href="#completed" data-bs-toggle="tab">completed</a></li>
<li class="nav-item"><a class="nav-link" href="#Cancelled" data-bs-toggle="tab">Cancelled</a></li>
</ul>
</div>
<div class="card-body">
<div class="tab-content">
<div class="active tab-pane" id="Bookings">

Bookings




</div>

<div class="tab-pane" id="completed">


completed


</div>

<div class="tab-pane" id="Cancelled">

cancelled

</div>

</div>

</div>
</div>

</div>

</div>




<div class="row">




<div class="col-md-12">
<div class="card">
<div class="card-header p-2">
<ul class="nav nav-pills">
<li class="nav-item"><a class="nav-link active" href="#Earnings" data-bs-toggle="tab">Earnings</a></li>
<li class="nav-item"><a class="nav-link" href="#Wallet" data-bs-toggle="tab">Wallet Transactions</a></li>
</ul>
</div>
<div class="card-body">
<div class="tab-content">
<div class="active tab-pane" id="Earnings">

Earnings




</div>

<div class="tab-pane" id="Wallet">


Wallet


</div>



</div>

</div>
</div>

</div>

</div>









</div>
</section>