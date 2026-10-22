<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $model app\modules\admin\models\WebSetting */

?>
<div class="web-setting-view">

    <div class="row">
        <div class="col-sm-9">
            <h2><?= Html::encode($model->name) ?></h2>
        </div>
    </div>

    <div class="row">
<?php 
    $gridColumn = [
        'setting_id',
        'name',
        'setting_key',
        'value:ntext',
        'type_id',
        'status',
        ['attribute' => 'created_date', 'visible' => false],
        ['attribute' => 'updated_date', 'visible' => false],
        ['attribute' => 'create_user_id', 'visible' => false],
        ['attribute' => 'updated_user_id', 'visible' => false],
    ];
    echo DetailView::widget([
        'model' => $model,
        'attributes' => $gridColumn
    ]); 
?>
    </div>
</div>