<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use kartik\grid\GridView;
use app\models\User;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\FavouriteLocation */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Favourite Locations', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="favourite-location-view">
<div class="card">
       <div class="card-body">
    <div class="row">
        <div class="col-sm-9">
            <h2><?= 'Favourite Location'.' '. Html::encode($this->title) ?></h2>
        </div>
        <div class="col-sm-3" style="margin-top: 15px">
            
            <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
                          <?php  if(\Yii::$app->user->identity->user_role==User::ROLE_ADMIN || \Yii::$app->user->identity->user_role==User::ROLE_SUBADMIN){ ?>
             <?= Html::a('Delete', ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => 'Are you sure you want to delete this item?',
                    'method' => 'post',
                ],
            ])
            ?>  
             <?php  } ?>
        </div>
    </div>
    </div>
    </div>
    <div class="card">
       <div class="card-body">

    <div class="row">
<?php 
    $gridColumn = [
        ['attribute' => 'id', 'visible' => false],
        'user_id',
        'latitude',
        'logitude',
        'address_type',
        'address',
        'pincode',
        'status',
        'created_on',
        'updated_on',
        'update_user_id',
        'create_user_id',
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

