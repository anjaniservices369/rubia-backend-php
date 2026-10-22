<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\WebSetting as BaseWebSetting;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * This is the model class for table "web_setting".
 */
class WebSetting extends BaseWebSetting
{
    /**
     * @inheritdoc
     */

    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 2;
    
	const WEB_SETTING = 1;
	const RAZORPAY = 2;
	const NOTIFICATION = 3;
	const URL_SETTING = 4;
	const ENABLE_SERVICES = 5;
	const AMOUNT_SETTING = 6;
	const SECRET_ID = 7;
	const CONTENT_SETTING = 8;
	const FAX_SETTING = 9;
	const PAYPAL_SETTING = 10;
	const EMAIL_SETTING = 11;
	const SMPT_SETTING = 12;
    const OTHERS= 0;
    const PAYMENT_GATEWAY = 14;
    const DRIVER_SETTINGS = 70;
    const RIDER_SETTINGS = 80;
    const BIKE_SETTINGS = 81;
    const TAXI_SETTINGS = 82;
    const USER_SETTINGS = 90;

    const MEMBERSHIP_SETTINGS = 20;

	const ENABLE = 1;
	const DISABLE = 0;

	const COMMISSION_FIXED = 1;
    const COMMISSION_PERCENT = 2;

    
    public function rules()
    {
        return array_replace_recursive(parent::rules(),
	    [
            [['name', 'setting_key', 'value'], 'required'],
            [['value'], 'string'],
            [['type_id', 'status', 'create_user_id', 'updated_user_id'], 'integer'],
            [['created_date', 'updated_date'], 'safe'],
            [['name'], 'string', 'max' => 255],
            [['setting_key'], 'string', 'max' => 50]
        ]);
    }
    
    public function getTypeOption() {
		return [ 
				self::WEB_SETTING => "Web Setting",
                self::RAZORPAY => "Razorpay",

				self::NOTIFICATION => "Notification",
				self::URL_SETTING => "Url Setting",
				self::ENABLE_SERVICES => "Enable Services",
				self::AMOUNT_SETTING => "AMOUNT_SETTING",
				self::SECRET_ID => "SECRET_ID",
				self::CONTENT_SETTING => "CONTENT_SETTING",

				self::FAX_SETTING => "FAX_SETTING",
				self::PAYPAL_SETTING => "PAYPAL_SETTING",
				self::EMAIL_SETTING => "EMAIL_SETTING",
				self::SMPT_SETTING => "SMPT_SETTING",
				self::OTHERS => "OTHER",		
		];
	}

	public function getStateOption() {
		return [ 
				self::STATUS_ACTIVE => "Active",
				self::STATUS_INACTIVE => "InActive",
				
		];
	}
    
    public function getSettingBykey($key) {
		$model = self::find ()->where ( ['setting_key'=> $key] )->one ();
		if (! empty ( $model )) {
			// a protected setting (see SettingGuide) is saved encrypted and handed out in plain text only here, to the code that needs it
			return SettingVault::decrypt ( $model->value );
		}
    }

    /**
     * A protected setting (API secret, password, token) is encrypted before it is saved, so a copy of the database does not give the keys away.
     */
    public function beforeSave($insert)
    {
        if (SettingGuide::isSecret((string) $this->setting_key) && (string) $this->value !== '') {
            $this->value = SettingVault::encrypt((string) $this->value);
        }

        return parent::beforeSave($insert);
    }
    public function faviconImage($options = [], $default = "user.png")
    { 
        if (!empty ($this->value)) {
            $file = [
                '/uploads/'. $this->value    
            ];
            //var_dump($file); exit;
        } else {
            $file = \yii::$app->urlManager->createAbsoluteUrl('themes/img/' . $default);
        }
        if (empty ($options)) {
            $options = [
                'class' => 'img-responsive',
                'width' => '90px',
                'height' => '90px'
            ];
        }
        return Html::img($file, $options);
	}
	public function logoImage($options = [], $default = "user.png")
    { 
        if (!empty ($this->value)) {
            $file = [
                '/uploads/'. $this->value    
            ];
            //var_dump($file); exit;
        } else {
            $file = \yii::$app->urlManager->createAbsoluteUrl('uploads/' . $default);
        }
        if (empty ($options)) {
            $options = [
                'class' => 'img-responsive',
                'width' => '90px',
                'height' => '90px'
            ];
        }
        return Html::img($file, $options);
	}
	
	
}
