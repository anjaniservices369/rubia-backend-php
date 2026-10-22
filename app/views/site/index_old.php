<?php 
use yii\helpers\Url;
use yii\helpers\Html;
use app\modules\admin\models\Retailer;
?>

<style>
 /*img.banner_image {
    height: 339px;
 }*/
    .site_banner_image.slick-slide {
    height: 285px !important;
    }
    .top_bnr_stl img {
        max-height: none !important;
        width: 100%;
        /* border-radius: 4px; */
        object-fit: scale-down;
    }
    .str_crd.splofr_crd_stls {
        height: 230px;
    }
    .image.all-stores {
        width: 80%;
        align-items: center;
        margin-left: 9%;
    }  
</style>

<!-- MAIN CONTENT START -->
 <!-- Start Slider Area -->
 <div class="bd_cnt_dglt_mrg">
        <section class="sec_stl top_bnr_sec_pd">
                <div class="row">
                        <?php if(!empty($banners)){?>
                        <div class="col-lg-12 col-md-12 col-xs-12 col-sm-12 mb_rm_pdng">
                            <div class="main_slider top_bnr_stl">
                                <!-- <div class="col-lg-12 col-md-12 col-xs-12 col-md-12"> -->
								<?php foreach($banners as $banner){?>
                                        <div class="site_banner_image">
                                            <img src="<?=Yii::$app->request->baseUrl?>/uploads/banners/<?= $banner['banner_image']?>" alt="" class="banner_image">
                                        </div>
								<?php } ?>
                                
                            </div>
                        </div>
					<?php } ?>
                  
                    </div>
            </section>
    

  <!-- End Slider Area -->

   <!-- Start Slider Area -->
 <div class="bd_cnt_dglt_mrg">
    <section class="sec_cstm_stls sec_bg_attach">
        <div class="row">
            <div class="col-lg-8 col-md-8">
                <h1>We also pay you in bitcoin whenever you shop!</h4>
            </div>
            <div class="col-lg-5 col-md-5"></div>
        </div>
    </section>
 </div>

 <!-- HOW IT WORKS SECTION  -->

 <div class="bd_cnt_dglt_mrg">
    <section class="">
        <div class="row text-center">
            <div class="col-md-12">
                <div class="sec_heading">
                    <h2><b>How it works</b></h4>
                </div>
            </div>
        </div>
        <div class="row text-center how_it_works_slider">
            
            <div class="col-lg-4 col-md-4">
                <div class="hwutwrks_mn_blk">
                    <img src="<?=Yii::$app->request->baseUrl?>/themes/frontend/assets/images/cashbaka/download_app.png" alt="" class="img-responsive">
                    <h4>Download the app</h4>
                    <p>Lorem Ipsum is simply dummy
                        text of the printing and
                        typesetting industry.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-4">
                <div class="hwutwrks_mn_blk">
                    <img src="<?=Yii::$app->request->baseUrl?>/themes/frontend/assets/images/cashbaka/complete_purchase.png" alt="" class="img-responsive">
                    <h4>Complete Purchase</h4>
                    <p>Lorem Ipsum is simply dummy
                        text of the printing and
                        typesetting industry.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-4">
                <div class="hwutwrks_mn_blk">
                    <img src="<?=Yii::$app->request->baseUrl?>/themes/frontend/assets/images/cashbaka/get_cashback.png" alt="" class="img-responsive">
                    <h4>Get Cashback</h4>
                    <p>Lorem Ipsum is simply dummy
                        text of the printing and
                        typesetting industry.</p>
                </div>
            </div>
            <!-- <div class="col-lg-4 col-md-4">
                <div class="hwutwrks_mn_blk">
                    <img src="assets/images/cashbaka/get_cashback.png" alt="" class="img-responsive">
                    <h4>Get Cashback</h4>
                    <p>Lorem Ipsum is simply dummy
                        text of the printing and
                        typesetting industry.</p>
                </div>
            </div> -->
            
        </div>
    </section>
 </div>

<!-- SPECIAL OFFERS  -->
<?php if(!empty($featured_stores)){?>
 <div class="bd_cnt_dglt_mrg spcl_ofr_sec">
    <section class="sec_stl">
        <div class="row text-center">
            <div class="col-md-12">
                <div class="sec_heading">
                    <h1  class="text-white"><b>Today Special Offers</b></h1>
                </div>
            </div>
        </div>
        <div class="row special_offer_slider text-center">
		<?php foreach($featured_stores as $store){?>
            <div class="col-lg-3 col-md-3 col-xs-6 feature-stores">
                <div class="str_crd splofr_crd_stls">
                    <a href="#">
                        <img src="<?= $store['store_logo_url']?>" alt="<?= $store['name']?>" class="image all-stores">
                        <hr>
						<?php if($store->cashback_disable  == 0){ ?>
                        <p class="cat_ttl"><?= $store['cashback']?></p>
						<?php } else{?>
							<p class="cat_ttl"></p>
						<?php } ?>
                        <div class="splofr_crd_btm_blk">
                            <h5><?= substr($store->name,0,10)?></h5>
                            <!-- <p>Books Online Studies</p> -->
							<?php $store->description?>
                        </div>
                    </a>
                </div>
            </div>
<?php } ?>
        </div>
    </section>
 </div>
<?php } ?>

<!-- STORES SECTION  -->
<?php if($all_stores){?>
 <div class="bd_cnt_dglt_mrg strs_sec_pdng">
    <section class="sec_stl">
        <div class="row text-center">
            <div class="col-md-12">
                <div class="sec_heading">
                    <h1 class="text-uppercase"><b>Stores</b></h1>
                </div>
            </div>
        </div>
        <div class="row text-center">
		<?php foreach($all_stores as $store){?>
            <div class="col-lg-3 col-md-3 col-xs-6">
                <div class="str_crd splofr_crd_stls">
                    <a href="#">
						<img src="<?= $store['store_logo_url']?>" alt="<?= $store['name']?>" class="image all-stores">
                        <hr>
						<?php if($store->cashback_disable == 0){ ?>
                        <p class="cat_ttl text-success"><?= $store['cashback']?></p>
						<?php } else{ ?>
							<p class="cat_ttl text-success"></p>
						<?php } ?>
                        <div class="splofr_crd_btm_blk">
						<h5><?= substr($store->name,0,10)?></h5>
                            <p><?= substr($store->description,0,10)?></p>
                        </div>
                    </a>
                </div>
            </div>
		<?php } ?>
        </div>
        <div class="row text-center">
            <div class="col-md-12">
                <div class="all_strs_btn_blk">
                    <a href="<?= Url::toRoute(['site/all-stores'])?>">
                        All Stores <i class="fa fa-chevron-circle-down" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>
 </div>
<?php } ?>

 <!-- CASHBACK EXTENSION SECTION  -->

 <div class="bd_cnt_dglt_mrg extnsn_blk_bg_img">
    <section class="sec_stl">
        <div class="row text-center">
            <div class="col-lg-5 col-md-5 col-xs-12">
                <div class="rfr_ern_blk">
                    <h2><b>Refer & Earn</b></h2>
                    <img src="<?=Yii::$app->request->baseUrl?>/themes/frontend/assets/images/cashbaka/rfr_ern.png" alt="" class="img-responsive">
                    <p>Invite your friends and earn 10% Cashback every time
                        your friend shops via <?= Yii::$app->name?></p>
                    
                </div>
            </div>
            <div class="col-lg-7 col-md-7 col-xs-12">
                <div class="cashbk_extns_blk">
                    <h2><b><?= Yii::$app->name?> Extension</b></h2>
                    <p>Install the <?= Yii::$app->name?> extension, and cashback will always be
                        at your fingertips.Have the possibility of activating
                        cashback in one click on the store’s site. We’ve made sure that
                        the extension will work with your browser:
                        </p>
                    <div class="extnsn_blk_imgs">
                        <a href="#!">
                            <img src="<?=Yii::$app->request->baseUrl?>/themes/frontend/assets/images/cashbaka/mozilla.png" alt="">
                        </a>
                        <a href="#!">
                            <img src="<?=Yii::$app->request->baseUrl?>/themes/frontend/assets/images/cashbaka/google.png" alt="">
                        </a>
                        
                    </div>
                </div>
            </div>
            <div class="col-lg-12 col-md-12 col-xs-12">
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-xs-12">
                        <div class="rfrn_btn_blk">
                            <a href="#!">Get Now <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6 col-xs-12">
                        <div class="csbk_extnsn_btn">
                            <a href="#!">Install For Google Chrome </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- <div class="bd_cnt_dglt_mrg strs_sec_pdng">
    <section class="sec_stl">
        <div class="row text-center">
            <div class="col-md-12">
                <div class="sec_heading">
                    <h1 class="text-uppercase"><b>Frequently Asked Questions</b></h1>
                </div>
            </div>
        </div>
        <div class="row text-center">
	
        </div>
    </section>
</div> -->
 </div>


<!-- MAIN CONTENT END -->