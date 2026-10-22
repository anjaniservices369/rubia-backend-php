<?php

namespace app\modules\admin\models\base;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
/**
 * This is the base model class for table "vehicle_verification".
 *
 * @property integer $id
 * @property integer $booking_id
 * @property string $image
 * @property integer $image_type
 * @property integer $status
 * @property integer $create_user_id
 * @property integer $update_user_id
 * @property string $created_on
 * @property string $updated_on
 *
 * @property \app\modules\admin\models\Booking $booking
 * @property \app\modules\admin\models\User $createUser
 * @property \app\modules\admin\models\User $updateUser
 */
class VehicleVerification extends \yii\db\ActiveRecord
{
    use \mootensai\relation\RelationTrait;

    /**
     * This function helps \mootensai\relation\RelationTrait runs faster
     * @return array relation names of this model
     */
    public function relationNames()
    {
        return ['booking', 'createUser', 'updateUser'];
    }

    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_DELETE = 2;

    const IS_FEATURED = 1;
    const IS_NOT_FEATURED = 0;

    const image_type_front = 1;
    const image_type_back = 2;
    const image_type_side_one = 3;
    const image_type_side_two = 4;
    const image_type_damages = 5;
    const image_type_selfie = 6;

    const type_before_ride = 1;
    const type_after_ride = 2;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['booking_id', 'image', 'image_type'], 'required'],
            [
                [
                    'booking_id',
                    'image_type',
                    'status',
                    'create_user_id',
                    'update_user_id',
                ],
                'integer',
            ],
            [['created_on', 'updated_on'], 'safe'],
            [['image'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'vehicle_verification';
    }

    public function getStateOptions()
    {
        return [
            self::STATUS_INACTIVE => 'In Active',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_DELETE => 'Deleted',
        ];
    }
    public function getStateOptionsBadges()
    {
        if ($this->status == self::STATUS_ACTIVE) {
            return '<span class="badge text-bg-success">Active</span>';
        } elseif ($this->status == self::STATUS_INACTIVE) {
            return '<span class="badge text-bg-secondary">In Active</span>';
        } elseif ($this->status == self::STATUS_DELETE) {
            return '<span class="badge text-bg-danger">Deleted</span>';
        }
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

    public function getImageType()
    {
        return [
            self::image_type_front => 'Front Image',
            self::image_type_back => 'Back Image',
            self::image_type_side_one => 'Side One Image',
            self::image_type_side_two => 'Side Two Image',
            self::image_type_damages => 'Damages Image',
            self::image_type_selfie => 'Selfie Image',
        ];
    }

    public function getImageTypeBadges()
    {
        $imageType = $this->image_type;

        if ($imageType == self::image_type_front) {
            return '<span class="badge text-bg-primary">Front Image</span>';
        } elseif ($imageType == self::image_type_back) {
            return '<span class="badge text-bg-secondary">Back Image</span>';
        } elseif ($imageType == self::image_type_side_one) {
            return '<span class="badge text-bg-info">Side One Image</span>';
        } elseif ($imageType == self::image_type_side_two) {
            return '<span class="badge text-bg-warning">Side Two Image</span>';
        } elseif ($imageType == self::image_type_damages) {
            return '<span class="badge text-bg-danger">Damages Image</span>';
        } elseif ($imageType == self::image_type_selfie) {
            return '<span class="badge text-bg-success">Selfie Image</span>';
        }
    }

    public function getRideType()
    {
        $rideTypes = [
            self::type_before_ride => 'Before Ride',
            self::type_after_ride => 'After Ride',
        ];

        return $rideTypes;
    }

    public function getRideTypeBadges()
    {
        $rideType = $this->type;

        if ($rideType == self::type_before_ride) {
            return '<span class="badge text-bg-primary">Before Ride</span>';
        } elseif ($rideType == self::type_after_ride) {
            return '<span class="badge text-bg-success">After Ride</span>';
        }

        return ''; // Return an empty string if the ride type is not recognized
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'booking_id' => Yii::t('app', 'Booking ID'),
            'image' => Yii::t('app', 'Image'),
            'image_type' => Yii::t('app', 'Image Type'),
            'status' => Yii::t('app', 'Status'),
            'create_user_id' => Yii::t('app', 'Create User ID'),
            'update_user_id' => Yii::t('app', 'Update User ID'),
            'created_on' => Yii::t('app', 'Created On'),
            'updated_on' => Yii::t('app', 'Updated On'),
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
     * @return \app\modules\admin\models\VehicleVerificationQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\admin\models\VehicleVerificationQuery(
            get_called_class()
        );
    }
    public function asJson()
    {
        $data = [];
        $data['id'] = $this->id;

        $data['booking_id'] = $this->booking_id;

        $data['image'] = $this->image;

        $data['image_type'] = $this->image_type;

        $data['status'] = $this->status;

        $data['create_user_id'] = $this->create_user_id;

        $data['update_user_id'] = $this->update_user_id;

        $data['created_on'] = $this->created_on;

        $data['updated_on'] = $this->updated_on;

        return $data;
    }
}
