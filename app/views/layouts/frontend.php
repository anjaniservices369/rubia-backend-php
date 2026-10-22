<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\User;

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
    <meta charset="<?= Yii::$app->charset ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title>Pinky food delivery</title>
    <?php $this->head() ?>

    <link rel="icon" href="<?= \Yii::$app->view->theme->getUrl('/frontend') ?>/assets/img/logo/logo.jpeg" sizes="32x32">

</head>
<body class="home page-template page-template-template-frontpage page-template-template-frontpage-php page page-id-4 cookies-not-set startpage-english">
<?php $this->beginBody() ?>

<?php
echo $this->render('main-header', [
    'categories' => $categories
]);
?>

<div id="preloader">
         <div class="loader" id="loader-1"></div>
      </div>

        <?= $content; ?>

<?php
echo $this->render('main-footer');
?>

<!-- Javascript -->




<!-- Toast Notification -->
<!-- <script>
    AOS.init();
</script> -->
<!-- Toast Notification -->
<script type="text/javascript">
    // Toast Notification
    // $(window).load(function() {
    //     setTimeout(function() {
    //         Materialize.toast('<span>Hiya! I am a toast.</span>', 1500);
    //     }, 1500);
    //     setTimeout(function() {
    //         Materialize.toast('<span>You can swipe me too!</span>', 3000);
    //     }, 5000);
    //     setTimeout(function() {
    //         Materialize.toast('<span>You have new order.</span><a class="btn btn-sm-flat yellow-text" href="#">Read<a>', 3000);
    //     }, 15000);
    // });
</script>
<!-- <script type="text/javascript">
$ ( document ). ready ( function (){
    $('.today_offer').bxSlider({
        auto: true,
        autoControls: true,
        stopAutoOnClick: true,
        pager: false,
        speed: 600,
        infiniteLoop: true,
        responsive: true,
        maxSlides: 8,
        minSlides: 1,
        slideWidth: 150,
        slide: 460,
        slideMargin: 5,
        moveSlides: 1

    });
});
</script> -->
<!-- <script type="text/javascript">
$ ( document ). ready ( function (){
    $('.cuisines-slider').bxSlider({
        auto: true,
        autoControls: true,
        stopAutoOnClick: true,
        pager: false,
        speed: 600,
        infiniteLoop: true,
        responsive: true,
        maxSlides: 6,
        minSlides: 1,
        slideWidth: 225,
        slide: 800,
        slideMargin: 0,
        moveSlides: 1

    });
});
</script> -->

<!--<script type="text/javascript">-->
<!--    window.onload = function() {-->
<!--        var startPos;-->
<!--        var geoSuccess = function(position) {-->
<!--            startPos = position;-->
<!--            document.getElementById('startLat').innerHTML = startPos.coords.latitude;-->
<!--            document.getElementById('startLon').innerHTML = startPos.coords.longitude;-->
<!--        };-->
<!--        navigator.geolocation.getCurrentPosition(geoSuccess);-->
<!--    };-->
<!--</script>-->
<!--<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_KEY&libraries=places&callback=initAutocomplete" async defer></script>-->
<!--<script type="text/javascript">-->
<!--    document.addEventListener('DOMContentLoaded', function() {-->
<!--        var elems = document.querySelectorAll('.parallax-img1');-->
<!--        var instances = M.Parallax.init(elems);-->
<!--    });-->
<!---->
<!--    // Or with jQuery-->
<!---->
<!--    $(document).ready(function(){-->
<!--        $('.parallax-img1').parallax();-->
<!--    });-->
<!---->
<!--</script>-->



<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
