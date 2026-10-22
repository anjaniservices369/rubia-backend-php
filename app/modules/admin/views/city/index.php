<?php

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

use yii\helpers\Html;
use kartik\export\ExportMenu;
use app\models\User;
use app\modules\admin\models\base\Banner;
use app\modules\admin\models\base\City;
use app\modules\admin\models\base\CityRateCard;

use kartik\grid\GridView;
use yii\helpers\Url;

$this->title = 'States';
$this->params['breadcrumbs'][] = $this->title;
$search = "$('.search-button').click(function(){
	$('.search-form').toggle(1000);
	return false;
});";
$this->registerJs($search);
?>

    <div class="card">
       <div class="card-body">
       <div class="btn-group">
   
</div>

<h2>States</h2>
    <?= GridView::widget([
        'dataProvider' => $statesDataProvider,
        'columns' => [
            [
                'attribute' => 'id',
                'label' => 'State ID',
            ],
            [
                'attribute' => 'state_name',
                'label' => 'State Name',
                'format' => 'raw',
                'value' => function ($model) {
                    return Html::a($model->state_name, ['districts', 'stateId' => $model->id]);
                },
            ],
        ],
    ]) ?>
 
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<script>
    $(document).on('change', 'select[id^=status_list_]', function () {
        var id = $(this).attr('data-id');
        var val = $(this).val();

        $.ajax({
            type: "POST",
            url: "/rubia_backend/gii/default/status-change",
            data: {id: id, val: val},
            success: function (data) {
                swal("Good job!", "Status Successfully Changed!", "success");
            }
        });
    });
</script>
