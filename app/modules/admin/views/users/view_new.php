<?php

use kartik\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;
use app\modules\admin\widgets\LinkedColumn;
use yii\widgets\Pjax;
use \app\modules\admin\models\CashbackTransaction;


/* @var $this yii\web\View */
/* @var $model app\models\Users */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-view">
<div class="card card-default">
    <div class="row card-header">
        <div class="col-sm-8">
            <h2><?=Yii::t('app', 'User: ') . ' ' . Html::encode($this->title)?></h2>
        </div>
        <div class="col-sm-4" style="margin-top: 15px">

            <?=Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-sm btn-primary'])?>

        </div>
    </div>

<!-- profile page design start-->
<div class="row">
          <div class="col-md-3">

            <!-- Profile Image -->
            <div class="card card-primary card-outline">
              <div class="card-body box-profile">
             
                <div class="text-center">
                  <img class="profile-user-img img-fluid img-circle" src="" alt="User profile picture">
                </div>

                <h3 class="profile-username text-center"><?php echo  $model->username;?></h3>
                <p class="text-muted text-center"><?php echo  $model->email;?></p>
              
                <p class="text-muted"></p>
                <hr>
                <ul class="list-group list-group-unbordered mb-3">
                  <li class="list-group-item">
                    <b>Total Balence</b> <a class="float-end badge text-bg-info"><?= $available_cash ;?></a>
                  </li>
                  <li class="list-group-item">
                    <b>Approved Cashback</b> <a class="float-end badge text-bg-success"><?=$approved_cash;?></a>
                  </li>
                  <li class="list-group-item">
                    <b>Pending Cashbak</b> <a class="float-end badge text-bg-warning"><?=$pending_cash;?></a>
                  </li>
                  <li class="list-group-item">
                    <b>Requested Cashbak</b> <a class="float-end badge text-bg-danger"><?=$requested_cash;?></a>
                  </li>
                  <li class="list-group-item">
                    <b>Withdraw Cashbak</b> <a class="float-end badge text-bg-success"><?=$withdraw_cash;?></a>
                  </li>
                  <li class="list-group-item">
                    <b>Paid Cashbak</b> <a class="float-end badge text-bg-info"><?=$paid_cash;?></a>
                  </li>
                  <li class="list-group-item">
                    <b>Referral Code</b> <a class="float-end"><?= $model->referal_code;?></a>
                  </li>
                </ul>
               
                <?php
$gridColumn = [
    ['attribute' => 'id', 'visible' => true],
    'email',

];
echo DetailView::widget([
    'model' => $model,
    'attributes' => $gridColumn,
]);
?>


                </div>
                <?=Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary'])?>

              <!-- /.card-body -->
            </div>
            </div>
            <!-- /.card -->

            <!-- About Me Box -->

          <!-- /.col -->
          <div class="col-md-9">
            <div class="card">
              <div class="card-header p-2">
                <ul class="nav nav-pills">
                 
                  <li class="nav-item"><a class="nav-link active" href="#activity2" data-bs-toggle="tab">Cashback Activity </a></li>
                  <li class="nav-item"><a class="nav-link" href="#settings" data-bs-toggle="tab">Settings </a></li>
                  <li class="nav-item"><a class="nav-link" href="#referral_network" data-bs-toggle="tab">Referral Network </a></li>
                  <li class="nav-item"><a class="nav-link " href="#click-activity" data-bs-toggle="tab">Click Activity </a></li>
                 </ul>
              </div><!-- /.card-header -->
              <div class="card-body">
                <div class="tab-content">
                  <div class="active tab-pane" id="activity2">
                    <!-- Post -->
                    
                    <?php Pjax::begin(); ?>  
  <?php

echo GridView::widget ( [ 
		'dataProvider' => $cashback_activity,
		'layout' => "{items}\n{pager}\n{summary}",
		'summaryOptions' => [ 
				'class' => 'summary float-end' 
		],
		'tableOptions' => [ 
				'class' => 'table table-bordered' 
		],
		'filterModel' => true,
		'columns' => [ 
				
				//'reference_id',
				[ 
					//'attribute' => 'parent_trans_id',
					'label' => 'Reference Id',
					'value' => function ($data) {
						// if($data->payment_type == 'Refferal Commision'){
						// 	$trsn = CashbackTransaction::find()->where(['transaction_id' => $data->parent_trans_id])->all();
						//     //var_dump($data->transactionid->parentuser["username"]); exit;
						// 	return $data->transaction->parentuser["username"]; //isset($data->transactionid->parentuser["username"]))?$data->transactionid->parentuser["username"]):$data->transactionid->parentuser["id"]) ; 
							
						// }else{
							return  $data->reference_id;
						//}
					} 
				
				],
				
				// 'retailer_id',
				[ 
						'attribute' => 'store_id',
						'value' => function ($data) {
							return $data->store_id;
						} 
					
					// 'value' => isset($dataProvider->categorys)?$dataProvider->categorys->category_name:'',
				],
				// 'user_id',
				// 'parent_trans_id',
				[
					'attribute' => 'user_id',
					'label' => 'User ID',
				],
				'payment_type',
				// 'payment_method',
				// 'payment_details:ntext',
				//'sale_amount',
				//'earned_amount',
				'amount',
				[ 
						'attribute' => 'payment_status',
						"format" => 'raw',
						'value' => function ($data) {
							$html = '';
							$html .= '<select id="payment_status_list_'. $data->transaction_id. '" data-id="'.$data->transaction_id.'" >';
							$lists = $data->getStatus ();
						
							foreach ( $lists as $key => $list ) {
								
								if($key==$data->payment_status){
									$html .= '<option value="' . $key . '" selected>' . $list . '</option>';
									
								}else{
									$html .= '<option value="' . $key . '">' . $list . '</option>';
									
								}
							}
							
							$html .= '</select>';
							
							return $html;
						} 
				] 
			// 'reason:ntext',
			// 'created_date',
			// 'exp_confirm_date',
			// 'updated_date',
			// 'request_date',
			// 'process_date',
		
		] 
] );
?>
<?php Pjax::end(); ?>
                    <!-- /.post -->
                  </div>
                  <!-- /.tab-pane -->
                  <div class="tab-pane" id="click-activity">
                    <!-- The timeline -->
                    <?php Pjax::begin(); ?>  
				    <?php echo GridView::widget ( [ 
																				'dataProvider' => $clicks,
																				'layout' => "{items}\n{pager}\n{summary}",
																				'summaryOptions' => [ 
																						'class' => 'summary float-end' 
																				],
																				'tableOptions' => [ 
																						'class' => 'table table-bordered' 
																				],
																				'filterModel' => true,
																				'columns' => [ 
																						
																						'click_id',
																						'user_id',
																						[ 
																								'attribute' => 'retailer_id',
																								'value' => function ($data) {
																									return isset ( $data->retailer ) ? $data->retailer->name : "";
																								} 
																						
																						],
																						
																						'created_date',
																						//'status' 
																				
																				] 
																		] );
																		?>
<?php Pjax::end(); ?>
                   
                  </div>
                  <!-- /.tab-pane -->

                  <div class="tab-pane" id="settings">
                  <?php Pjax::begin(); ?>
				  <?= GridView::widget([
					'dataProvider' => $dataProvider,
					///'filterModel'  => $searchModel,
					'columns'      => [
						['class' => 'yii\grid\SerialColumn'],
						
						'username',
						'first_name',
						'email',
						'contact_no',
						[ 
							'label' => 'Referer List',
							'attribute' => 'referal_id',
							'format' => 'html',
							'value' => function ($data) {
								
								return "<a href=" . Url::toRoute ( [ 
										'users/referer-list',
										'id' => $data->id 
								] ) . "><i class='fa fa-gift'></i></a>";
							} 
					
					],
						'user_role',
						[
							'label' => 'status',
							'attribute' => 'status',
							'format' => 'html',
							'value' => function ($data) {
								return $data->stateBadges();
							}
						],
						
						'created_at:date:Registered',
						// 'updated_at',
						
					],
				]); ?>
    <?php Pjax::end(); ?>
               
                  </div>

                  <div class="tab-pane" id="referral_network">
                  <?php Pjax::begin(); ?>
					<?php echo GridView::widget([
        'dataProvider' => $referral_list,
        'layout' => "{items}\n{pager}\n{summary}",
        'summaryOptions' => ['class' => 'summary float-end'],
		    'tableOptions' => ['class' => 'table table-bordered'],
		
        'filterModel' => $searchModel,
        'columns' => [
          
			//'first_name',
			[
				 	//'class' => LinkedColumn::class,
					'header' => '<a href="#">Name</a>',
				 	'attribute' => 'full_name',
				 	'value' => 'fullName',
				],
            'email:email',
            [

                'label' => 'status',
                'attribute' => 'status',
                'filter' => $searchModel->getStatusesList(),
                'format' => 'html',
                'value' => function ($data) {
                    return $data->stateBadges();
                }
            ],


           
        ],
    ]); ?>
	 <?php Pjax::end(); ?>
                
                  </div>
                  <div class="tab-pane" id="timeslots">
                 
                  </div>
                  <!-- /.tab-pane -->
                </div>
                <!-- /.tab-content -->
              </div><!-- /.card-body -->
            </div>
            <!-- /.nav-tabs-custom -->
          </div>
          <!-- /.col -->
        </div>


<!-- profile page end -->




    </div>



    </div>

</div>
</div>
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>	
<script>
$(document).on('change','select[id^=payment_status_list_]',function(){
var id=$(this).attr('data-id');
var val=$(this).val();

$.ajax({
	  type: "POST",
	  url: "<?= Url::toRoute(['users/change-transaction-status'])?>",
	  data: {id:id,val:val},
	  success: function(response){
		  if(response == 1){
			swal("Good job!", "Status Successfully Changed!", "success");
		  }else{
			swal("Ohh", "Somthing Went Wrong", "error");
		  }

		
	  }
	});	
});
</script>	