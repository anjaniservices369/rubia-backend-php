<?php

namespace app\modules\payments\services;

use app\modules\admin\models\Courses;
use Yii;
use yii\base\Component;
use app\modules\admin\models\WebSetting;
use CURLFile;

/**
 * Cashfree client: online payment orders (CreateOrders, verifyPayment) and identity checks used when onboarding
 * drivers (Aadhaar OTP / document verification, police verification, face match). Cash rides never touch it.
 */
class CashFree extends Component
{

  // [doc:start] cred - A credential. None is kept in code: they are pasted in Integrations, or set as environment variables.
  /**
   * A credential. None is kept in code: they are pasted in Integrations, or set as environment variables
   * (CASHFREE_PG_CLIENT_ID, CASHFREE_PG_CLIENT_SECRET, CASHFREE_PG_MODE, CASHFREE_VERIFY_CLIENT_ID, CASHFREE_VERIFY_CLIENT_SECRET,
   * VERIFY24X7_USERNAME, VERIFY24X7_PASSWORD, VERIFY24X7_FACEMATCH_TOKEN). The dashboard value is used when it is filled in.
   */
  public static function cred($key) {
    $value = '';
    try {
      $value = (string) (new WebSetting())->getSettingBykey($key);
    } catch (\Throwable $e) {
      $value = '';
    }
    if (trim($value) === '') {
      $value = (string) \app\modules\admin\models\Integrations::env($key);
    }

    return trim($value);
  }
  // [doc:end] cred

  // [doc:start] pgBase - Pg Base
  public static function pgBase() {
    return self::cred('cashfree_pg_mode') === 'production' ? 'https://api.cashfree.com/pg' : 'https://sandbox.cashfree.com/pg';
  }
  // [doc:end] pgBase


  // [doc:start] getSignature - Get Signature
  public static function getSignature() {
    $clientId = self::cred('cashfree_verify_client_id');
    $publicKey =openssl_pkey_get_public(file_get_contents("web/public-key/accountId_6715_public_key.pem"));
    $encodedData = $clientId.".".strtotime("now");
    return static::encrypt_RSA($encodedData, $publicKey);
  }
  // [doc:end] getSignature


private static function encrypt_RSA($plainData, $publicKey) { if (openssl_public_encrypt($plainData, $encrypted, $publicKey,
OPENSSL_PKCS1_OAEP_PADDING))
      $encryptedData = base64_encode($encrypted);
    else return NULL;
    return $encryptedData;
  }




  
  // [doc:start] getSignaturePayment - Get Signature Payment
  public static function getSignaturePayment() {
    $clientId = self::cred('cashfree_verify_client_id');
    $publicKey =openssl_pkey_get_public(file_get_contents("web/public-key/accountId_6715_public_key.pem"));
    $encodedData = $clientId.".".strtotime("now");
    return static::encrypt_RSAPayment($encodedData, $publicKey);
  }
  // [doc:end] getSignaturePayment


private static function encrypt_RSAPayment($plainData, $publicKey) { if (openssl_public_encrypt($plainData, $encrypted, $publicKey,
OPENSSL_PKCS1_OAEP_PADDING))
      $encryptedData = base64_encode($encrypted);
    else return NULL;
    return $encryptedData;
  }






  

    //Send Notification to Driver
    // [doc:start] CreateOrders - Create Orders
    public function CreateOrders($data)
    {
        $dd = [];
        $setting = new WebSetting();

  

        $customer_id = $data["name"] . "customerid" . Yii::$app->security->generateRandomString(10);
 
        $curl = curl_init();
        $dd['customer_details']['customer_id'] = $customer_id;
        $dd['customer_details']['customer_email'] = $data['email'];
        $dd['customer_details']['customer_phone'] = (string)$data['customer_phone'];
        $dd['order_id'] = (string)$data['order_id'];
        $dd['order_amount'] = (string)$data['order_amount'];
        $dd['order_currency'] = "INR";

        $encodeData = json_encode($dd);

        curl_setopt_array($curl, array(
            CURLOPT_URL => self::pgBase().'/orders',
            // CURLOPT_URL => 'https://api.cashfree.com/pg/orders',

            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $encodeData,
        
                CURLOPT_HTTPHEADER => array(
                    'accept: application/json',
                        'content-type: application/json',
                        'x-api-version: 2022-09-01',
                        'x-client-id: '.self::cred('cashfree_pg_client_id'),
                        'x-client-secret: '.self::cred('cashfree_pg_client_secret'),
                        // 'x-cf-signature:'.self::getSignaturePayment(),
            

          ),


        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;

 


    }
    // [doc:end] CreateOrders


    //Verify Payment
    // [doc:start] verifyPayment - Verify Payment
    public function verifyPayment($orderId)
    {
        $setting = new WebSetting();
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => self::pgBase().'/orders/'.$orderId,
            // CURLOPT_URL => 'https://api.cashfree.com/pg/orders/'.$orderId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
   


            
  CURLOPT_HTTPHEADER => array(
            'accept: application/json',
                'content-type: application/json',
                'x-api-version: 2022-09-01',
                'x-client-id: '.self::cred('cashfree_pg_client_id'),
                 'x-client-secret: '.self::cred('cashfree_pg_client_secret'),
              //  'x-cf-signature:'.self::getSignaturePayment(),
    

  ),



        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
    // [doc:end] verifyPayment






// [doc:start] sendAadharOtp - Send Aadhar Otp
public function sendAadharOtp($aadhaar_number){
    $curl = curl_init();
 

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://api.cashfree.com/verification/offline-aadhaar/otp',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'{
    "aadhaar_number":"'.$aadhaar_number.'"
}',

  CURLOPT_HTTPHEADER => array(
    'x-client-id: '.self::cred('cashfree_verify_client_id'),
    'x-client-secret: '.self::cred('cashfree_verify_client_secret'),
    'x-cf-signature:'.self::getSignature(),
    'x-api-version: 2022-09-01',
    'Content-Type: application/json'

  ),


));

$response = curl_exec($curl);

curl_close($curl);
return $response;
}
// [doc:end] sendAadharOtp




// [doc:start] verifyAadhar - Verify Aadhar
public function verifyAadhar($ref_id,$otp){
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api.cashfree.com/verification/offline-aadhaar/verify',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS =>'{
    "otp":"'.$otp.'",
    "ref_id":"'.$ref_id.'"
    }',
      CURLOPT_HTTPHEADER => array(
        'x-client-id: '.self::cred('cashfree_verify_client_id'),
        'x-client-secret: '.self::cred('cashfree_verify_client_secret'),
        'x-cf-signature:'.self::getSignature(),
        'x-api-version: 2022-09-01',
        'Content-Type: application/json'
      ),
    ));
    
    $response = curl_exec($curl);
    
    curl_close($curl);
    return $response;
}
// [doc:end] verifyAadhar





// [doc:start] documentAadharVerify - Document Aadhar Verify
public function documentAadharVerify($front_image,$back_image){

    $str = rand(1111111111,9999999999);
    $verification_id =  md5($str);
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api.cashfree.com/verification/document/aadhaar',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => array('verification_id' => $verification_id,
      'front_image'=> $front_image,
      'back_image'=>$back_image
    
    ),
      CURLOPT_HTTPHEADER => array(
        'x-client-id: '.self::cred('cashfree_verify_client_id'),
        'x-client-secret: '.self::cred('cashfree_verify_client_secret'),
        'x-cf-signature:'.self::getSignature(),
        'x-api-version: 2022-09-01',
        'Content-Type: application/json'
      ),
    ));
    
    $response = curl_exec($curl);
    
    curl_close($curl);
    return $response;
}
// [doc:end] documentAadharVerify




// [doc:start] policeVerificationSignIn - Police Verification Sign In
public function policeVerificationSignIn(){

  $curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://mvp.verify24x7.in/verifyManualApi/api/users/signIn',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => 'username='.urlencode(self::cred('verify24x7_username')).'&password='.urlencode(self::cred('verify24x7_password')),
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/x-www-form-urlencoded'
  ),
));

$response = curl_exec($curl);
curl_close($curl);
return  $response;
}
// [doc:end] policeVerificationSignIn

// [doc:start] verifyManual - Verify Manual
public function verifyManual($name,$worker_id,$address,$dob,$fatherName){

  $policeVerificationSignIn = json_decode($this->policeVerificationSignIn());
  $curl = curl_init();

  curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://mvp.verify24x7.in/verifyManualApi/api/tasks/addCases',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS =>'{
  "username":"'.self::cred('verify24x7_username').'",
  "name":"'.$name.'",
  "applicantId":"'.$worker_id.'",
  "addresses":[{"address":"'.$address.'","periodOfStay":""}],
  "dob":"'.$dob.'",
  "fatherName":"'.$fatherName.'",
  "type":"lawyer"
  }',
    CURLOPT_HTTPHEADER => array(
      'Content-Type: application/json',
      'Authorization: Bearer '.$policeVerificationSignIn->apiKey
    ),
  ));
  
  $response = curl_exec($curl);
  
  curl_close($curl);
  return  $response;



}
// [doc:end] verifyManual


// [doc:start] getCaseStatus - Get Case Status
public function getCaseStatus($verifyId){
  $policeVerificationSignIn = json_decode($this->policeVerificationSignIn());

  $curl = curl_init();

  curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://mvp.verify24x7.in/verifyManualApi/api/tasks/getCaseStatus',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS =>'{
  "username": "'.self::cred('verify24x7_username').'",
  "verifyId":"'.$verifyId.'"
  }',
    CURLOPT_HTTPHEADER => array(
      'Content-Type: application/json',
      'Authorization: Bearer '.$policeVerificationSignIn->apiKey
    ),
  ));
  
  $response = curl_exec($curl);
  
  curl_close($curl);
  return  $response;

}
// [doc:end] getCaseStatus





// [doc:start] faceMatch - Face Match
public function faceMatch($img1,$img2){


$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://apiv3.verify24x7.in/compare_faces',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => array('auth_token' => self::cred('verify24x7_facematch_token'),
  'img1'=> new CURLFILE($img1,'image/jpeg'),
  'img2'=> new CURLFILE($img2,'image/jpeg')),

));

$response = curl_exec($curl);

curl_close($curl);
return  $response;


}
// [doc:end] faceMatch








}
