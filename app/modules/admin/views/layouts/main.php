<?php
use yii\helpers\Html;
use app\widgets\FlashAlert;
use lo\modules\noty\Wrapper;
use app\modules\admin\assets\AssetBundle;
use kingston\mdbootstrap\MDBootstrapAsset;
use kingston\mdbootstrap\MDBootstrapPluginAsset;
use app\modules\admin\models\WebSetting;

/* @var $this \yii\web\View */
/* @var $content string */

AssetBundle::register($this);
// MDBootstrapAsset::register($this);
// MDBootstrapPluginAsset::register($this);

$adminlteAssets = Yii::$app->assetManager->getPublishedUrl(
    '@vendor/almasaeed2010/adminlte/dist'
);
$setting = new WebSetting();

$icon = $setting->getSettingBykey('website_favicon');

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<script>
	// light or dark, remembered in this browser (set before the page is drawn, so there is no flash)
	// the choice made with the moon in the top bar; until then the system setting (light or dark) decides
	try {
		var saved = localStorage.getItem('rb-theme');
		var dark = saved ? saved === 'dark' : (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
		document.documentElement.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
	} catch (e) {}
	</script>
	<meta charset="<?= Yii::$app->charset ?>"/>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?= Html::csrfMetaTags() ?>
	<title><?= Html::encode($this->title) ?></title>
	<link rel="icon" href="<?= Yii::$app
     ->getUrlManager()
     ->getBaseUrl() ?>/uploads/<?php echo $icon; ?>" type="image/x-icon">

	<?php $this->head(); ?>
	<!-- <link href="//fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet"> -->
	
	<!-- <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css"> -->
</head>
<!-- the look of the dashboard lives in modules/admin/assets/css/admin.css -->
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary theme-<?= Html::encode(Yii::$app->params['serviceEntry']['service'] ?? 'all') ?>">
<?php $this->beginBody(); ?>
<a class="rb-skip" href="#main-content">Skip to main content</a>

<div class="app-wrapper">

	<?= $this->render('../partials/header', [
     'adminlteAssets' => $adminlteAssets,
 ]) ?>

	<?= $this->render('../partials/nav', ['adminlteAssets' => $adminlteAssets]) ?>

	<main class="app-main">
		<?= $this->render('../partials/content-header') ?>

		<div class="app-content">
			<div class="container-fluid" id='flash'><span id="main-content" tabindex="-1"></span>
				<?= FlashAlert::widget() ?>
			
				<?= $content ?>
				<br>
			</div>
		</div>
	</main>
	<script>
	// cards side by side in a flex row (see admin.css, "cards in a flex layout"): every box of the page that holds two or more cards
	(function () {
		var root = document.getElementById('flash');
		if (!root) { return; }
		[root].concat([].slice.call(root.children)).forEach(function (box) {
			if (box.tagName !== 'DIV') { return; }
			var cards = [].filter.call(box.children, function (c) { return c.classList.contains('card'); });
			if (cards.length >= 2) { box.classList.add('rb-flex'); }
		});
	})();
	</script>
	<script>
$('button[type="reset"]').click(function(){
    window.location.href = window.location.href.split('?')[0];
});
</script>
	<?= $this->render('../partials/footer') ?>
</div>
<!-- ./wrapper -->

<?php $this->endBody(); ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
</body>
</html>
<?php $this->endPage(); ?>
