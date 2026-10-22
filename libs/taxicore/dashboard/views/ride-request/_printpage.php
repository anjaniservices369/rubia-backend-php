<?php

use app\modules\admin\models\base\RideEarnings;
use app\modules\admin\models\base\RideRequest;
use app\modules\admin\models\WebSetting;

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>



<body>
    <section>

        <table class="table table-borderless" style="border: none !important;">
            <thead>
                <tr>
                    <th style="margin-top: 0;">
                        <h5><b>Payment Summary</b></h5>
                    </th>
                    <th width="10"><img src="https://ik.imagekit.io/tcz2d20pj/_E-Go-logo-3-white-final-2_1_.png?ik-sdk-version=javascript-1.4.3&updatedAt=1673071903649" alt="" width="60px" srcset=""></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <h5>Ride Id:</h5>
                    </td>
                    <td>
                        <h5><?= $ride_request->id ?></h5>
                    </td>
                </tr>
                <tr>
                    <td>
                        <h5>Time Of Ride:</h5>
                    </td>
                    <td>
                        <h5><?= date('d-m-Y H:i', strtotime($ride_request->created_on)) ?></h5>
                    </td>
                </tr>


            </tbody>
        </table>

        <table>
            <tr>
                <td>
                    <h3 style="text-align: center;" class="text-center">Total : <?php echo 'Rs ' . $ride_request->final_price ?></h3>
                </td>
            </tr>
        </table>

        <table class="table">
            <thead>
                <tr class="breaklines">
                    <td class="wrap-text">
                        <p> <strong> Pickup Location :</strong> <?= $ride_request->pickup_address ?></p>
                    </td>

                    <td class="wrap-text">
                        <p><strong> Drop Location : </strong> <?= $ride_request->drop_address ?></p>
                    </td>
                </tr>
            </thead>
        </table>
        <table class="table">
            <thead>

                <tr class="breaklines">
                    <td class="">
                        <h5><b>Bill Details</b></h5>

                    </td>

                </tr>
            </thead>
        </table>
        <?php



        $settings = new WebSetting();
        $sgst = $settings->getSettingBykey('sgst');
        $cgst = $settings->getSettingBykey('cgst');
        $totalTaxPercentage = $sgst + $cgst;
        $rideEarnings = RideEarnings::find()->where(['ride_id' => $ride_request->id])->one();
        // $totalTaxAmount = ($ride_request->final_price * $totalTaxPercentage) / 100;
        $totalTaxAmount = $rideEarnings->admin_earning;
        $amountWithoutTax = $ride_request->final_price - $rideEarnings->admin_earning;


        $finalAmount = $ride_request->final_price;
        $adminCommision = $ride_request->rideCommision->commision;
        $adminCommisionAmount = ($finalAmount * $adminCommision) / 100;
        $driverEarnings = $finalAmount - $adminCommisionAmount;



        ?>
        <table class="table">
            <thead>
                <tr class="breaklines">
                    <th class="">
                        <p style="color:#1b2a49"> <strong> Ride Charges </strong></p>
                    </th>

                    <th class="">
                        <p style="color:#1b2a49">Rs. <?= round($driverEarnings,2) ?> </p>
                    </th>
                </tr>

                <tr class="breaklines">
                    <th class="">
                        <p style="color:#1b2a49"> <strong> Taxes & Charges </strong></p>
                    </th>

                    <th class="">
                        <p style="color:#1b2a49"> Rs. <?= round($adminCommisionAmount,2) ?> </p>
                    </th>
                </tr>

                <tr class="breaklines" style="border-top: 2.4px solid #dbdbdb !important; ">
                    <th class="">
                        <p style="color:#1b2a49"> <strong> Total Amount </strong></p>
                    </th>

                    <th class="">
                        <p style="color:#1b2a49"> Rs. <?= round($finalAmount,2) ?> </p>
                    </th>
                </tr>

            </thead>
        </table>
        <table class="table">
            <thead>
                <tr class="breaklines">
                    <th class="">
                        <?php if ($ride_request->payment_method == RideRequest::PAYMENT_METHOD_CASH) { ?>
                            <p style="color:#1b2a49 !important;"> <strong style="color:#1b2a49 !important;"> Paid Using : CASH </strong></p>

                        <?php } else if ($ride_request->payment_method ==  RideRequest::PAYMENT_METHOD_ONLINE) { ?>
                            <p style="color:#1b2a49 !important;"> <strong style="color:#1b2a49 !important;"> Paid Using : ONLINE </strong></p>

                        <?php  } else if ($ride_request->payment_method ==  RideRequest::PAYMENT_METHOD_WALLET) { ?>
                            <p style="color:#1b2a49 !important;"> <strong style="color:#1b2a49 !important;"> Paid Using : WALLET </strong></p>

                        <?php } ?>
                    </th>


                </tr>



            </thead>
        </table>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br><br>
        <br>
        <br><br>
        <br>
        <br><br>
        <br>
        <br>

        <table class="table table-borderless">
            <thead>
                <tr>
                    <th style="margin-top: 0;">
                        <h5><b>Tax Invoice</b></h5>
                        <p>#<?= $ride_request->id ?></p>
                    </th>
                    <th width="10"><img src="https://ik.imagekit.io/tcz2d20pj/_E-Go-logo-3-white-final-2_1_.png?ik-sdk-version=javascript-1.4.3&updatedAt=1673071903649" alt="" width="60px" srcset=""></th>
                </tr>
            </thead>

        </table>
        <table class="table">
            <thead>
                <tr class="breaklines">
                    <th class="">
                        <p style="color:#1b2a49"> <strong> Invoice No:- </strong></p>
                    </th>

                    <th class="" width="">
                        <p style="color:#1b2a49"> #<?= $ride_request->id ?> </p>
                    </th>
                </tr>

                <tr class="breaklines">
                    <th class="">
                        <p style="color:#1b2a49"> <strong> Invoice Date:-
                            </strong></p>
                    </th>

                    <th class="" width="">
                        <p style="color:#1b2a49"> <?= date('d-m-Y H:i', strtotime($ride_request->created_on)) ?>
                        </p>
                    </th>
                </tr>


                <tr class="breaklines" style="">
                    <th class="">
                        <p style="color:#1b2a49"> <strong> State:- </strong></p>
                    </th>

                    <th class="" width="">
                        <p style="color:#1b2a49"> Maharashtra:- </p>
                    </th>
                </tr>

                <tr class="breaklines" style="">
                    <th class="">
                        <p style="color:#1b2a49"> <strong> Tax Category:-
                            </strong></p>
                    </th>

                    <th class="" width="">
                        <p style="color:#1b2a49"> Other local transportation <br> services of
                            passengers n.e.c. (996419) </p>
                    </th>
                </tr>

                <tr class="breaklines" style="">
                    <th class="">
                        <p style="color:#1b2a49"> <strong> Place of Supply:-

                            </strong></p>
                    </th>

                    <th class="" width="">
                        <p style="color:#1b2a49"> <?= $ride_request->city->name??'' ?> </p>
                    </th>
                </tr>
                <tr class="breaklines" style="">
                    <th class="">
                        <p style="color:#1b2a49"> <strong> GST Number:-


                            </strong></p>
                    </th>

                    <th class="" width="">
                        <p style="color:#1b2a49"> 27CSCPP4037J1ZJ </p>
                    </th>
                </tr>

                <tr class="breaklines" style="">
                    <th class="">
                        <p style="color:#1b2a49"> <strong> Driver Name:-



                            </strong></p>
                    </th>

                    <th class="" width="">
                        <p style="color:#1b2a49"> <?= isset($ride_request->driver->first_name) ? $ride_request->driver->first_name : $ride_request->driver->username ?? "(Not Assigned)" ?> </p>
                    </th>
                </tr>
                <tr class="breaklines" style="">
                    <th class="">
                        <p style="color:#1b2a49"> <strong> Vehicle Number:- </strong></p>
                    </th>

                    <th class="" width="">
                        <p style="color:#1b2a49"><?= isset($ride_request->driverDetail->vehical_number) ? $ride_request->driverDetail->vehical_number : "(Not Assigned)" ?></p>
                    </th>
                </tr>
                <tr class="breaklines" style="">
                    <th class="">
                        <p style="color:#1b2a49"> <strong> Chassis Number:- </strong></p>
                    </th>

                    <th class="" width="">
                        <p style="color:#1b2a49"><?= isset($ride_request->driverDetail->chassis_number) ? $ride_request->driverDetail->chassis_number : "(Not Assigned)" ?></p>
                    </th>
                </tr>
                <tr class="breaklines" style="">
                    <th class="">
                        <p style="color:#1b2a49"> <strong> Customer Name:-
                            </strong></p>
                    </th>

                    <th class="" width="">
                        <p style="color:#1b2a49"><?= ($ride_request->customer['name'] ?: $ride_request->customer['phone']) ?: "(Not Assigned)" ?></p>
                    </th>
                </tr>
                <tr class="breaklines" style="">
                    <th class="">
                        <p style="color:#1b2a49"> <strong> Customer Pick Up <br> Address:-
                            </strong></p>
                    </th>

                    <th class="" width="">
                        <p style="color:#1b2a49"><?= isset($ride_request->pickup_address) ? $ride_request->pickup_address :  "(Not Assigned)" ?></p>
                    </th>
                </tr>
            </thead>
        </table>

        <table class="table">
            <thead>

                <tr class="breaklines">
                    <td class="">
                        <h5><b>Bill Details</b></h5>

                    </td>

                </tr>
            </thead>
        </table>

        <table class="table">
            <thead>


                <?php $rideEarnings = RideEarnings::find()->where(['ride_id' => $ride_request->id])->one();
                $sgst = $settings->getSettingBykey('sgst');
                $cgst = $settings->getSettingBykey('cgst');
                if (!empty($rideEarnings)) {


                    $sgstprice = ($driverEarnings * $sgst) / 100;
                    $cgstprice = ($driverEarnings * $cgst) / 100;

                    $totalTaxPercentage = $sgst + $cgst;
                    $totalAwt  = $driverEarnings - ($sgstprice + $cgstprice);
                    $totalTaxAmount = ($rideEarnings->driver_earning * $totalTaxPercentage) / 100;

                    // $amountWithoutTax = $rideEarnings->driver_earning - $totalTaxAmount;
                } else {
                    $sgstprice = 0;
                    $cgstprice = 0;
                    // $amountWithoutTax = 0;
                }


                ?>

                <tr class="breaklines" style="">
                    <th class="">
                        <p style="color:#1b2a49"> <strong> Driver Fee </strong></p>
                    </th>


                    <th class="" width="">

                        <p style="color:#1b2a49"> Rs <?= round($totalAwt,2) ?></p>
                    </th>
                </tr>

                <tr class="breaklines" style="">
                    <th class="">
                        <p style="color:#1b2a49"> <strong> CGST (<?= $cgst ?>%) </strong></p>
                    </th>

                    <th class="" width="">
                        <p style="color:#1b2a49">Rs. <?= round($cgstprice, 2) ?> </p>
                    </th>
                </tr>
                <tr class="breaklines" style="">
                    <th class="">
                        <p style="color:#1b2a49"> <strong> SGST (<?= $sgst ?>%) </strong></p>
                    </th>

                    <th class="" width="">
                        <p style="color:#1b2a49">Rs. <?= round($sgstprice, 2) ?> </p>
                    </th>
                </tr>
                <tr class="breaklines" style="border-top: 2.4px solid #dbdbdb !important;">
                    <th class="">
                        <p style="color:#1b2a49"> <strong> Ride Charge </strong></p>
                    </th>

                    <th class="" width="">
                        <p style="color:#1b2a49"> Rs <?= round($driverEarnings, 2) ?> </p>
                    </th>
                </tr>
            </thead>
        </table>



        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br>
        <br><br>
        <br>
        <br><br>
        <br>


        <table class="table table-borderless">
            <thead>
                <tr>
                    <th style="margin-top: 0;">
                        <h5><b>Tax Invoice</b></h5>
                        <p>#<?= $ride_request->id ?></p>
                    </th>
                    <th width="10"><img src="https://ik.imagekit.io/tcz2d20pj/_E-Go-logo-3-white-final-2_1_.png?ik-sdk-version=javascript-1.4.3&updatedAt=1673071903649" alt="" width="60px" srcset=""></th>
                </tr>
            </thead>

        </table>
        <table class="table">
            <thead>
                <tr class="breaklines">
                    <th class="">
                        <p style="color:#1b2a49"> <strong> EasyGo Transport Service </strong></p>
                        <p style="color:#7393B3">Near Meenatai Thakare Chowk, 4th Floor, B/402, Sai Anand
                            Plaza, Mumbai-Agra Road, Castle Mill Naka, Thane, Thane,
                            Maharashtra, 400601
                        </p>
                    </th>


                </tr>

                <tr class="breaklines">
                    <th class="">
                        <p style="color:#1b2a49"> <strong> <?= ($ride_request->customer['name'] ?: $ride_request->customer['phone']) ?: "(Not Assigned)" ?> </strong></p>
                        <p style="color:#7393B3"><?= isset($ride_request->pickup_address) ? $ride_request->pickup_address : $ride_request->pickup_address ?? "(Not Assigned)" ?>
                        </p>
                    </th>


                </tr>


                <tr class="breaklines">
                    <th class="">
                        <p style="color:#1b2a49"> <strong> Invoice No:- </strong></p>
                    </th>

                    <th class="" width="">
                        <p style="color:#1b2a49"> #<?= $ride_request->id ?> </p>
                    </th>
                </tr>

                <tr class="breaklines">
                    <th class="">
                        <p style="color:#1b2a49"> <strong> Invoice Date:-
                            </strong></p>
                    </th>

                    <th class="" width="">
                        <p style="color:#1b2a49"> <?= date('d-m-Y H:i', strtotime($ride_request->created_on)) ?>
                        </p>
                    </th>
                </tr>



                <tr class="breaklines" style="">
                    <th class="">
                        <p style="color:#1b2a49"> <strong> Tax Category:-
                            </strong></p>
                    </th>

                    <th class="" width="">
                        <p style="color:#1b2a49"> Other local transportation <br> services of
                            passengers n.e.c. (996419) </p>
                    </th>
                </tr>

                <tr class="breaklines" style="">
                    <th class="">
                        <p style="color:#1b2a49"> <strong> Place of Supply:-

                            </strong></p>
                    </th>

                    <th class="" width="">
                        <p style="color:#1b2a49"> <?= $ride_request->city->name??'' ?> </p>
                    </th>
                </tr>
                <tr class="breaklines" style="">
                    <th class="">
                        <p style="color:#1b2a49"> <strong> GST Number:-


                            </strong></p>
                    </th>

                    <th class="" width="">
                        <p style="color:#1b2a49"> 27CSCPP4037J1ZJ </p>
                    </th>
                </tr>

            </thead>
        </table>

        <table class="table">
            <thead>

                <tr class="breaklines">
                    <td class="">
                        <h5><b>Bill Details</b></h5>

                    </td>

                </tr>
            </thead>
        </table>

        <table class="table">
            <thead>


                <?php $rideEarnings = RideEarnings::find()->where(['ride_id' => $ride_request->id])->one();
                $sgst = $settings->getSettingBykey('sgst');
                $cgst = $settings->getSettingBykey('cgst');
                if (!empty($rideEarnings)) {


                    $sgstprice = ($adminCommisionAmount * 9) / 100;
                    $cgstprice = ($adminCommisionAmount * 9) / 100;

                    $totalTaxPercentage = $sgst + $cgst;

                    $totalTaxAmount = ($adminCommisionAmount * $totalTaxPercentage) / 100;

                    $amountWithoutTax = $adminCommisionAmount - $sgstprice- $cgstprice;
                } else {
                    $sgstprice = 0;
                    $cgstprice = 0;
                    $amountWithoutTax = 0;
                }


                ?>

                <tr class="breaklines" style="">
                    <th class="">
                        <p style="color:#1b2a49"> <strong> Transaction Fee </strong></p>
                    </th>

                    <th class="" width="">
                        <p style="color:#1b2a49"> Rs <?= round($amountWithoutTax ?? '0', 2) ?> </p>
                    </th>
                </tr>

                <tr class="breaklines" style="">
                    <th class="">
                        <p style="color:#1b2a49"> <strong> CGST (9%) </strong></p>
                    </th>

                    <th class="" width="">
                        <p style="color:#1b2a49">Rs. <?= round($cgstprice, 2) ?> </p>
                    </th>
                </tr>
                <tr class="breaklines" style="">
                    <th class="">
                        <p style="color:#1b2a49"> <strong> SGST (9%) </strong></p>
                    </th>

                    <th class="" width="">
                        <p style="color:#1b2a49">Rs. <?= round($sgstprice, 2) ?> </p>
                    </th>
                </tr>
                <tr class="breaklines" style="border-top: 2.4px solid #dbdbdb !important;">
                    <th class="">
                        <p style="color:#1b2a49"> <strong> Final Amount </strong></p>
                    </th>

                    <th class="" width="">
                        <p style="color:#1b2a49"> Rs <?= round($adminCommisionAmount ?? '0', 2) ?></p>
                    </th>
                </tr>
            </thead>
        </table>
    </section>
</body>

</html>