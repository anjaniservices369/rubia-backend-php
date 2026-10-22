<?php

namespace app\modules\admin\models\base;

use Yii;
use app\modules\geo\services\DrivingDistance;
use app\modules\admin\models\UserBookingRatings;
use app\modules\admin\models\base\DriverRequests;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use yii\helpers\StringHelper;
use yii\db\Expression;


/**
 * This is the model class for table "cancel_ride_charges".
 *
 * @property int $id
 * @property int $user_id
 * @property int $booking_id
 * @property int $driver_id
 * @property int $settlement_booking_id
  * @property int $admin_cancellation_earnings
 * @property string $cancel_role
 * @property string $cancel_reason
 * @property string $cancel_charge
 * @property string $status
 * @property string $created_date
 */
class CancelRideCharges extends \yii\db\ActiveRecord
{
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_REJECTED = 'rejected';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cancel_ride_charges';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'booking_id', 'driver_id','settlement_booking_id','cancel_role', 'cancel_reason', 'cancel_charge', 'status','admin_cancellation_earnings'], 'required'], // Add driver_id to the rule
            [['user_id', 'booking_id', 'driver_id','settlement_booking_id','admin_cancellation_earnings'], 'integer'], // Add driver_id to the rule
            [['cancel_role', 'cancel_reason', 'cancel_charge', 'status'], 'string'],
            [['created_date'], 'safe'],
        ];
    }
    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'booking_id' => 'Booking ID',
           'driver_id' => 'Driver ID', 
          'settlement_booking_id' => 'Settlement Booking ID',
            'cancel_role' => 'Cancel Role',
            'cancel_reason' => 'Cancel Reason',
            'cancel_charge' => 'Cancel Charge',
            'status' => 'Status',
            'created_date' => 'Created Date',
          'admin_cancellation_earnings' => 'Admin Cancellation Earnings'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => ['created_date'],
                ],
                'value' => new Expression('NOW()'),
            ],
        ];
    }
}