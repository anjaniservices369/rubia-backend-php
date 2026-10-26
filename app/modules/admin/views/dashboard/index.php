<?php
/* @var $this \yii\web\View */

use app\models\User;
use app\modules\admin\models\Orders;
use app\modules\admin\models\Store;
use app\modules\admin\models\StoreEarnings;
use miloschuman\highcharts\Highcharts;
use yii\helpers\Url;

$this->title = 'Dashboard';
$this->params['subheading'] = '';

?>
<div class="rb-dash">
<?php if (\Yii::$app->user->identity->user_role == User::ROLE_ADMIN) { ?>
    <?php if (!empty($operations)) : $op = $operations; ?>
    <!-- Operations -->
    <section class="content rb-wide">
        <div class="container-fluid">
            <div class="card">
                <div class="card-title p-3">
                    <h4>Operations</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-3 col-6">
                            <div class="small-box <?= $op['taxiOnline'] > 0 ? 'bg-success' : 'bg-warning' ?>">
                                <div class="inner">
                                    <h3><?= (int) $op['taxiOnline'] ?> <small>/ <?= (int) $op['taxiDrivers'] ?></small></h3>
                                    <p>Taxi Drivers Online</p>
                                </div>
                                <div class="icon"><i class="ion ion-person"></i></div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box <?= $op['waitingRides'] > 0 ? 'bg-warning' : 'bg-info' ?>">
                                <div class="inner">
                                    <h3><?= (int) $op['waitingRides'] ?></h3>
                                    <p>Rides Waiting for a Driver<?= $op['waitingRides'] > 0 ? ' (oldest ' . (int) $op['oldestWaitingMinutes'] . ' min)' : '' ?><?= $op['staleRides'] > 0 ? ' &middot; ' . (int) $op['staleRides'] . ' older than 24 h never closed' : '' ?></p>
                                </div>
                                <div class="icon"><i class="ion ion-clock"></i></div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box <?= $op['noDriver24h'] > 0 ? 'bg-danger' : 'bg-info' ?>">
                                <div class="inner">
                                    <h3><?= (int) $op['noDriver24h'] ?></h3>
                                    <p>No Driver Found (last 24 h)</p>
                                </div>
                                <div class="icon"><i class="ion ion-alert"></i></div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-secondary">
                                <div class="inner">
                                    <h3><?= (int) $op['mediaFiles'] ?> <small><?= $op['mediaMb'] ?> MB</small></h3>
                                    <p>Uploaded Images / Documents</p>
                                </div>
                                <div class="icon"><i class="ion ion-image"></i></div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box <?= $op['push']['ready'] ? 'bg-success' : 'bg-danger' ?>">
                                <div class="inner">
                                    <h3><?= \yii\helpers\Html::encode($op['push']['driver']) ?></h3>
                                    <p>Push Notifications: <?= \yii\helpers\Html::encode($op['push']['note']) ?></p>
                                </div>
                                <div class="icon"><i class="ion ion-android-notifications"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($showDriverBookings)) : ?>
    <section class="content">
        <div class="container-fluid">

            <div class="card">
                <div class="card-title p-3">
                    <h4>Driver Bookings</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-3 col-6">

                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3><?php echo ($modelAllBookings); ?></h3>
                                    <p>All Bookings</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-bag"></i>
                                </div>
                                <!-- <a href="<?= Url::to('deals') ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a> -->
                            </div>
                        </div>

                        <div class="col-lg-3 col-6">

                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3><?php echo ($modelCompleteBookings); ?></h3>
                                    <p>Completed Bookings</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-stats-bars"></i>
                                </div>
                                <!-- <a href="<?= Url::to('deals') ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a> -->
                            </div>
                        </div>

                        <div class="col-lg-3 col-6">

                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3><?php echo ($modelCancelledBookings); ?></h3>
                                    <p>Cancelled Bookings</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-person-add"></i>
                                </div>
                                <!-- <a href="<?= Url::to('users') ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a> -->
                            </div>
                        </div>

                        <div class="col-lg-3 col-6">

                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3><?php echo ($modelPendingBookings); ?></h3>
                                    <p>Waiting for Driver</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-pie-graph"></i>
                                </div>
                                <!-- <a href="<?= Url::to('report') ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a> -->
                            </div>
                        </div>

                        <div class="col-lg-3 col-6">

                            <div class="small-box bg-dark">
                                <div class="inner">
                                    <h3><?php echo ($modelOngoingBookings); ?></h3>
                                    <p>Ongoing Rides</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-pie-graph"></i>
                                </div>
                                <!-- <a href="<?= Url::to('report') ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a> -->
                            </div>
                        </div>

                        <div class="col-lg-3 col-6">

                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3><?php echo ($modelDriverRejectBookings); ?></h3>
                                    <p>Bookings Rejected by Drivers</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-pie-graph"></i>
                                </div>
                                <!-- <a href="<?= Url::to('report') ?>" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a> -->
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>
<?php } ?>

<?php foreach ($rideBlocks as $block) {
    echo $this->render('_ride_category', ['label' => $block['label'], 'stats' => $block['stats']]);
} ?>
</div>

