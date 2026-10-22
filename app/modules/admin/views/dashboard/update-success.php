<?php
// die('dddd');
use app\modules\admin\models\UserEmergencyContacts;
use yii\helpers\Html;
use yii\widgets\DetailView;
use kartik\grid\GridView;
use app\modules\admin\models\base\Booking;
use app\modules\admin\models\User;
use yii\data\ActiveDataProvider;
/* @var $this yii\web\View */
/* @var $model app\models\Users */

//$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Users'), 'url' => ['index']];
//$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-view">
<div class="card">
    <div class="card-body">
    <div class="row">
        <div class="col-sm-9">
            <h2><?= Yii::t('app', 'Admin').' '. Html::encode($this->title) ?></h2>
        </div>
        <div class="col-sm-3" style="margin-top: 15px">   
            <?= Html::a(Yii::t('app', 'Update Profile'), ['update-profile', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
           
        </div>
    </div>
    <div class="row">
        <?php 
            $gridColumn = [
                ['attribute' => 'id', 'visible' => false],
              //  'username',
                'email',
                'first_name',
                'last_name',
              //  'contact_no',
              //  'user_role'
            ];
            echo DetailView::widget([
                'model' => $model,
                'attributes' => $gridColumn
            ]);
        ?>
    </div>
    </div>
    </div>
    </div>
    <?php 