<?php

use yii\helpers\Html;
use yii\helpers\Url;
use kartik\form\ActiveForm;
use kartik\widgets\FileInput;
// use yii\widgets\ActiveForm;
use app\models\Setting;
use app\modules\admin\models\WebSetting;

// var_dump($paypal_setting);exit;
/* @var $this yii\web\View */
/* @var $model app\models\Setting */

$this->title = Yii::t('app', 'CMS');
$this->params['header'] = Yii::t('app', 'Settings');
$this->params['description'] = Yii::t('app', 'Create');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Settings'), 'url' => ['cms']];
$this->params['breadcrumbs'][] = $this->title;


?>
<style>
.file-loading:before {
display:none}
</style>
<div class="setting-create">
<div class="card">
<div class="card-body"> 
<div class="row">
  <div class="col-md-12">
    <div class="nav-tabs-custom">
    <div class="nav nav-tabs nav-fill" id="nav-tab" role="tablist">
    <a class="nav-item nav-link active" id="nav-home-tab" data-bs-toggle="tab" href="#web_setting" role="tab" aria-controls="nav-home" aria-selected="true">Website Setting</a>
  
	<!-- <a class="nav-item nav-link" id="nav-contact-tab" data-bs-toggle="tab" href="#amount_settings" role="tab" aria-controls="nav-contact" aria-selected="false">Amount Setting</a> -->
	<!-- <a class="nav-item nav-link" id="nav-contact-tab" data-bs-toggle="tab" href="#social_login" role="tab" aria-controls="nav-contact" aria-selected="false">Social Login Setting</a> -->
	<!-- <a class="nav-item nav-link" id="nav-contact-tab" data-bs-toggle="tab" href="#url_setting" role="tab" aria-controls="nav-contact" aria-selected="false">Url Setting</a> -->
    <a class="nav-item nav-link" id="nav-contact-tab" data-bs-toggle="tab" href="#notification_setting" role="tab" aria-controls="nav-contact" aria-selected="false">Notification Setting</a>
    <a class="nav-item nav-link" id="nav-driver-tab" data-bs-toggle="tab" href="#driver_setting" role="tab" aria-controls="nav-contact" aria-selected="false">Driver Setting</a>
    <a class="nav-item nav-link" id="nav-skipper-tab" data-bs-toggle="tab" href="#rider_settings" role="tab" aria-controls="nav-contact" aria-selected="false">Rider Setting</a>
    <a class="nav-item nav-link" id="nav-user-tab" data-bs-toggle="tab" href="#user_settings" role="tab" aria-controls="nav-contact" aria-selected="false">User Setting</a>
  
						
	</div>
      <!--<ul class="nav nav-tabs">
        <li class="active"><a href="#web_setting" data-bs-toggle="tab" aria-expanded="false">Website Setting</a></li>
        <li class=""><a href="#razor_pay" data-bs-toggle="tab" aria-expanded="false">RazorPay</a></li>
        <li class=""><a href="#notification" data-bs-toggle="tab" aria-expanded="false">Notifications</a></li>
        <li class=""><a href="#amount_settings" data-bs-toggle="tab" aria-expanded="true">Amount Setting</a></li>
       
      </ul>-->
     <div class="tab-content">
     
        
      <div class="tab-pane active" id="web_setting">
    <div class="cms-form">
        <?php $form = ActiveForm::begin(); ?>
        <div class="row">
            <?php foreach ($web_settings as $setting) : ?>
                <?php if (in_array($setting->setting_key, \app\modules\admin\models\Integrations::managedKeys(), true)) { continue; } ?>
                <div class="col-md-6">
                    <?= $form->field($setting, 'setting_id')->hiddenInput(['maxlength' => true])->label(false) ?>
                    <label><?= $setting->name ?></label>
                    <?= $form->field($setting, 'value')->textInput(['id' => 'cms_' . $setting->setting_id, 'data-id' => $setting->setting_id])->label(false) ?>
                </div>
            <?php endforeach; ?>
            <?php $adminCancellationearningSetting = WebSetting::findOne(['setting_key' => 'admin_cancellation_earning']); ?>
            <?php if ($adminCancellationearningSetting && !in_array('admin_cancellation_earning', \app\modules\admin\models\Integrations::managedKeys(), true)) : ?>

            <div class="col-md-6">
                <label>Admin Cancellation Earning</label>
                <?= $form->field($adminCancellationearningSetting, 'value')->textInput(['id' => 'cms_' . $adminCancellationearningSetting->setting_id, 'data-id' => $adminCancellationearningSetting->setting_id])->label(false) ?>
            </div>
            <?php endif; ?>

        </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>
       
       
       
       
       
    <div class="tab-pane" id="notification_setting">
        <div class="cms-form">
            <?php $form = ActiveForm::begin(); ?>
            <p class="text-muted">
                These settings have moved to the <a href="<?= \yii\helpers\Url::to(['/admin/integrations/index']) ?>">Integrations</a> module (SMS, push, and each
                service's own Charges page). Anything still listed below hasn't been assigned to a service yet.
            </p>
            <div class="row">
                <?php foreach ($notification as $setting) { ?>
                    <?php if (in_array($setting->setting_key, \app\modules\admin\models\Integrations::managedKeys(), true)) { continue; } ?>
                    <?php $isSecret = \app\modules\admin\models\SettingGuide::isSecret($setting->setting_key); ?>
                    <div class="col-md-6">
                        <?= $form->field($setting, 'setting_id')->hiddenInput(['maxlength' => true])->label(false) ?>
                        <label class="d-block"><?= \yii\helpers\Html::encode($setting->name) ?> <?= \app\modules\admin\models\SettingGuide::badge($setting->setting_key, trim((string) $setting->value) !== '') ?></label>
                        <?php if ($isSecret) { ?>
                            <input type="password" class="form-control" id="cms_<?= (int) $setting->setting_id ?>"
                                   data-id="<?= (int) $setting->setting_id ?>" autocomplete="new-password" value=""
                                   placeholder="<?= $setting->value !== '' ? 'saved and protected - type here only to replace it' : 'paste the REST API key here' ?>">
                            <div class="small text-muted"><?= \app\modules\admin\models\SettingGuide::note($setting->setting_key) ?></div>
                        <?php } else { ?>
                            <?= $form->field($setting, 'value')->textInput(['id' => 'cms_' . $setting->setting_id, 'data-id' => $setting->setting_id, 'autocomplete' => 'off'])->label(false) ?>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
        </div>

    


<!-- ... (existing code) -->


           


         

        <!-- Driver Settings -->
        <div class="tab-pane" id="driver_setting">
        <div class="cms-form">
            <?php $form = ActiveForm::begin(); ?>
            <div class="row">
                <?php foreach ($driver_settings as $setting){?>
                <?php if (in_array($setting->setting_key, \app\modules\admin\models\Integrations::managedKeys(), true)) { continue; } ?>
                <div class="col-md-6">
                    <?= $form->field($setting, 'setting_id')->hiddenInput(['maxlength' => true])->label(false) ?>
                    <label><?= $setting->name ?></label>
                    <?= $form->field($setting, 'value')->textInput(['id'=>'cms_'.$setting->setting_id,'data-id'=>$setting->setting_id])->label(false) ?>
                </div>
            <?php }?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
        </div>
        <!-- Taxi Driver Settings -->
        <div class="tab-pane" id="rider_settings">
        <div class="cms-form card p-2">
        <div class="card-title"> Auto</div>
            <?php $form = ActiveForm::begin(); ?>
            <div class="row">
                <?php foreach ($rider_settings as $setting){?>
                <?php if (in_array($setting->setting_key, \app\modules\admin\models\Integrations::managedKeys(), true)) { continue; } ?>
                <div class="col-md-6">
                    <?= $form->field($setting, 'setting_id')->hiddenInput(['maxlength' => true])->label(false) ?>
                    <label><?= $setting->name ?></label>
                    <?= $form->field($setting, 'value')->textInput(['id'=>'cms_'.$setting->setting_id,'data-id'=>$setting->setting_id])->label(false) ?>
                </div>
            <?php }?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
        <div class="cms-form card p-2">
        <div class="card-title"> Bike</div>
            <?php $form = ActiveForm::begin(); ?>
            <div class="row">
                <?php foreach ($bike_settings as $setting){?>
                <?php if (in_array($setting->setting_key, \app\modules\admin\models\Integrations::managedKeys(), true)) { continue; } ?>
                <div class="col-md-6">
                    <?= $form->field($setting, 'setting_id')->hiddenInput(['maxlength' => true])->label(false) ?>
                    <label><?= $setting->name ?></label>
                    <?= $form->field($setting, 'value')->textInput(['id'=>'cms_'.$setting->setting_id,'data-id'=>$setting->setting_id])->label(false) ?>
                </div>
            <?php }?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
        <div class="cms-form card p-2">
            <div class="card-title"> Taxi</div>
            <?php $form = ActiveForm::begin(); ?>
            <div class="row">
                <?php foreach ($taxi_settings as $setting){?>
                <?php if (in_array($setting->setting_key, \app\modules\admin\models\Integrations::managedKeys(), true)) { continue; } ?>
                <div class="col-md-6">
                    <?= $form->field($setting, 'setting_id')->hiddenInput(['maxlength' => true])->label(false) ?>
                    <label><?= $setting->name ?></label>
                    <?= $form->field($setting, 'value')->textInput(['id'=>'cms_'.$setting->setting_id,'data-id'=>$setting->setting_id])->label(false) ?>
                </div>
            <?php }?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
        </div>
       
       
        <!-- User Settings -->
        <div class="tab-pane" id="user_settings">
        <div class="cms-form">
            <?php $form = ActiveForm::begin(); ?>
            <div class="row">
                <?php foreach ($user_settings as $setting):?>
                <?php if (in_array($setting->setting_key, \app\modules\admin\models\Integrations::managedKeys(), true)) { continue; } ?>
                <div class="col-md-6">
                    <?= $form->field($setting, 'setting_id')->hiddenInput(['maxlength' => true])->label(false) ?>
                    <label><?= $setting->name ?></label>
                    <?= $form->field($setting, 'value')->textInput(['id'=>'cms_'.$setting->setting_id,'data-id'=>$setting->setting_id])->label(false) ?>
                </div>
                <?php  endforeach; ?> 
            <?php 	
            $notificationPeriodSetting = WebSetting::findOne(['setting_key' => 'notification_period']); ?>

            <?php if ($notificationPeriodSetting && !in_array('notification_period', \app\modules\admin\models\Integrations::managedKeys(), true)) : ?>
            <div class="col-md-6">
                <label>Notification Period</label>
                <?= $form->field(  $notificationPeriodSetting, 'value')->textInput(['id' => 'cms_' . $notificationPeriodSetting->setting_id, 'data-id' => $notificationPeriodSetting->setting_id])->label(false) ?>
            </div>
            <?php endif; ?>
            
            </div>
            <?php ActiveForm::end(); ?>
        </div>
        </div>
       
       
       
       
       
        <div class="tab-pane" id="url_setting">
        <div class="cms-form">
            <?php $form = ActiveForm::begin(); ?>
            <div class="row">
                <?php foreach ($url_settings as $setting){?>
                <div class="col-md-6">
                    <?= $form->field($setting, 'setting_id')->hiddenInput(['maxlength' => true])->label(false) ?>
                    <label><?= $setting->name ?></label>
                    <?= $form->field($setting, 'value')->textInput(['id'=>'cms_'.$setting->setting_id,'data-id'=>$setting->setting_id])->label(false) ?>
                </div>
            <?php }?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
        </div>
      
       
        <div class="tab-pane" id="amount_settings">
        <div class="cms-form">
            <?php $form = ActiveForm::begin(); ?>
            <div class="row">


            <?php foreach ($amount_settings as $setting){?>
                    <?php if($setting->setting_key=='commission_type'){
                        echo '<div class=" col-md-6">';
                        echo $form->field($setting, 'setting_id')->hiddenInput(['maxlength' => false])->label(false);
                        echo "<label>".$setting->name."</label>";
                        echo  $form->field($setting, 'value')->dropDownList(['1' => 'COMMISSION_FIXED', '2' => 'COMMISSION_PERCENT'], ['prompt'=>'Select Option','class'=>'get_selected_value form-control','id'=>'cms_'.$setting->setting_id,'data-id'=>$setting->setting_id])->label(false);
                        echo '</div>';
                    }
                    else if($setting->setting_key=='cashback_type'){
                        echo '<div class=" col-md-6">';
                        echo $form->field($setting, 'setting_id')->hiddenInput(['maxlength' => false])->label(false);
                        echo "<label>".$setting->name."</label>";
                        echo  $form->field($setting, 'value')->dropDownList(['1' => 'COMMISSION_FIXED', '2' => 'COMMISSION_PERCENT'], ['prompt'=>'Select Option','class'=>'get_selected_value form-control','id'=>'cms_'.$setting->setting_id,'data-id'=>$setting->setting_id])->label(false);
                        echo '</div>';
                    }

                    else if($setting->setting_key=='enable_signup_bonus'){
                        echo '<div class=" col-md-6">';
                        echo $form->field($setting, 'setting_id')->hiddenInput(['maxlength' => false])->label(false);
                        echo "<label>".$setting->name."</label>";
                        echo  $form->field($setting, 'value')->dropDownList(['1' => 'Yes', '0' => 'No'], ['prompt'=>'Select Option','class'=>'get_selected_value form-control','id'=>'cms_'.$setting->setting_id,'data-id'=>$setting->setting_id])->label(false);
                        echo '</div>';
                    }
                    else if($setting->setting_key=='enable_social_media'){
                        echo '<div class=" col-md-6">';
                        echo $form->field($setting, 'setting_id')->hiddenInput(['maxlength' => false])->label(false);
                        echo "<label>".$setting->name."</label>";
                        echo  $form->field($setting, 'value')->dropDownList(['1' => 'Enable', '0' => 'Disable'], ['prompt'=>'Select Option','class'=>'get_selected_value form-control','id'=>'cms_'.$setting->setting_id,'data-id'=>$setting->setting_id])->label(false);
                        echo '</div>';
                    }
                    
                    
                    else{
                        echo '<div class="col-md-6">';
                        echo '<div id="'.$setting->setting_key.'">';
                        echo $form->field($setting, 'setting_id')->hiddenInput(['maxlength' => true])->label(false);
                        echo '<label>'.$setting->name.'</label>';
                        echo $form->field($setting, 'value')->textInput(['id'=>'cms_'.$setting->setting_id,'data-id'=>$setting->setting_id])->label(false);
                        echo '</div></div>';

                    }
                     ?>
            <?php }?>
                <?php /*foreach ($amount_settings as $setting){?>
                <div class="col-md-6">
                    <?= $form->field($setting, 'id')->hiddenInput(['maxlength' => true])->label(false) ?>
                    <label><?= $setting->name ?></label>
                    <?= $form->field($setting, 'value')->textInput(['id'=>'cms_'.$setting->id,'data-id'=>$setting->id])->label(false) ?>
                </div>
            <?php }*/ ?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
        </div>


                <!-- ... (existing code) -->

        <div class="tab-pane" id="secret_id">
        <div class="cms-form">
            <?php $form = ActiveForm::begin(); ?> 
            <div class="row">
                <?php foreach ($secret_id as $setting){?>
                    <?php if($setting->setting_key=='enable_otp_verification' || $setting->setting_key=='enable_google_login' || $setting->setting_key=='enable_time_slot' ){
                        echo '<div class=" col-md-6">';
                        echo $form->field($setting, 'setting_id')->hiddenInput(['maxlength' => false])->label(false);
                        echo "<label>".$setting->name."</label>";
                        echo  $form->field($setting, 'value')->dropDownList(['1' => 'Yes', '0' => 'No'], ['prompt'=>'Select Option','class'=>'get_selected_value form-control','id'=>'cms_'.$setting->setting_id,'data-id'=>$setting->setting_id])->label(false);
                        echo '</div>';
                    }else{
                        echo '<div class="col-md-6">';
                        echo '<div id="'.$setting->setting_key.'">';
                        echo $form->field($setting, 'setting_id')->hiddenInput(['maxlength' => true])->label(false);
                        echo '<label>'.$setting->name.'</label>';
                        echo $form->field($setting, 'value')->textInput(['id'=>'cms_'.$setting->setting_id,'data-id'=>$setting->setting_id])->label(false);
                        echo '</div></div>';

                    }
                     ?>
            <?php }?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
        </div>

       

      </div>
    </div>
  </div>
</div>
</div>
</div>
</div>
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<script>
    $(document).on('click', '.nav-link', function () {
        var tabId = $(this).attr('href');
        if (tabId === '#paymentgateway_settings') {
            $('#paymentGatewaySettings').show();
        } else {
            $('#paymentGatewaySettings').hide();
        }
    });

    $(document).on('change', 'input[id^=cms_]', function () {
        var id = $(this).attr('data-id');
        var value = $(this).val();
        $.ajax({
            type: "POST",
            url: "<?= Url::toRoute(['web-setting/save-cms']) ?>",
            data: { id: id, value: value },
            cache: false,
            success: function (data) {
                swal("Good job!", "Settings Saved!", "success");
            }
        });
    });

    $(document).on("change", 'select[id^=cms_]', function () {
        var id = $(this).attr('data-id');
        var value = $(this).val();
        $.ajax({
            type: "POST",
            url: "<?= Url::toRoute(['web-setting/save-cms']) ?>",
            data: { id: id, value: value },
            cache: false,
            success: function (data) {
                swal("Good job!", "Settings Saved!", "success");
            }
        });
    });
</script>







