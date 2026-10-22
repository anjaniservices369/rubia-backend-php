<?php

namespace app\modules\admin\models\base;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
/**
 * This is the base model class for table "driver_requests".
 *
 * @property integer $id
 * @property integer $driver_id
 * @property integer $booking_id
 * @property integer $status
 * @property string $created_on
 * @property string $updated_on
 * @property integer $create_user_id
 * @property integer $update_user_id
 *
 * @property \app\modules\admin\models\Booking $booking
 * @property \app\modules\admin\models\User $driver
 * @property \app\modules\admin\models\User $createUser
 * @property \app\modules\admin\models\User $updateUser
 */
class DriverRequests extends \yii\db\ActiveRecord
{
    use \mootensai\relation\RelationTrait;

    /**
     * This function helps \mootensai\relation\RelationTrait runs faster
     * @return array relation names of this model
     */
    public function relationNames()
    {
        return ['booking', 'driver', 'createUser', 'updateUser'];
    }
    const STATUS_WAITING_FOR_DRIVER = 1;
    const STATUS_DRIVER_ACCEPTED = 2;
    const STATUS_DRIVER_REJECTED = 3;
    const STATUS_GO_FOR_PICKUP = 4;
    const STATUS_ARRIVED_LOCATION = 5;
    const STATUS_START_RIDE = 6;
    const STATUS_RIDE_COMPLETED = 7;
    const STATUS_USER_CANCELLED = 8;
    const STATUS_BOOKING_AUTO_CANCELLED = 9;
    const STATUS_DRIVER_CANCELLED = 10;
    const STATUS_CANCELLED = 11;
    const STATUS_ACCEPTED_RIDES = 12;

    const IS_FEATURED = 1;
    const IS_NOT_FEATURED = 0;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['driver_id', 'booking_id'], 'required'],
            [
                [
                    'driver_id',
                    'booking_id',
                    'status',
                    'create_user_id',
                    'update_user_id',
                ],
                'integer',
            ],
            [['created_on', 'updated_on'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'driver_requests';
    }

    public function getStateOptions()
    {
        return [
            self::STATUS_WAITING_FOR_DRIVER => 'Waiting for Driver',
            self::STATUS_DRIVER_ACCEPTED => 'Driver Accepted',
            self::STATUS_DRIVER_REJECTED => 'Driver Rejected',
            self::STATUS_GO_FOR_PICKUP => 'Go for Pickup',
            self::STATUS_ARRIVED_LOCATION => 'Arrived at Location',
            self::STATUS_START_RIDE => 'Start Ride',
            self::STATUS_RIDE_COMPLETED => 'Ride Completed',
            self::STATUS_USER_CANCELLED => 'User Cancelled',
            self::STATUS_BOOKING_AUTO_CANCELLED => 'Booking Auto Cancelled',
            self::STATUS_DRIVER_CANCELLED => 'Driver Cancelled',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_ACCEPTED_RIDES => 'Accepted Rides',
        ];
    }

    public function getStateOptionsBadges()
    {
        $status = $this->status;

        if ($status == self::STATUS_WAITING_FOR_DRIVER) {
            return '<span class="badge text-bg-primary">Waiting for Driver</span>';
        } elseif ($status == self::STATUS_DRIVER_ACCEPTED) {
            return '<span class="badge text-bg-info">Driver Accepted</span>';
        } elseif ($status == self::STATUS_DRIVER_REJECTED) {
            return '<span class="badge text-bg-warning">Driver Rejected</span>';
        } elseif ($status == self::STATUS_GO_FOR_PICKUP) {
            return '<span class="badge text-bg-primary">Go for Pickup</span>';
        } elseif ($status == self::STATUS_ARRIVED_LOCATION) {
            return '<span class="badge text-bg-primary">Arrived at Location</span>';
        } elseif ($status == self::STATUS_START_RIDE) {
            return '<span class="badge text-bg-primary">Start Ride</span>';
        } elseif ($status == self::STATUS_RIDE_COMPLETED) {
            return '<span class="badge text-bg-primary">Ride Completed</span>';
        } elseif ($status == self::STATUS_USER_CANCELLED) {
            return '<span class="badge text-bg-danger">User Cancelled</span>';
        } elseif ($status == self::STATUS_BOOKING_AUTO_CANCELLED) {
            return '<span class="badge text-bg-danger">Booking Auto Cancelled</span>';
        } elseif ($status == self::STATUS_DRIVER_CANCELLED) {
            return '<span class="badge text-bg-danger">Driver Cancelled</span>';
        } elseif ($status == self::STATUS_CANCELLED) {
            return '<span class="badge text-bg-danger">Cancelled</span>';
        } elseif ($status == self::STATUS_ACCEPTED_RIDES) {
            return '<span class="badge text-bg-primary">Accepted Rides</span>';
        }

        return ''; // Return an empty string if the status is not recognized
    }

    public function getFeatureOptions()
    {
        return [
            self::IS_FEATURED => 'Is Featured',
            self::IS_NOT_FEATURED => 'Not Featured',
        ];
    }

    public function getFeatureOptionsBadges()
    {
        if ($this->is_featured == self::IS_FEATURED) {
            return '<span class="badge text-bg-success">Featured</span>';
        } elseif ($this->is_featured == self::IS_NOT_FEATURED) {
            return '<span class="badge text-bg-danger">Not Featured</span>';
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'driver_id' => Yii::t('app', 'Driver ID'),
            'booking_id' => Yii::t('app', 'Booking ID'),
            'status' => Yii::t('app', 'Status'),
            'created_on' => Yii::t('app', 'Created On'),
            'updated_on' => Yii::t('app', 'Updated On'),
            'create_user_id' => Yii::t('app', 'Create User ID'),
            'update_user_id' => Yii::t('app', 'Update User ID'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBooking()
    {
        return $this->hasOne(\app\modules\admin\models\Booking::className(), [
            'id' => 'booking_id',
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDriver()
    {
        return $this->hasOne(\app\modules\admin\models\User::className(), [
            'id' => 'driver_id',
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreateUser()
    {
        return $this->hasOne(\app\modules\admin\models\User::className(), [
            'id' => 'create_user_id',
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdateUser()
    {
        return $this->hasOne(\app\modules\admin\models\User::className(), [
            'id' => 'update_user_id',
        ]);
    }

    /**
     * @inheritdoc
     * @return array mixed
     */
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'updated_on',
                'updatedAtAttribute' => 'created_on',
                'value' => date('Y-m-d H:i:s'),
            ],
            'blameable' => [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'create_user_id',
                'updatedByAttribute' => 'update_user_id',
            ],
        ];
    }

    /**
     * @inheritdoc
     * @return \app\modules\admin\models\DriverRequestsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\admin\models\DriverRequestsQuery(
            get_called_class()
        );
    }
    public function asJson()
    {
        $data = [];
        $data['id'] = $this->id;

        $data['driver_id'] = $this->driver_id;

        $data['booking_id'] = $this->booking_id;

        $data['status'] = $this->status;

        $data['created_on'] = $this->created_on;

        $data['updated_on'] = $this->updated_on;

        $data['create_user_id'] = $this->create_user_id;

        $data['update_user_id'] = $this->update_user_id;

        return $data;
    }
}
