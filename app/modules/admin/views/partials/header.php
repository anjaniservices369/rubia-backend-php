<?php

use yii\helpers\Url;
use yii\helpers\Html;
use app\modules\admin\models\Notification;
use app\modules\admin\models\WebSetting;

/* @var $this \yii\web\View */
/* @var $content string */
?>

<nav class="app-header navbar navbar-expand bg-body border-bottom"><div class="container-fluid">
		<!-- Left navbar links -->
		<ul class="navbar-nav">
			<li class="nav-item">
				<a class="nav-link" data-lte-toggle="sidebar" href="#"><i class="fas fa-bars"></i></a>
			</li>
		</ul>

		<!-- Right navbar links -->
		<ul class="navbar-nav ms-auto">
			<!-- Messages Dropdown Menu -->

			<!-- light / dark -->
			<li class="nav-item">
				<a class="nav-link" href="#" id="rb-theme-toggle" role="button" title="Light or dark" aria-label="Switch between light and dark"><i class="fas fa-moon"></i></a>
			</li>
			<script>
			(function () {
				var link = document.getElementById('rb-theme-toggle');
				function paint() { link.firstElementChild.className = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'fas fa-sun' : 'fas fa-moon'; }
				link.addEventListener('click', function (e) {
					e.preventDefault();
					var dark = document.documentElement.getAttribute('data-bs-theme') !== 'dark';
					document.documentElement.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
					try { localStorage.setItem('rb-theme', dark ? 'dark' : 'light'); } catch (x) {}
					paint();
				});
				paint();
			})();
			</script>

			<!-- Notifications Dropdown Menu -->
			<li class="nav-item dropdown">
				<a class="nav-link" data-bs-toggle="dropdown" href="#">
					<i class="far fa-bell"></i>
					<span class="badge text-bg-warning navbar-badge">
						<?php $noticount = Notification::find()->where(['mark_read' => 0])->count();
						echo $noticount ?>

					</span>
				</a>
				<div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
					<span class="dropdown-item dropdown-header"><?php echo $noticount; ?> Notifications</span>
					<?php if ($noticount > 0) {
						$notification = (new Notification())->getLatestUnreadNotification();

						foreach ($notification as $noty) {
					?>

							<div class="dropdown-divider"></div>
							<a href="<?= Url::toRoute(['notification/view', 'id' => $noty->id]) ?>" class="dropdown-item">
								<i class="<?= $noty->icon ?> me-2"></i> <?= $noty->title ?>
								<span class="float-end text-muted text-sm"><?= $noty->created_on ?> </span>
							</a>


					<?php }
					} ?>


					<div class="dropdown-divider"></div>
					<a href="<?= Url::toRoute(['notification/index']) ?>" class="dropdown-item dropdown-footer">See All Notifications</a>
				</div>
			</li>
		</ul>
	</div></nav>
<audio id="myAudio" muted autoplay>
	<source src="https://www.soundjay.com/buttons/button-6.mp3" type="audio/ogg">
</audio>

<?php
$setting = new WebSetting();
$dashboard_noti = $setting->getSettingBykey('dashboard_notification');



?>
	<script>
		// window.setInterval(getAlert, 5000);

		function getAlert() {

			$.ajax({
				type: "GET",
				url: "<?= Url::toRoute(['notification/get-notification']) ?>",
				cache: false,
				success: function(data) {
					console.log(data);

					if (data.count == 0) {
						// $.playSound("https://www.soundjay.com/buttons/button-6.mp3");
					} else {


						$.each(data.detail, function(i, item) {

							
							$(document).Toasts('create', {
									class: 'bg-success',
									title: 'New Order #' + data.detail[i].order_id,
									subtitle: '<a href="notification/update-read?id=' + data.detail[i].order_id + '" class="btn btn-warning btn-sm ms-4">Open</a>',
									body: 'You recieve a new order',
									fade: true,
									autohide: true,
									delay: 3000

								},

							)


						});

						var x = document.getElementById("myAudio");
						x.muted = false;
						x.play();

						// $('.noty-count').html(data.count);
						// $.each(data.detail, function(key, val) {
						// 	// console.log(val);
						// 	var html = getNotyHtml(val);
						// 	$('.notification-data').append(html);

						// 	var x = document.getElementById("myAudio");
						// 	x.muted = false;
						// 	x.play();



						// 	// getgrowl(val);

						// });

					}
				}
			});

		}
	</script>
