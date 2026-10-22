<?php
/* @var $this \yii\web\View */
/* @var $label string  e.g. "Bike Taxi" */
/* @var $stats array   keys: all, completed, cancelled, pending, ongoing, noDriver */

use yii\helpers\Html;

$cards = [
    ['bg-info', 'all', 'All Bookings', 'ion-bag'],
    ['bg-success', 'completed', 'Completed Bookings', 'ion-stats-bars'],
    ['bg-danger', 'cancelled', 'Cancelled Bookings', 'ion-person-add'],
    ['bg-warning', 'pending', 'Waiting for Driver', 'ion-pie-graph'],
    ['bg-secondary', 'noDriver', 'No Driver Found', 'ion-person'],
    ['bg-dark', 'ongoing', 'Ongoing Rides', 'ion-pie-graph'],
];
?>
<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-title p-3">
                <h4><?= Html::encode($label) ?></h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($cards as [$bg, $key, $text, $icon]) : ?>
                        <div class="col-lg-3 col-6">
                            <div class="small-box <?= $bg ?>">
                                <div class="inner">
                                    <h3><?= (int) $stats[$key] ?></h3>
                                    <p><?= Html::encode($text) ?></p>
                                </div>
                                <div class="icon">
                                    <i class="ion <?= $icon ?>"></i>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>