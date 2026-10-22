<?php

use app\modules\admin\models\OrderDetails;
use app\modules\admin\models\Orders;
use app\modules\admin\models\WebSetting;
use yii\helpers\Html;

/* @var $this \yii\web\View view component instance */
/* @var $message \yii\mail\MessageInterface the message being composed */
/* @var $content string main view render result */
?>
<?php $this->beginPage() ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?= Yii::$app->charset ?>" />
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>

<body>
    <?php $this->beginBody() ?>

    <style>
        body {
            font-family: Helvetica, sans-serif;
            font-size: 13px;
        }

        .container {
            max-width: 680px;
            margin: 0 auto;
        }

        .logotype {
            background: #000;
            color: #fff;
            width: 75px;
            height: 75px;
            line-height: 75px;
            text-align: center;
            font-size: 11px;
        }

        .column-title {
            background: #eee;
            text-transform: uppercase;
            padding: 15px 5px 15px 15px;
            font-size: 11px
        }

        .column-detail {
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
        }

        .column-header {
            background: #eee;
            text-transform: uppercase;
            padding: 15px;
            font-size: 11px;
            border-right: 1px solid #eee;
        }

        .row {
            padding: 7px 14px;
            border-left: 1px solid #eee;
            border-right: 1px solid #eee;
            border-bottom: 1px solid #eee;
        }

        .alert {
            background: #ffd9e8;
            padding: 20px;
            margin: 20px 0;
            line-height: 22px;
            color: #333
        }

        .socialmedia {
            background: #eee;
            padding: 20px;
            display: inline-block
        }
    </style>
    </head>

    <body>
        <?php
        $order_id =  Yii::$app->request->post('order_id');

    //    $order_id = $order_id;
        $order_id = (int)$order_id;
        $orders = Orders::find()->where(['id' => $order_id])->one();
        // var_dump();exit;
        $orderItems = OrderDetails::find()->where(['order_id' => $orders->id])->all();
        ?>
        <div class="container">
            <table width="100%">
                <tr>
                    <td width="75px">
                        <div class="logotype"><img src="https://alladinice.in/wp-content/uploads/2022/10/logo-150x150.png" width="80" height="80"></img></div>
                    </td>
                    <td width="300px">
                        <div style="background: #257cff;border-left: 15px solid #fff; color:#fff; padding-left: 30px;font-size: 26px;font-weight: bold;letter-spacing: -1px;height: 73px;line-height: 75px;">Order invoice</div>
                    </td>
                    <td></td>
                </tr>
            </table>
            <br><br>
            <h4 style="padding-bottom: 0; margin-bottom: 0;">Greetings from Alladin Ice</h4>
            <p>Your order was delivered! Rate your fast delivery in the app</p>
            <table width="100%" style="border-collapse: collapse;">
                <h3>Order Summery</h3>

                <tr>
                    <td width="50%" style="background:#eee;padding:20px;">
                        <h4>Vendor Details:</h4> <br>

                        <strong>Order-id:</strong> <?= $orders->id ?><br>

                        <strong>Vendor Name:</strong><?= $orders->store->store_name ?><br>
                        <strong>Vendor Address:</strong> <?= $orders->store->street . '&nbsp;' . $orders->store->post_code ?><br>

                        <strong>Order placed at:</strong> <?= $orders->created_on ?><br>
                        <strong>Order delivered at:</strong> <?= $orders->updated_on ?><br>
                    </td>
                    <td style="background:#eee;padding:20px;">
                        <h4>Delivery To:</h4> <br>

                        <?php if (!empty($orders->user->first_name)) { ?>
                            <strong>Name:</strong><?php echo $orders->user->first_name ?><br>
                        <?php } else { ?>
                            <strong>Name:</strong><?php echo $orders->user->username ?><br>

                        <?php } ?>
                        <?php if (!empty($orders->deliveryAddress)) { ?>
                            <strong>Customer Address:</strong> <?php echo $orders->deliveryAddress->location . $orders->deliveryAddress->pincode ?><br>
                        <?php } else { ?>
                            <strong>Customer Address:</strong> <?php echo ""; ?><br>

                        <?php } ?>
                        <strong>Order Status: </strong><?php echo $orders->getOrderStatusOptionsBadges(); ?><br>

                        <strong>Phone no.:</strong> <?php echo $orders->user->contact_no ?? '' ?><br>
                    </td>
                </tr>
            </table><br>


            <div style="background: #257cff url(https://cdn4.iconfinder.com/data/icons/basic-ui-2-line/32/shopping-cart-shop-drop-trolly-128.png) no-repeat;width: 50px;height: 50px;margin-right: 10px;background-position: center;background-size: 25px;float: left; margin-bottom: 15px;"></div>
            <h3>Your Items</h3>

            <table width="100%" style="border-collapse: collapse;border-bottom:2px solid #eee;">
                <tr>
                    <td width="40%" class="column-header">id</td>
                    <td width="20%" class="column-header">Item Name</td>
                    <td width="20%" class="column-header">Amount</td>

                </tr>
                <?php foreach ($orderItems as $orderItem) { ?>
                    <tr>
                        <td class="row"><span style="color:#777;font-size:11px;"></span><?php echo $orderItem->id ?></td>
                        <td class="row"><?php echo $orderItem->categoryItems->title ?></td>

                        <td class="row"><?php echo $orderItem->amount ?></td>



                        <?php $setting = new WebSetting();
                        $pakingcharge = (int)$setting->getSettingBykey('packing_charges'); ?>

                    </tr>
                <?php } ?>

            </table><br>
            <table width="100%" style="background:#eee;padding:20px;">
                <tr>
                    <td>
                        <table width="300px" style="float:right">

                            <tr>
                                <td><strong>Taxes and Packing Charges:</strong></td>
                                <td style="text-align:right"> Rs.<?php echo $orders->tax ?></td>
                            </tr>
                            <tr>

                                <td><strong>Delivery Fee:</strong></td>
                                <td style="text-align:right"><?php echo $orders->delivery_charge ?></td>
                            </tr>
                            <tr>
                                <td><strong>Coupon Discount:</strong></td>

                                <td style="text-align:right">Rs.-<?php echo $orders->coupon_discount ?></td>
                            </tr>
                            <tr>
                                <td><strong>Grand total:</strong>
                                </td>
                                <td style="text-align:right"><?php echo $orders->total_w_tax ?></td>


                            </tr>



                        </table>
                    </td>
                </tr>
            </table>
            <div class="socialmedia">The link to rate your delivery experience is only valid for mobile devices.</small></div>
            <div class="alert">Disclaimer: Attached is the invoice for the restaurant services provided by the outlet. For items not covered in the attached invoice, the outlet shall be responsible to issue an invoice directly to you.</div>

        </div><!-- container -->
        <?php $this->endBody() ?>
    </body>

</html>
<?php $this->endPage() ?>