<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\Breadcrumbs;
use app\assets\AppAsset;
use app\components\widgets\Flash;
use app\components\widgets\Title;
use kartik\widgets\Growl;
use kartik\growl\GrowlAsset;
use kartik\base\AnimateAsset;
use lo\modules\noty\Wrapper;
GrowlAsset::register($this);
AnimateAsset::register($this);

/* @var $this \yii\web\View */
/* @var $content string */

?>
<style>
div#w1-success {
    display: none;
}
</style>
<header class="main-header">
<?php /*if (Yii::$app->session->hasFlash('success')): ?>
    <div class="alert alert-success alert-dismissable">
         <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
         <h4><i class="icon fa fa-check"></i>Saved!</h4>
         <?= Yii::$app->session->getFlash('success',['message']) ?>
    </div>
<?php endif;*/ ?>
<div id="user-new-order-success">
<?php foreach (Yii::$app->session->getAllFlashes() as $message):; ?>
            <?php
            echo \kartik\widgets\Growl::widget([
                'type' => (!empty($message['type'])) ? $message['type'] : 'danger',
                'title' => (!empty($message['title'])) ? Html::encode($message['title']) : 'Title Not Set!',
                'icon' => (!empty($message['icon'])) ? $message['icon'] : 'fa fa-info',
                'body' => (!empty($message['message'])) ? Html::encode($message['message']) : 'Message Not Set!',
                'showSeparator' => true,
                'delay' => 1, //This delay is how long before the message shows
                'pluginOptions' => [
                    'delay' => (!empty($message['duration'])) ? $message['duration'] : 10000, //This delay is how long the message shows for
                    'placement' => [
                        'from' => (!empty($message['positonY'])) ? $message['positonY'] : 'top',
                        'align' => (!empty($message['positonX'])) ? $message['positonX'] : 'left',
                    ]
                ]
            ]);
            ?>
        <?php endforeach; ?>
<?php /*echo Wrapper::widget([
    'layerClass' => 'lo\modules\noty\layers\Growl',
]);*/ ?>
</div>
<button>Press to play sound</button>
    <?php

    $name=Yii::$app->name;

    if(\Yii::$app->user->identity->role_id==\app\models\User::ROLE_MERCHANT){

        $name=Yii::$app->user->identity->full_name;

    }


    ?>

    <?= Html::a('<span class="logo-mini">APP</span><span class="logo-lg">' .$name . '</span>', Yii::$app->homeUrl, ['class' => 'logo']) ?>

    <nav class="navbar navbar-static-top" role="navigation">
		
		<a href="#" class="sidebar-toggle" data-toggle="push-menu"
			role="button"> <span class="sr-only">Toggle navigation</span>
			<span class="icon-bar"></span> <span class="icon-bar"></span> 
			<span class="icon-bar"></span>
		</a>

		<div class="navbar-custom-menu">

			<ul class="nav navbar-nav">

		<?php 	if(\Yii::$app->user->identity->role_id==\app\models\User::ROLE_ADMIN){ ?>
				<li class="dropdown notifications-menu"><a href="#"
					class="dropdown-toggle" data-toggle="dropdown"> <i
						class="fa fa-bell-o"></i> <span class="label label-warning noty-count"><?php $noticount=\app\models\Notification::find()->where(['mark_read'=>0])->orderBy(['id'=>SORT_DESC])->count(); echo $noticount?></span>
				</a>
					<ul class="dropdown-menu">
					<li>
                                   <!-- inner menu: contains the actual data -->
									<ul class="menu" id="notification-data">
                                        <?php if($noticount > 0){
                                            $notification=(new \app\models\Notification())->getLatestUnreadNotification();

                                            foreach ($notification as $noty){
                                            ?>
<li><a href="<?=Url::toRoute(['/notification/view','id'=>$noty->id])?>"> <i class="<?= $noty->icon ?> text-aqua"></i> <?= $noty->title ?>
										</a></li>

										<!--<li><a href="<?php //Url::toRoute(['/order/view','id'=>$noty->order_id])?>"> <i class="<?= $noty->icon ?> text-aqua"></i><?= $noty->title ?>	   
										</a></li>-->

                                        <?php } }?>
					</ul></li>
											</ul>
					</li>
											<?php } ?>
				<!-- User Account: style can be found in dropdown.less -->

				<li class="dropdown user user-menu"><a href="#"
					class="dropdown-toggle" data-toggle="dropdown"> <?=\Yii::$app->user->identity->profileImage ( [ "class" => "user-image","alt" => "User Image" ] )?> <span
						class="hidden-xs"><?=\yii::$app->user->identity->full_name?></span>
				</a>
					<ul class="dropdown-menu">
						<!-- User image -->
						<li class="user-header">
                            <?=\Yii::$app->user->identity->profileImage ( [ "class" => "img-circle","alt" => "User Image" ] )?>

                            <p>
								<?=\yii::$app->user->identity->full_name?> <small>Member since
									<?= \yii::$app->formatter->asRelativeTime(\yii::$app->user->identity->created_on) ?></small>
							</p>
						</li>
						<!-- Menu Footer-->
						<li class="user-footer">
							<div class="pull-left">
								<a href="<?= Url::toRoute(['/user/view', 'id' => \Yii::$app->user->id]) ?>" class="btn btn-sm btn-default btn-flat">Profile</a>
							</div>
							<div class="pull-right">
                                <?=Html::a ( 'Sign out', [ '/user/logout' ], [ 'data-method' => 'post','class' => 'btn btn-default btn-flat' ] )?>
                            </div>
						</li>
					</ul></li>




				<!-- User Account: style can be found in dropdown.less -->
			
				</li>
			</ul>
		</div>
	</nav>
	<div id="growl"></div>
	<?php 

	?>
</header>

<script src='https://cdn.rawgit.com/admsev/jquery-play-sound/master/jquery.playSound.js'></script>
<script type="text/javascript">
$('button').on('click', function () { 
	
    	var obj = document.createElement("audio");
	//	alert(obj);
            obj.src = "http://www.soundjay.com/misc/sounds/bell-ringing-01.mp3"; 
            obj.play();	
    });
   setInterval("my_function();",10000); 
    function my_function(){
      $('#user-new-order-success').load(location.href +'#user-new-order-success');
	
    }
  </script>
<script>

window.setInterval(getNotification, 5000);

//window.setInterval(getgrowl, 5000);


function getNotification(){

	$.ajax({
		type: "GET",
		url: "<?= Url::toRoute(['notification/get-notification'])?>",
		cache: false,
		success: function(data){
		console.log(data);
			if(data.count==0||$.isEmptyObject(data.detail)){
				}
				else{
				
			$('.noty-count').html(data.count);
			$.each(data.detail, function (key,val) {
			
				var html=getNotyHtml(val);
				 $('.notification-data').append(html);
				// $.playSound("uploads/Tornado_Siren_II-Delilah-747233690.mp3");
				//$.playSound("ia601303.us.archive.org/12/items/TornadoSirenIIDelilah747233690_201512/Tornado_Siren_II-Delilah-747233690.mp3");
				 getgrowl(val);

			});

		}
		}
	});

}

function getgrowl(msg){
	//alert('dddddfdsfsdfsdfsd');
	$.ajax({
		type: "POST",
		url: "<?= Url::toRoute(['notification/growl'])?>",
		cache: false,
	//	dataType : "json",
		data : {
			msg: msg
		}, 
		success: function(data){
		console.log(data);
			//$("#growl").html(data.growl);
			$.notify(data.growl);
			$.playSound("http://www.soundjay.com/misc/sounds/bell-ringing-01.mp3");
			//$.playSound("https://fs.flockusercontent.com/8da26de15719173486b3cc27?Expires=1571920184&Signature=OOeSVVGRvc0RGE3onSoFLvP0Kb9stdnZhxEgGtdoz6jYSihrjuQbLbJKvmv8yYTbhonA-k8BsZ~vojMJd7R9Ah6wuGauvTh8AkUvgy6i3jGF-xSh6CUApBE0MDJ7~RwQpTYL3EFlNGaxZsjm33bP1zuy9dSMhsZgZRVdz5UWo6tgzRje3j6qLm0zMv-0niBnYgZknf4HAAqFLCxqeItHs5M4XXNbbCxLHo2KrStL58EMLWcOlfMwtlQJIP3NASP8g1l6-UM9nvGtK2WBx8ubdQTazymsHBLwPeQFWAcLQbZw5m0QoZBaG9gxUe79JKMoIlemX9aDcZzX3E6qXfsPMQ__&Key-Pair-Id=APKAJMN6OEFOLBEBMIJA");
				
		} ,
		error: function(xhr, status, error) {
  alert(error);
}
	});
}

function getNotyHtml(data){
	
	var html='';
	html+='<li><a href="'+data.url+'"> <i class="'+data.icon+'"></i>'+data.title+'';
	html+= "</a></li>";
	return html;

}
</script>
