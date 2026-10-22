
<?php
   use yii\helpers\Url;
   use yii\helpers\Html;
use app\models\Setting;

?>


<style>
    @media only screen and (max-width: 600px) {
        html, body {
        width: 100%;
        overflow-x: hidden;
    }
    .rm_img_trnsfrm_stl img{
    left: unset;
    transform: unset;
    }
    }


    /* SOCIAL MEDIA BUTTON STYLES */

        .scl_mda_invt_blk .fa {
            padding: 10px 20px;
            font-size: 20px;
            text-align: center;
            margin: 15px 2px 0;
            border-radius: 3px;
            transition: 0.7s;
        }

        .scl_mda_invt_blk .fa:hover {
            opacity: 0.7;
            border-radius: 5px;
            -ms-transform: rotate(5deg); /* IE 9 */
        transform: rotate(5deg); /* Standard syntax */
        transition: 0.7s;
        }

       

        .cpy_lnk_blk .myInput, .invt_emal_blk form input {
        border: 1px solid #b1b1b1;
        height: auto !important;
        padding: 10px;
        border-radius: 3px;
        width: 100%;
        margin-top: 1%;
        margin-bottom: 3%;
    }
            
</style>

<script type='text/javascript' src='https://platform-api.sharethis.com/js/sharethis.js#property=5d28962147eb1a0012ad36a3&product=inline-share-buttons' async='async'></script>
<!-- MAIN CONTENT START -->
<div class="bd_cnt_dglt_mrg">
<section class="sec_stl top_bnr_sec_pd">
  
<div class="row">
    
            <div class="col-lg-2 col-md-2 col-sm-2 hidden-xs"></div>
              <div class="col-lg-8 col-md-8 col-sm-8 col-xs-12">
            
        <div class=" str_crd splofr_crd_stls rfrl_blk">
            <h1>Contact Us</h1>
        <form id="contact">
            <div class="cpy_lnk_blk">
                <span>Name: </span>
                <input type="text"  class="myInput" id="name" required/>
            </div>
            <div class="cpy_lnk_blk">
                <span>Email: </span>
                <input type="email" class="myInput"  id="email" required/>
            </div>
            <div class="cpy_lnk_blk">
                <span>Choose Topic: </span>
                <select name="cars"  class="myInput" id="subject">
                <option>Select from list below</option>
                <option value="General enquires">General enquires</option> 
                <option value="My cashback is incorrect or missing">My cashback is incorrect or missing</option> 
                <option value="Get Listed on our site">Get Listed on our site</option> 
                <option value="Partnerships">Partnerships</option> 
                <option value="Media">Media</option> 
                <option value="Other">Other</option>
                </select>
                <!-- <input type="text"  class="myInput" id="subject"> -->
            </div>
            <div class="cpy_lnk_blk">
                <span>Message: </span>
                <textarea  class ="myInput" id="message"></textarea>
            </div>
           
            <button class="btn btn-md strt_erng_btn1 center accent-color" id="con-emailsend">SUBMIT</button>
           
            </form>
            <br>
        
        </div>
    </div>
</div>

</section>





</div>

<!-- MAIN CONTENT END -->


<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

<script type="text/javascript">
 $('#con-emailsend').on('click',function(){
 event.preventDefault();
 //$("#preloader1").css("display","block");
// $("#status1").css("display","block");
 var name = $('#name').val();
 var subject = $('#subject').val();
var email = $('#email').val();
var message = $('#message').val();
//alert(subject);

//var referal_code ="<?php //echo  $profileModel->referal_code;?>";
 var check_email = RegExp("^([a-z0-9\\+_\\-]+)(\\.[a-z0-9\\+_\\-]+)*@([a-z0-9\\-]+\\.)+[a-z]{2,6}$", 'ig');
        if(name == "")
          {
              swal("", "Name Cannot be empty", "error"); 
              return false;
          }
         else if(email == "")
          {
              swal("", "Email Cannot be empty", "error"); 
              return false;
          }

          else if(!check_email.test(email))
          {
              //alert("enter valid email address!");
              swal("", "Please Enter Valid Email Address!", "error");
             // $('#status1').fadeOut();
            //  $('#preloader1').delay(350).fadeOut('slow');
            //  $('body').delay(350).css({'overflow':'visible'});;
              return false;
          }
          else if(message == "")
          {
              swal("", "Message Cannot be empty", "error"); 
              return false;
          }
          else if(subject == "")
          {
              swal("", "Subject Cannot be empty", "error"); 
              return false;
          }


        else{
          $.ajax({
            type: 'POST',
            url: " <?=Url::toRoute(['contactmail'])?>",
          data:{ name:name,subject:subject,email:email,message:message},
           success:function(response){
            //$('#status1').fadeOut();
             // $('#preloader1').delay(350).fadeOut('slow');
             // $('body').delay(350).css({'overflow':'visible'});;
               swal("Thank you!", "Our team will contact you soon", "success"); 
                $("#contact")[0].reset();
            },
            error:function(xhr, status, error){
              //alert(xhr.responseText);
              //$('#status1').fadeOut();
              //$('#preloader1').delay(350).fadeOut('slow');
             // $('body').delay(350).css({'overflow':'visible'});;
              swal("Sorry!", "Something Went Wrong!", "error"); 
              location.reload();
            }
  });
        }


 });

  
</script>