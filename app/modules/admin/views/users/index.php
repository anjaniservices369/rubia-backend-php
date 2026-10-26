<?php

use app\models\User;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\Pjax;
use kartik\grid\GridView;
use app\modules\admin\widgets\BoxGridView;
use app\modules\admin\widgets\LinkedColumn;
use app\modules\admin\Module as AdminModule;
use kartik\switchinput\SwitchInput;
use kartik\export\ExportMenu; // Add the ExportMenu widget

/* @var $this yii\web\View */
/* @var $searchModel app\models\UserSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Customers-profile';
$this->params['breadcrumbs'][] = $this->title;

$js = <<<JS
    function sendRequest(status, id){
       if(status == true){
           val = 1;
       }else{
           val = 0;
       }
        $.ajax({
            url:'users/update-status',
            method:'post',
            data:{val:val,id:id},
            success:function(data){
              alert('status updated');
            },
            error:function(jqXhr,status,error){
                alert(error);
            }
        });
    }
JS;

$this->registerJs($js, \yii\web\View::POS_READY);
?>

<div class="user-index">
    <div class="card">
        <div class="card-body">
            
            <!-- Export CSV button -->
            <?= ExportMenu::widget([
                'dataProvider' => $dataProvider,
                'columns' => [
                    // List the columns you want to include in the CSV export
                    'id',
                    'first_name',
                    'email',
                    'contact_no',
                    'status',
                    'created_at:date',
                    // Add other columns as needed
                ],
                'exportConfig' => [
                    ExportMenu::FORMAT_CSV => [
                        'label' => 'CSV', // Label for the CSV export option
                        'icon' => 'export', // Icon CSS class (you can use 'export' or any other CSS class)
                        'alertMsg' => 'The CSV export file will be generated for download.', // Alert message
                        'options' => ['title' => 'CSV', 'class' => 'btn btn-success'], // Button options
                    ],
                ],
                'dropdownOptions' => [
                    'label' => 'Export',
                    'class' => 'btn btn-secondary text-white',
                    'itemsBefore' => [
                        '<div class="dropdown-header">Export All Data</div>',
                    ],
                ],
                'target' => ExportMenu::TARGET_BLANK, // Open the export link in a new tab
            ]) ?>

            <!-- GridView with data -->
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],
                    'id',
                    [
                        'class' => LinkedColumn::class,
                        'header' => '<a href="#">Name</a>',
                        'attribute' => 'first_name',
                        'value' => 'first_name',
                    ],
                    [
                        'class' => LinkedColumn::class,
                        'header' => '<a href="#">Email</a>',
                        'attribute' => 'username',
                        'value' => 'email',
                    ],
                    'contact_no',
                    [
                        // the city is found from the address the customer gave (see Customer Map), so a franchise finds its customers by city
                        'attribute' => 'city_id',
                        'label' => 'City (from address)',
                        'format' => 'raw',
                        'filter' => \yii\helpers\ArrayHelper::map((new \yii\db\Query())->select(['id', 'name'])->from('city')->where(['status' => 1])->orderBy('name')->all(), 'id', 'name'),
                        'value' => function ($model) {
                            return \yii\helpers\Html::encode($model->city_name ?: '');
                        },
                    ],
                    [
                        'attribute' => 'status',
                        'filter' => \app\models\User::getStatusesList(),
                        'format' => 'raw',
                        'value' => function ($data) {
                            $html = '';

                            $html .= '<select id="status_list_' .
                                $data->id .
                                '" data-id="' .
                                $data->id .
                                '" class="form-control" >';
                            $lists = $data->getStatusesList();

                            foreach ($lists as $key => $list) {
                                if ($key == $data->status) {
                                    $html .=
                                        '<option value="' .
                                        $key .
                                        '" selected>' .
                                        $list .
                                        '</option>';
                                } else {
                                    $html .=
                                        '<option value="' .
                                        $key .
                                        '">' .
                                        $list .
                                        '</option>';
                                }
                            }
                            $html .= '</select>';

                            return $html;
                        },
                    ],
                    'created_at:date:Registered',
                    [
                        'class' => 'kartik\grid\ActionColumn',
                        'template' => '{view}  ',
                        'urlCreator' => function ($action, $model) {
                            return Url::toRoute(['users/user-profile', 'id' => $model->id]);
                        },
                    ],
                ],
            ]) ?>

        </div>
    </div>
</div>

<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<script>
$(document).on('change','select[id^=status_list_]',function(){
    var id=$(this).attr('data-id');
    var val=$(this).val();
    $.ajax({
        type: "POST",
        url: "<?= Url::toRoute(['users/status-change']) ?>",
        data: {id:id,val:val},
        success: function(data){
            swal("Good job!", "Status Successfully Changed!", "success");
        }
    });
});
</script>
