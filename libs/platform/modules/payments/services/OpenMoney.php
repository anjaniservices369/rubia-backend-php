<?php

namespace app\modules\payments\services;

use app\modules\admin\models\AuthSession;
use app\modules\admin\models\OpenMoneyContacts;
use app\modules\admin\models\OpenMoneyLinkedContact;
use app\modules\admin\models\WebSetting;
use Yii;
use yii\base\Component;

class OpenMoney extends Component
{




    // [doc:start] generateToken - Generate Token
    public function generateToken($data)

    {
        $settings = new WebSetting();
        $secerate_key = \app\modules\admin\models\Integrations::value('open_money_secret');
        $api_key = \app\modules\admin\models\Integrations::value('open_money_key');

        $token = $api_key . ':' . $secerate_key;
    
        $curl = curl_init();

        curl_setopt_array($curl, array(
             CURLOPT_URL => 'https://icp-api.bankopen.co/api/payment_token', //https://icp-api.bankopen.co/api/payment_token
        //    CURLOPT_URL => 'https://sandbox-icp-api.bankopen.co/api/payment_token', //https://icp-api.bankopen.co/api/payment_token
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
     "amount": "' . $data["final_price"] . '",
     "udf": "",
     "contact_number": "' . $data["contact_no"] . '",
     "email_id": "' . $data["email_id"] . '",
     "currency": "INR",
     "mtx": "' . rand(0000000000, 111111100) . date('Y-m-d-H-i-s') . '"
}',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
    // [doc:end] generateToken

    // Payment Status

    // [doc:start] paymentStatus - Payment Status
    public function paymentStatus($paymentToken)
    {

        $settings = new WebSetting();
        $secerate_key = $settings->getSettingBykey('open_money_secret');
        $api_key = $settings->getSettingBykey('open_money_key');

        $token = $api_key . ':' . $secerate_key;
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://icp-api.bankopen.co/api/payment_token/' . $paymentToken . '/payment',
            // CURLOPT_URL => 'https://sandbox-icp-api.bankopen.co/api/payment_token/' . $paymentToken . '/payment',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $token,
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
    // [doc:end] paymentStatus

   
}
