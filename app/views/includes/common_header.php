<?php 

use yii\helpers\Url;


?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <!-- google map api -->

    <!-- Compiled and minified JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

    <!--materialize element animation-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/animate.css@3.5.2/animate.min.css">
    <!-- or -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.5.2/animate.min.css">

<style>
#materialize-lean-overlay-1{
    display:none;
}
.user-location{
    white-space: nowrap;
    
    overflow: hidden;
}
#materialize-lean-overlay-2{
    z-index:1001 !important;
}
#materialize-lean-overlay-3{
    z-index:1000 !important;
}

</style>
  

<header id="header" class="page-topbar">
<!-- start header nav-->


<nav>
    <div class="nav-wrapper">
        <a href="#!" class="brand-logo">
            <a href="<?= Url::toRoute(['site/index'])?>" class="brand-logo rest_page-brand-logo darken-1"><img src="<?= \Yii::$app->view->theme->getUrl('/frontend') ?>/images/hungerfix_logo.png" alt="materialize logo"></a>
        </a>
        <div class="user-location">
         <?php  $location = !empty($_GET['address']) ? $_GET['address'] : (isset($_COOKIE['address']) ? $_COOKIE['address'] : 'Your Location'); ?>
            <a id="head-address" href="#location-modal" value="head-address" class="modal-trigger"><i class="material-icons location_edit_icon">edit_location</i><p class="hide-on-med-and-down"><?php echo  $location; ?></p>
            </a>

        </div>

        <a href="#" data-target="mobile-demo" class="sidenav-trigger"><i class="material-icons">menu</i></a>
        <ul class="right hide-on-med-and-down">


            <li>
                <a href="#" class="navbar-color">
                    <img src="<?= \Yii::$app->view->theme->getUrl('/frontend') ?>/images/mobile.png" class="mobile-image"> Get Mobile App
                </a>
            </li>
            <!-- <li>
                <a href="#" class="navbar-color dropdown-trigger" data-target="suggestion-dropdown">
                    <img src="images/mobile2.png" class="mobile-image"> Suggestions<i class="material-icons right">arrow_drop_down</i>
                </a>
            </li> -->
            <!--<li>
                <a href="/cart" class="navbar-color">
                    <img src="images/food1.png" class="table-image"> Order Food
                </a>
            </li>-->
            <li>
                <a onclick="tableBooking()" class="navbar-color">
                    <img src="<?= \Yii::$app->view->theme->getUrl('/frontend') ?>/images/table.png" class="table-image"> Book a Table
                </a>
            </li>
            <li>
                <a onclick="tableBooking()" class="navbar-color">
                    <img src="<?= \Yii::$app->view->theme->getUrl('/frontend') ?>/images/buffet1.png" class="catering-image"> Catering
                </a>
            </li>
         
            <?php if(empty(\Yii::$app->user->id)){?>

            <li>
                <a href="<?= \yii\helpers\Url::toRoute(['user/login'])?>" class="navbar-color">
                    <img src="<?= \Yii::$app->view->theme->getUrl('/frontend') ?>/images/lock.png" class="catering-image"> LogIn
                </a>
            </li>
            <li>
                <a href="<?= \yii\helpers\Url::toRoute(['user/login'])?>" class="navbar-color">
                    <img src="<?= \Yii::$app->view->theme->getUrl('/frontend') ?>/images/user-icon.png" class="catering-image"> SignUp
                </a>
            </li>
          
            <?php }else{ ?>
                <li>
            <!-- <a class="waves-effect waves-light btn modal-trigger" href="#modal5">Modal Bottom Sheet Style</a> -->
                <a href="<?= \yii\helpers\Url::toRoute(['cart/my-cart'])?>" id="cart" class="waves-effect waves-light modal-trigger navbar-color">
                    <img src="<?= \Yii::$app->view->theme->getUrl('/frontend') ?>/images/shop.png" class="cart-image"> Cart
                </a>
            </li>
                <li>
                <a href="<?= \yii\helpers\Url::toRoute(['user/user-dashboard'])?>" class="navbar-color">
                <img src="<?= \Yii::$app->view->theme->getUrl('/frontend') ?>/images/user-icon.png" class="catering-image"> Dashboard</a>
                </li>
                <li><a href="<?= \yii\helpers\Url::toRoute(['user/logout'])?>" data-method="post" class="navbar-color">
                <img src="<?= \Yii::$app->view->theme->getUrl('/frontend') ?>/images/logout.png" class="cart-image"> Logout</a>
					</li>
            <?php } ?>              
           
        </ul>
    </div>
</nav>

<ul class="sidenav" id="mobile-demo">
    <li><a href="mobile.html">Get Mobile App</a></li>
    <!--<li><a href="sass.html">Order Food</a></li>-->
    <li><a href="badges.html">Book a Table</a></li>
    <li><a href="collapsible.html">Catering</a></li>

    <li>
        <a href="#" class="navbar-color">
            <img src="images/bag.png" class="cart-image"> Cart
        </a>
    </li>
</ul>
<div id="myCart" class="modal bottom-sheet" style="z-index: 1003; display: block; opacity: 0; bottom: -100%;">
                  <div id="myCart-modal">
                    
                  </div>
                </div>
<!-- end header nav-->
</header>
<script>
function tableBooking(){
	swal('Coming Soon','We will update you .');
}

</script>