
<?php

/* @var $this \yii\web\View */

/* @var $content string */

use app\widgets\FlashAlert;
use yii\helpers\Html;
use yii\bootstrap4\Breadcrumbs;
//use app\assets\AssetBundle;
use app\modules\admin\models\WebSetting;

//AssetBundle::register($this);
//app\assets\FrontendAsset::register($this);
//Yii::$app->assetManager->bundles['yii\web\JqueryAsset'] = false;

$setting = new WebSetting();
$title = $setting->getSettingBykey('website_title');
$meta_des = $setting->getSettingBykey('home_page_meta_description');
$icon = $setting->getSettingBykey('website_favicon');
//var_dump($title); exit;

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
	<meta charset="<?= Yii::$app->charset ?>">
  <meta name="verify-admitad" content="a02dbb88a1"/>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description"  content="<?php echo $meta_des ; ?>" />
  <meta name="title"  content="<?php echo $title ; ?>" />
  <link rel="icon" href="<?= Yii::$app->getUrlManager()->getBaseUrl() ?>/uploads/<?php echo $icon;?>" type="image/x-icon">
	<?= Html::csrfMetaTags() ?>
	<title><?= isset($title)?$title:'' ?></title>
	<?php $this->head() ?>
<style>
    html, body {
        width: 100%;
        overflow-x: hidden;
    }
    .sb_mn_algnmnt{
        margin-bottom: 0%;
    }
    .bd_cnt_dglt_mrg {
        margin-top: 4%;
    }
    div#preloader {
    position: fixed;
    left: 0;
    top: 0;
    z-index: 99999;
    width: 100%;
    height: 100%;
    overflow: visible;
    background: #fff url('../images/app_logo.png') no-repeat center center;
    /* background-size: contain; */
    }


    /* Center the loader */
#loader {
  position: absolute;
  left: 50%;
  top: 50%;
  z-index: 1;
  width: 120px;
  height: 120px;
  margin: -76px 0 0 -76px;
  border: 16px solid #f3f3f3;
  border-radius: 50%;
  border-top: 16px solid #3498db;
  -webkit-animation: spin 2s linear infinite;
  animation: spin 2s linear infinite;
}

@-webkit-keyframes spin {
  0% { -webkit-transform: rotate(0deg); }
  100% { -webkit-transform: rotate(360deg); }
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Add animation to "page content" */
.animate-bottom {
  position: relative;
  -webkit-animation-name: animatebottom;
  -webkit-animation-duration: 1s;
  animation-name: animatebottom;
  animation-duration: 1s
}

@-webkit-keyframes animatebottom {
  from { bottom:-100px; opacity:0 } 
  to { bottom:0px; opacity:1 }
}

@keyframes animatebottom { 
  from{ bottom:-100px; opacity:0 } 
  to{ bottom:0; opacity:1 }
}

#myDiv {
  display: none;
  text-align: center;
}

</style>

<script type='text/javascript' src='https://platform-api.sharethis.com/js/sharethis.js#property=6030c73cb247c100112bd240&product=sop' async='async'></script>
<!--Start of Tawk.to Script-->
<script type="text/javascript">
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
s1.async=true;
s1.src='https://embed.tawk.to/6033a6519c4f165d47c5cf49/1ev4s3cas';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s0.parentNode.insertBefore(s1,s0);
})();
</script>
<!--End of Tawk.to Script-->
</head>


<body onload="myFunction()" style="margin:0;">
<?php $this->beginBody() ?>
<div id="loader"></div>
<div style="display:none;" id="myDiv" class="animate-bottom">
	<?= $this->render('//partials/header'); ?>

	<!-- <div class="container"> -->
		<?php // Breadcrumbs::widget([
			// 'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
		// ]) ?>
		<?php // FlashAlert::widget() ?>
		<?= $content ?>
	<!-- </div> -->


<?= $this->render('//partials/footer'); ?>
</div>
<?php $this->endBody() ?>

<!-- slick slider script -->
<script src="<?=Yii::$app->request->baseUrl?>/themes/frontend/assets/js/slick.js"></script>
<script src="<?=Yii::$app->request->baseUrl?>/themes/frontend/assets/js/slick.min.js"></script>
<script>
var myVar;

function myFunction() {
  myVar = setTimeout(showPage, 1500);
}

function showPage() {
  document.getElementById("loader").style.display = "none";
  document.getElementById("myDiv").style.display = "block";
}
</script>
<!-- main slider  -->
<script>
        $('.main_slider').slick({
            infinite: true,
            slidesToShow: 2,
            slidesToScroll: 1,
            autoplay: true,
            autoplaySpeed: 2000,
            arrows: true,
            // dots: true,
            responsive: [
    {
      breakpoint: 768,
      settings: {
        arrows: false,
        centerMode: true,
        centerPadding: '40px',
        slidesToShow: 1
      }
    },
    {
      breakpoint: 480,
      settings: {
        arrows: false,
        centerMode: true,
        centerPadding: '40px',
        slidesToShow: 1
      }
    }
  ]
        });
</script>

<script>
        $('.bst_slng_mbl_sldr').slick({
            infinite: true,
            slidesToShow: 6,
            slidesToScroll: 1,
            // autoplay: true,
            autoplaySpeed: 2500,
            arrows: true,
            // dots: true,
            responsive: [
    {
      breakpoint: 768,
      settings: {
        arrows: false,
        centerMode: true,
        centerPadding: '10px',
        slidesToShow: 2
      }
    },
    {
      breakpoint: 480,
      settings: {
        arrows: false,
        centerMode: true,
        centerPadding: '10px',
        slidesToShow: 2
      }
    }
  ]
        });
    </script>
    <script>
        $('.ppl_prd_sldr').slick({
            infinite: true,
            slidesToShow: 5,
            slidesToScroll: 1,
            // autoplay: true,
            autoplaySpeed: 2500,
            arrows: true,
            // dots: true,
            responsive: [
    {
      breakpoint: 768,
      settings: {
        arrows: false,
        centerMode: true,
        centerPadding: '10px',
        slidesToShow: 2
      }
    },
    {
      breakpoint: 480,
      settings: {
        arrows: false,
        centerMode: true,
        centerPadding: '0px',
        slidesToShow: 2
      }
    }
  ]
        });
    </script>

<!-- MEGA MENU SCRIPT -->
<script>
$(document).ready(function() {
  jQuery(document).ready(function(){
    $(".dropdown").hover(
      function() { $('.dropdown-menu', this).stop().fadeIn("fast");
        },
      function() { $('.dropdown-menu', this).stop().fadeOut("fast");
    });
  });
})
</script>

<!-- ACCORDION -->
<script>
        var acc = document.getElementsByClassName("accordion");
        var i;
        
        for (i = 0; i < acc.length; i++) {
          acc[i].addEventListener("click", function() {
            this.classList.toggle("active");
            var panel = this.nextElementSibling;
            if (panel.style.maxHeight){
              panel.style.maxHeight = null;
            } else {
              panel.style.maxHeight = panel.scrollHeight + "px";
            } 
          });
        }
        </script>
<!-- MOBLE VIEW SIDE BAR -->

<script>
    function storeNav() {
      document.getElementById("myStorenav").style.width = "250px";
    }
    function storecloseNav() {
      document.getElementById("myStorenav").style.width = "0";
    }
  </script>
<script>
    function openNav() {
      document.getElementById("mySidenav").style.width = "250px";
    }
    
    function closeNav() {
      document.getElementById("mySidenav").style.width = "0";
    }
  </script>

  <!-- SHOW COUPON CODE -->
  <!-- <script>
  var btn = document.getElementsByClassName("btn-coupon")[0],
    coupon = document.getElementsByClassName("coupon-code")[0];
btn.onclick = function(){
  this.innerHTML = coupon.innerHTML;
};
  </script> -->

  <!-- SIGNIN AND JOIN MODAL CONDITIONS -->
  <script type="text/javascript">
    $(document).ready(function(){
        $("#display_join_form").click(function(){
            // alert('hi');
            
            $("#join_form").addClass("display_block");
            $("#signin_form").addClass("display_none");
            $("#signin_form").removeClass("display_block");
            $("#join_form").removeClass("display_none");
            
        });
    });
    </script>
    <script type="text/javascript">
        $(document).ready(function(){
            $("#display_signin_form").click(function(){
                // alert('hi');
                $("#signin_form").removeClass("display_none");
                $("#join_form").removeClass("display_block");
                $("#signin_form").addClass("display_block");
                $("#join_form").addClass("display_none");
                
            });
        });
        </script>
        <script type="text/javascript">
            $(document).ready(function(){
                $("#display_join_form_direct").click(function(){
                    // alert('hi');
                    
                    $("#join_form").addClass("display_block");
                    $("#signin_form").addClass("display_none");
                    $("#signin_form").removeClass("display_block");
                    $("#join_form").removeClass("display_none");
                    
                });
            });
        </script>
   

        <!-- HOW IT WORKS SLIDER  -->
        <script>
                $('.how_it_works_slider').slick({
                    infinite: true,
                slidesToShow: 4,
                slidesToScroll: 1,
                autoplay: true,
                autoplaySpeed: 2000,
                arrows: true,
                dots: true,
                responsive: [
                {
                breakpoint: 768,
                settings: {
                    arrows: false,
                    centerMode: true,
                    centerPadding: '40px',
                    slidesToShow: 1
                }
                },
                {
                breakpoint: 480,
                settings: {
                    arrows: false,
                    centerMode: true,
                    centerPadding: '40px',
                    slidesToShow: 1
                }
                }
            ]
                        });
        </script>

        <!-- SPECIAL OFFER BLOCK SLIDER  -->
        <script>
            $('.special_offer_slider').slick({
                infinite: true,
                slidesToShow: 4,
                slidesToScroll: 1,
                autoplay: true,
                autoplaySpeed: 3000,
                arrows: true,
                // dots: true,
                responsive: [
        {
          breakpoint: 768,
          settings: {
            arrows: false,
            centerMode: true,
            centerPadding: '10px',
            slidesToShow: 2
          }
        },
        {
          breakpoint: 480,
          settings: {
            arrows: false,
            centerMode: true,
            centerPadding: '10px',
            slidesToShow: 2
          }
        }
      ]
            });
        </script>

<script>
if ( navigator.platform.indexOf('Win') != -1 ) {
  window.document.getElementById("wrapper").setAttribute("class", "windows");
} else if ( navigator.platform.indexOf('Mac') != -1 ) {
  window.document.getElementById("wrapper").setAttribute("class", "mac");
}
</script>
</body>
</html>
<?php $this->endPage() ?>
