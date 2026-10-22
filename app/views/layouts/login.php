<?php
use yii\helpers\Html;

/* @var $this \yii\web\View */
/* @var $content string */

app\assets\FrontendAsset::register($this);
Yii::$app->assetManager->bundles['yii\web\JqueryAsset'] = false;

$model = new \app\models\Category();

$categories = $model->getCategoryLIst();

?>
    <?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
<meta charset="<?= Yii::$app->charset ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1">
        <?= Html::csrfMetaTags() ?>
        <title><?= Html::encode($this->title) ?></title>
        <?php $this->head() ?>
        
	<!-- Google font -->
<link href="https://fonts.googleapis.com/css?family=Hind:400,700"
	rel="stylesheet">
<link rel="stylesheet" type="text/css"
	href="<?= \Yii::$app->view->theme->getUrl('/frontend/css/frontend.css') ?>">

<!-- Bootstrap -->
<link type="text/css" rel="stylesheet"
	href="<?= \Yii::$app->view->theme->getUrl('/frontend/css') ?>/slick.css" />
<link type="text/css" rel="stylesheet"
	href="<?= \Yii::$app->view->theme->getUrl('/frontend/css') ?>/slick-theme.css" />

<!-- nouislider -->
<link type="text/css" rel="stylesheet"
	href="<?= \Yii::$app->view->theme->getUrl('/frontend/css') ?>/nouislider.min.css" />

<!-- Font Awesome Icon -->
<link rel="stylesheet"
	href="<?= \Yii::$app->view->theme->getUrl('/frontend/css') ?>/font-awesome.min.css">

<link rel="stylesheet" type="text/css"
	href="<?= \Yii::$app->view->theme->getUrl('/frontend/css/frontend.css') ?>">

<!-- Custom stlylesheet -->
<link type="text/css" rel="stylesheet"
	href="<?= \Yii::$app->view->theme->getUrl('/frontend/css') ?>/style.css" />






<link rel="shortcut icon" href="">

</head>
<body ng-app="ShopingCart">
    <?php $this->beginBody() ?>
	<!-- /NAVIGATION -->
	
	<?php
echo $this->render('main-header', [
    'categories' => $categories
]);
?>
	
	<div id="home" class="main-container">
    	<?= $content;?>
	</div>
	<?php
echo $this->render('main-footer');
?>

	<!-- Javascript -->
	<script
		src="<?= \Yii::$app->view->theme->getUrl('/frontend/js') ?>/slick.min.js"></script>
	<script
		src="<?= \Yii::$app->view->theme->getUrl('/frontend/js') ?>/nouislider.min.js"></script>
	<script
		src="<?= \Yii::$app->view->theme->getUrl('/frontend/js') ?>/jquery.zoom.min.js"></script>
	<script
		src="<?= \Yii::$app->view->theme->getUrl('/frontend/js') ?>/main.js"></script>

	<script
		src="<?= \Yii::$app->view->theme->getUrl('/frontend/js') ?>/style.js"></script>

	<script
		src="<?= \Yii::$app->view->theme->getUrl('/frontend/js') ?>/custom.js"></script>

    <?php $this->endBody() ?>
  </body>
</html>
<?php $this->endPage() ?>
