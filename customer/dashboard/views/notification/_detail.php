<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\Notification */

?>
<div class="notification-view">

    <div class="row">
        <div class="col-sm-9">
            <h2><?= Html::encode($model->title) ?></h2>
        </div>
    </div>

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