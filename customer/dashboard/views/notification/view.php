<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use kartik\grid\GridView;
use app\models\User;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\Notification */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Notifications'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="notification-view">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-sm-9">
                    <h2><?= Yii::t('app', 'Notification') . ' ' . Html::encode($this->title) ?></h2>
                </div>
         
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">

            <div class="row">
                <?php
                $gridColumn = [
                    'title',
                    'order_id',
                    'user_id',
                    'module',
                    'icon',
                    'mark_read',
                    'model_type',
                    'check_on_ajax',
                    'status',
                    'created_on',
                    'updated_on',
                ];
                echo DetailView::widget([
                    'model' => $model,
                    'attributes' => $gridColumn
                ]);
                ?>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="row">
                <h4>User<?= ' ' . Html::encode($this->title) ?></h4>
            </div>
            <?php
            $gridColumnUser = [
                'username',
                'auth_key',
                'password_hash',
                'password_reset_token',
                'email',
                'first_name',
                'last_name',
                'contact_no',
                'alternative_contact',
                'date_of_birth',
                'gender',
                'description',
                'address',
                'profile_image',
                'user_role',
                'oauth_client_user_id',
                'oauth_client',
                'access_token',
                'device_token',
                'device_type',
                'status',
                'referal_code',
                'referal_id',
                'signup_type',
                'is_deleted',
                'info_delete',
            ];
            echo DetailView::widget([
                'model' => $model,
                'attributes' => $gridColumnUser
            ]);
            ?>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="row">
                <h4>User<?= ' ' . Html::encode($this->title) ?></h4>
            </div>
            <?php
            $gridColumnUser = [
                'username',
                'auth_key',
                'password_hash',
                'password_reset_token',
                'email',
                'first_name',
                'last_name',
                'contact_no',
                'alternative_contact',
                'date_of_birth',
                'gender',
                'description',
                'address',
                'profile_image',
                'user_role',
                'oauth_client_user_id',
                'oauth_client',
                'access_token',
                'device_token',
                'device_type',
                'status',
                'referal_code',
                'referal_id',
                'signup_type',
                'is_deleted',
                'info_delete',
            ];
            echo DetailView::widget([
                'model' => $model,
                'attributes' => $gridColumnUser
            ]);
            ?>
        </div>
    </div>
</div>