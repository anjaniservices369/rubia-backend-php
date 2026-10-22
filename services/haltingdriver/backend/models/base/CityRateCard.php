<?php

namespace app\modules\admin\models\base;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
/**
 * This is the base model class for table "city_rate_card".
 *
 * @property integer $id
 * @property integer $city_id
 * @property integer $trip_type
 * @property integer $ride_type
 * @property integer $base_hour
 * @property integer $base_km
 * @property integer $base_fare
 * @property integer $extra_km_charge
 * @property integer $extra_min_charge
 * @property integer $night_charge
 * @property integer $out_station_charge
 * @property double $facilities
 * @property integer $status
 * @property string $created_on
 * @property string $updated_on
 * @property integer $create_user_id
 * @property integer $update_user_id
 *
 * @property \app\modules\admin\models\City $city
 * @property \app\modules\admin\models\User $createUser
 * @property \app\modules\admin\models\User $updateUser
 */
class CityRateCard extends \yii\db\ActiveRecord
{
    use \mootensai\relation\RelationTrait;

    /**
     * This function helps \mootensai\relation\RelationTrait runs faster
     * @return array relation names of this model
     */
    public function relationNames()
    {
        return ['city', 'createUser', 'updateUser'];
    }

    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_DELETE = 2;

    const IS_FEATURED = 1;
    const IS_NOT_FEATURED = 0;

    const RIDER_TYPE_ONEWAY = 1;
    const RIDER_TYPE_ROUNDTRIP = 2;

    const TRIP_TYPE_INTERCITY = 1;
    const TRIP_TYPE_OUTSTATION = 2;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                [
                    'city_id',
                    'trip_type',
                    'ride_type',
                    'base_hour',

                    'base_fare',

                    'extra_min_charge',
                    'night_charge',
                    'out_station_charge',
                ],
                'required',
            ],
            [
                [
                    'city_id',
                    'trip_type',
                    'ride_type',
                    'base_hour',
                    'base_km',
                    'base_fare',
                    'extra_km_charge',
                    'extra_min_charge',
                    'night_charge',
                    'out_station_charge',
                    'status',
                    'create_user_id',
                    'update_user_id',
                ],
                'integer',
            ],
            [['facilities'], 'number'],
            [['created_on', 'updated_on'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'city_rate_card';
    }
    public function geRideType()
    {
        return [
            self::RIDER_TYPE_ONEWAY => 'One Way',
            self::RIDER_TYPE_ROUNDTRIP => 'Round Trip',
        ];
    }

    public function getTripType()
    {
        return [
            self::TRIP_TYPE_INTERCITY => 'Inner City',
            self::TRIP_TYPE_OUTSTATION => 'Out Station',
        ];
    }

    public function getRideTypeBadges()
    {
        if ($this->ride_type == self::RIDER_TYPE_ONEWAY) {
            return '<span class="badge text-bg-success">One Way</span>';
        } elseif ($this->ride_type == self::RIDER_TYPE_ROUNDTRIP) {
            return '<span class="badge text-bg-danger">Round Trip</span>';
        }
    }

    public function getTripTypeBadges()
    {
        if ($this->trip_type == self::TRIP_TYPE_INTERCITY) {
            return '<span class="badge text-bg-success">Inner City</span>';
        } elseif ($this->trip_type == self::TRIP_TYPE_OUTSTATION) {
            return '<span class="badge text-bg-danger">Out Station</span>';
        }
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

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'city_id' => Yii::t('app', 'City ID'),
            'trip_type' => Yii::t('app', 'Trip Type'),
            'ride_type' => Yii::t('app', 'Ride Type'),
            'base_hour' => Yii::t('app', 'Base Hour'),
            'base_km' => Yii::t('app', 'Base Km'),
            'base_fare' => Yii::t('app', 'Base Fare'),
            'extra_km_charge' => Yii::t('app', 'Extra Km Charge'),
            'extra_min_charge' => Yii::t('app', 'Extra Min Charge'),
            'night_charge' => Yii::t('app', 'Night Charge'),
            'out_station_charge' => Yii::t('app', 'Out Station Charge'),
            'facilities' => Yii::t('app', 'Facilities'),
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
    public function getCity()
    {
        return $this->hasOne(\app\modules\admin\models\City::className(), [
            'id' => 'city_id',
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
     * @return \app\modules\admin\models\CityRateCardQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\admin\models\CityRateCardQuery(
            get_called_class()
        );
    }
    public function asJson()
    {
        $data = [];
        $data['id'] = $this->id;

        $data['city_id'] = $this->city_id;

        $data['trip_type'] = $this->trip_type;

        $data['ride_type'] = $this->ride_type;

        $data['base_hour'] = $this->base_hour;

        $data['base_km'] = $this->base_km;

        $data['base_fare'] = $this->base_fare;

        $data['extra_km_charge'] = $this->extra_km_charge;

        $data['extra_min_charge'] = $this->extra_min_charge;

        $data['night_charge'] = $this->night_charge;

        $data['out_station_charge'] = $this->out_station_charge;

        $data['facilities'] = $this->facilities;

        $data['status'] = $this->status;

        $data['created_on'] = $this->created_on;

        $data['updated_on'] = $this->updated_on;

        $data['create_user_id'] = $this->create_user_id;

        $data['update_user_id'] = $this->update_user_id;

        return $data;
    }
}
