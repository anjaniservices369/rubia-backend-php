<?php
use yii\widgets\DetailView;
use app\modules\admin\models\DrivingLicense;
use yii\helpers\Html;
use app\modules\admin\forms\UserForm;

use kartik\grid\GridView;
use app\models\User;
use app\modules\admin\models\DriverDetails;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\User */
/* @var $drivingLicenseModel app\modules\admin\models\DrivingLicense */

$this->title = Yii::t('app', 'View User License: ') . $model->username;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="card">
    <div class="row">
        <div class="card-heading">
            <h5 class="mb-2">User License Details</h5>
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
                                        <h6>License No. <?= isset($drivingLicenseModel->license_no) ? $drivingLicenseModel->license_no : "(not set)" ?></h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="user-post">
                                <div class="row p-4">
                                    <div class="col-lg-6 ">
                                        <h6>License Front</h6>
                                        <?= DetailView::widget([
                                            'model' => $drivingLicenseModel,
                                            'attributes' => [
                                                [
                                                    'attribute' => 'front_image',
                                                    'format' => ['image', ['width' => '300', 'height' => '200']],
                                                ],
                                            ],
                                        ]); ?>
                                    </div>
                                    <div class="col-lg-6">
                                        <h6>License Back</h6>
                                        <?= DetailView::widget([
                                            'model' => $drivingLicenseModel,
                                            'attributes' => [
                                                [
                                                    'attribute' => 'back_image',
                                                    'format' => ['image', ['width' => '300', 'height' => '200']],
                                                ],
                                            ],
                                        ]); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <?php
            echo DetailView::widget([
                'model' => $model, // Assign the correct model here
                'attributes' => [
                    'id',
                    'username',
                    'email',
                    // Add other attributes as needed
                ],
            ]);
            ?>
        </div>

    </div>
</div>
