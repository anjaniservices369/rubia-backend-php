<?php

use kartik\helpers\Html;
use yii\helpers\Url;

?>
<!-- <style>
  
</style> -->
<div class="page-layout">
    <!-- HEADER -->
    <div class="page-header">
        <table width="200">
            <tbody>
                <tr>
                    <td width="400" height="200" style="background-image:url('https://alladinice.in/wp-content/uploads/2022/10/logo-150x150.png'); background-size: 30px 30px;  background-repeat: no-repeat;">

                    </td>
                    <td width="500">
                        <h3 style="text-decoration: underline; font-weight: 900;">TAX INVOICE</h3>
                    </td>
                </tr>
            </tbody>
        </table>


        <table class="table-header" width="800">
            <tbody>
                <tr>
                    <td class="font12"><strong>Invoice No. Alladin-ice-<?= $model->id ?></strong></td>
                    <td class="font14"><strong>CUSTOMER DETAIL</strong></td>

                </tr>
                <tr>
                    <td class="font11" width="600">
                        <strong>Order Id. <?= $model->id ?></strong>
                        <p style="word-wrap: break-word;"><strong>Store Name:- </strong> <?php echo $model->store->store_name  ?></p>

                        <p><strong>Store Address:- </strong><?php echo $model->store->street . '&nbsp;' . $model->store->post_code ?></p>
                        <p><b>Store Number:</b> <?php echo $model->store->store_phone ?? '(Not Added)' ?></p>
                        <p><b>Store Licence No.:</b> <?php echo $model->store->store_licence_no ?? '(Not Added)' ?></p>
                    </td>

                    <td class="font11" width="400">
                        <p><strong>Name:- </strong><?php echo $model->user->first_name ?? 'Not Added' ?></p>
                        <address><strong>Delivery Address:- </strong><?php echo $model->deliveryAddress->location . '&nbsp;' . $model->deliveryAddress->pincode ?></address>
                        <p><b>Phone Number:-</b> <?php echo $model->user->contact_no ?? '(Not Added)' ?></p>
                        <?php if (!empty($model->user->gst_number)) { ?>
                            <p><b>GST Number:-</b> <?php echo $model->user->gst_number ?? '(Not Added)' ?></p>
                        <?php } ?>
                    </td>

                </tr>
                <tr>

                </tr>
            </tbody>
        </table>
        <hr>


        <table class="table-header" width="">
            <tbody>
                <!-- <tr>
                    <td class="font12" width="800"></td>

                </tr> -->
                <tr>
                    <td class="font11" width="800">
                        <p> <strong>Order Date & Time:- </strong><?= $model->created_on ?></p>
                        <p> <strong>Delivery Date & Time:- </strong> <?= $model->updated_on ?></p>

                        <p><strong>Delivery Type:- </strong><?php echo strip_tags($model->getDeliveryTypeOptionsBadges()) ?></p>

                    </td>

                    <td class="font11">

                        <p><strong>Order Type:- </strong><?php echo strip_tags($model->getOrderTypeOptionsBadges()) ?></p>
                        <p><strong>Payment Status:- </strong><?php echo strip_tags($model->getPaymentStatusBadges()) ?></p>
                        <p><strong>Payment Type:- </strong><?php echo strip_tags($model->getPaymentTypeBadges()) ?></p>
                    </td>

                </tr>
                <tr>

                </tr>
            </tbody>
        </table>
    </div>
    <!-- /HEADER -->

    <!-- /endif pages header -->
    <!-- CONTENT -->

    <div class="page-content">
        <table class="table-content" width="1100" style="border: 2px solid #eee; margin-top: 20px;">
            <thead width="">
                <tr width="" class="tab-border">
                    <th width="" style="border-bottom: solid 2px #eee ;">#</th>
                    <th width="" style="border-bottom: solid 2px #eee ;">PRODUCT NAME</th>
                    <th style="border-bottom: solid 2px #eee ;">QUANTITY</th>
                    <th style="border-bottom: solid 2px #eee ;">TAX %</th>
                    <th style="border-bottom: solid 2px #eee ;">AMOUNT</th>
                </tr>
            </thead>
            <tbody>

                <!-- /endif pages content -->
                <?php $i = 1;
                foreach ($model->orderDetail as $orderDetail) { ?>
                    <tr>


                        <td> <?php echo $i; ?> </td>
                        <td><?php echo $orderDetail->categoryItems->title ?></td>
                        <td><?php echo $orderDetail->qty ?></td>
                        <td><?php echo $orderDetail->categoryItems->tax_percentage ?></td>

                        <td><?php echo $orderDetail->amount ?></td>


                    </tr>
                <?php $i++;
                } ?>
                <!-- COUNTER ITEM -->
            </tbody>
        </table>
    </div>
    <!-- /CONTENT -->
    <!-- FOOTER -->
    <div class="page-footer">
        <table class="table-footer" width="900" style="margin-top:20px ;">
            <tbody>
                <tr>
                    <td class="grid-1">
                        <p class="font9"><?= (!empty($model->keterangan)) ? '<i>##</i>' . $model->keterangan : NULL ?></p>
                    </td>
                    <td class="grid-2 text-right" width="300">
                        <p class="font12"><strong></strong></p>
                        <table>
                            <tbody>
                                <tr>

                                    <td></td>
                                    <td></td>
                                    <td class="font9" width="100"><strong>Sub Total</strong></td>
                                    <td class="font9">:</td>
                                    <td class="font9 text-right"> Rs. <?php echo $model->sub_total ?></td>
                                </tr>
                                <tr>
                                    <td class="font9 text-right"></td>
                                    <td></td>
                                    <td class="font9" width="200">Taxes & <br>Packing Charges</td>
                                    <td class="font9">:</td>
                                    <td class="font9 text-right"> Rs. <?php echo $model->tax ?></td>
                                </tr>
                                <tr>
                                    <td colspan="2"></td>
                                    <td class="font9" width="60">Delivery Charged</td>
                                    <td class="font9">:</td>
                                    <td class="font9 text-right"> Rs. <?php echo $model->delivery_charge ?></td>
                                </tr>
                                <tr>
                                    <td class="font9 text-right"></td>
                                    <td></td>
                                    <td class="font9" width="60">Coupon Discount</td>
                                    <td class="font9">:</td>
                                    <td class="font9 text-right"> Rs. <?php echo $model->coupon_discount ?? 'Not Applied' ?></td>
                                </tr>

                                <tr>
                                    <td colspan="2"></td>
                                    <td class="font10" width="60"><strong>TOTAL</strong></td>
                                    <td class="font10">:</td>
                                    <td class="font10 text-right"><strong> Rs. <?php echo $model->total_w_tax ?></strong></td>

                                </tr>

                            </tbody>

                            <tr>


                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="grid-1"></td>

                    <td class="grid-2 font10 money text-justify"></td>
                </tr>

            </tbody>
        </table>
    </div>

    <!-- /COUNTER ITEM -->
</div>