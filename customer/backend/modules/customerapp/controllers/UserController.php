<?php
namespace app\modules\customerapp\controllers;

use app\modules\api\controllers\BKController;
use yii;
use yii\filters\AccessControl;
use yii\filters\AccessRule;
use yii\helpers\ArrayHelper;
use app\modules\auth\services\AuthSettings;
use yii\data\ActiveDataProvider;
use yii\helpers\Url;
use app\models\User;
use app\modules\admin\models\Auth;
use app\modules\admin\models\WebSetting;
use app\modules\admin\models\AuthSession;
use app\modules\admin\models\BannerCategorys;
use app\modules\admin\models\Booking;
use app\modules\admin\models\RideRequest;
use app\modules\admin\models\City;
use app\modules\admin\models\Coupon;
use app\modules\admin\models\DriverBookingRatings;
use app\modules\admin\models\DriverRequests;
use app\modules\admin\models\DriverWallet;
use app\modules\admin\models\FavouriteLocation;
use app\modules\admin\models\FcmNotification;
use app\modules\admin\models\Notification;
use app\modules\admin\models\Services;
use app\modules\admin\models\UserBookingRatings;
use app\modules\admin\models\UserEmergencyContacts;
use app\modules\admin\models\UserReferralEarnings;
use app\modules\admin\models\VehicleTypes;
use app\modules\admin\models\CityRateCard;
use app\modules\admin\models\base\UserWallet;
use app\modules\admin\models\base\CancelRideCharges;


use Exception;
use kartik\mpdf\Pdf;
use yii\helpers\Json;
/**
 * Customer app API: /api/user/* - the user app's own endpoints (see UserAppActions). The booking flow of each service is
 * served by that service (services/<service>/customer-backend).
 */
class UserController extends BKController
{
    use UserAppActions;

    // [doc:start] behaviors - Behaviors
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'corsFilter' => [
                'class' => \yii\filters\Cors::className(),
                'cors' => [
                    // restrict access to
                    'Origin' => [
                        'http://localhost:*',
                        'http://localhost:58600',
                    ],
                    // Allow only POST and PUT methods
                    'Access-Control-Request-Method' => ['POST', 'PUT'],
                    // Allow only headers 'X-Wsse'
                    'Access-Control-Request-Headers' => ['X-Wsse'],
                    // Allow credentials (cookies, authorization headers, etc.) to be exposed to the browser
                    'Access-Control-Allow-Credentials' => true,
                    // Allow OPTIONS caching
                    'Access-Control-Max-Age' => 3600,
                    // Allow the X-Pagination-Current-Page header to be exposed to the browser.
                    'Access-Control-Expose-Headers' => [
                        'X-Pagination-Current-Page',
                    ],
                ],
            ],

            'access' => [
                'class' => AccessControl::className(),
                'ruleConfig' => [
                    'class' => AccessRule::className(),
                ],

                'rules' => [
                    [
                        'actions' => [
                            'check',
                            'verify-otp',
                            'send-otp',
                            'resend-otp',
                            'index',
                            'update-profile',
                            'delete-account',
                            'add-user-emergency-contacts',
                            'add-emergency-contact',
                            'update-emergency-contact',
                            'list-emergency-contacts',
                            'delete-emergency-contact',
                            'update-lat-lng',
                            'apply-referral',
                            'services',
                            'logout',
                            'list-favourite-location',
                            'create-favourite-location',
                            'update-favourite-location',
                            'delete-favourite-location',
                            'list-fcm-notifications',
                            'get-estimate',
                            'coupons-list',
                            'check-boundary',
                            'vehicle-types',
                            'apply-coupon',
                            'assign-booking-to-driver',
                            'cancel-ride',
                            'ride-info',
                            'add-review',
                            'remove-coupon',
                            'rating-and-reviews',
                            'view-booking-details',
                            'clear-notifications',
                            'banner-with-category',
                            'update-profile-pic',
                            'my-referral-earings',
                            'pay-now',
                            'ride-request-active',
                            'ride-request-completed',
                            'ride-request-cancelled',
                          	'cancellation-charges',
                           'current-city',
                            'legal-page',
                            'my-transactions',
                         
                        ],  

                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => [
                            'check',
                            'verify-otp',
                            'send-otp',
                            'resend-otp',
                            'index',
                            'update-profile',
                            'add-user-emergency-contacts',
                            'update-emergency-contact',
                            'list-emergency-contacts',
                            'delete-emergency-contact',
                            'update-lat-lng',
                            'apply-referral',
                            'services',
                            'logout',
                            'list-favourite-location',
                            'create-favourite-location',
                            'update-favourite-location',
                            'delete-favourite-location',
                            'list-fcm-notifications',
                            'get-estimate',
                            'coupons-list',
                            'check-boundary',
                            'vehicle-types',
                            'apply-coupon',
                            'assign-booking-to-driver',
                            'cancel-ride',
                            'ride-info',
                            'add-review',
                            'remove-coupon',
                            'rating-and-reviews',
                            'view-booking-details',
                            'clear-notifications',
                            'banner-with-category',
                            'update-profile-pic',
                            'my-referral-earings',
                            'pay-now',
                            'ride-request-active',
                            'ride-request-completed',
                            'ride-request-cancelled',
							'cancellation-charges',
                           'current-city',
                            'legal-page',
                            'my-transactions',
                          

                        ],

                        'allow' => true,
                        'roles' => ['?', '*'],
                    ],
                ],
            ],
        ]);
    }
    // [doc:end] behaviors
}
