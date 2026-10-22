<?php


namespace app\modules\admin\models\base;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

/**
 * This is the base model class for table "ride_earnings".
 *
 * @property integer $id
 * @property integer $ride_id
 * @property integer $driver_id
 * @property double $total_ditance_km
 * @property double $admin_earning
 * @property double $driver_earning
 * @property integer $status
 * @property string $created_on
 * @property string $updated_on
 * @property integer $create_user_id
 * @property integer $update_user_id
 *
 * @property \app\modules\admin\models\RideRequest $ride
 * @property \app\modules\admin\models\User $driver
 */
class RideEarnings extends \yii\db\ActiveRecord
{
    use \mootensai\relation\RelationTrait;


    /**
     * This function helps \mootensai\relation\RelationTrait runs faster
     * @return array relation names of this model
     */
    public function relationNames()
    {
        return [
            'ride',
            'driver' 
        ];
    }

    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_CANCELLED = 2;

    const IS_FEATURED = 1;
    const IS_NOT_FEATURED = 0;

    const MANUL = 1; 
    const AUTO = 2;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['ride_id', 'driver_id', 'total_ditance_km', 'admin_earning', 'driver_earning', 'status', 'created_on', 'updated_on', 'update_user_id'], 'required'],
            [['ride_id', 'driver_id', 'status', 'create_user_id', 'update_user_id'], 'integer'],
            [['total_ditance_km', 'admin_earning', 'driver_earning'], 'number'],
            [['created_on', 'updated_on','method_reason','refrence_id'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ride_earnings';
    }

    public function getStateOptions()
    {
        return [

            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_CANCELLED => 'Cancelled',

        ];
    }
    public function getStateOptionsBadges()
    {

        if ($this->status == self::STATUS_PENDING) {
            return '<span class="badge bg-warning">Pending</span>';
        } elseif ($this->status == self::STATUS_APPROVED) {
            return '<span class="badge bg-success">Approved</span>';
        } elseif ($this->status == self::STATUS_CANCELLED) {
            return '<span class="badge bg-danger">Cancelled</span>';
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

    
    public function getTypeOptions()
    {
        return [

            self::MANUL => 'Manual',
            self::AUTO => 'Auto',

        ];
    }

    

    public function getTypeOptionsBadges()
    {
        if ($this->type == self::MANUL) {
            return '<span class="badge text-bg-success">Manual</span>';
        } elseif ($this->type == self::AUTO) {
            return '<span class="badge text-bg-danger">Auto</span>';
        }
    }
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'ride_id' => Yii::t('app', 'Ride ID'),
            'driver_id' => Yii::t('app', 'Driver ID'),
            'total_ditance_km' => Yii::t('app', 'Total Distance Km'),
            'admin_earning' => Yii::t('app', 'Admin Earning'),
            'driver_earning' => Yii::t('app', 'Driver Earning'),
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
    public function getRide()
    {
        return $this->hasOne(\app\modules\admin\models\RideRequest::className(), ['id' => 'ride_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDriver()
    {
        return $this->hasOne(\app\models\User::className(), ['id' => 'driver_id']);
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
                'createdAtAttribute' => 'created_on',
                'updatedAtAttribute' => 'updated_on',
                'value' => new \yii\db\Expression('NOW()'),
            ],
            'blameable' => [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'create_user_id',
                'updatedByAttribute' => 'update_user_id',
            ],
        ];
    }

    public static function getTotal($provider=[], $fieldName='')
    {
        $total = 0;
        foreach ($provider as $item) {
          $total += $item[$fieldName];
      }
      return $total;  
    }

    /**
     * @inheritdoc
     * @return \app\modules\admin\models\RideEarningsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\admin\models\RideEarningsQuery(get_called_class());
    }
    public function asJson()
    {
        $data = [];
        $data['id'] =  $this->id;

        $data['ride']['id'] =  $this->ride_id;
        $data['ride']['pickup_address'] =  !empty($this->ride->pickup_address)?$this->ride->pickup_address:'';
        $data['ride']['drop_address'] =  !empty($this->ride->drop_address)?$this->ride->drop_address:'';
        $data['ride']['payment_method'] =  !empty($this->ride->payment_method)?$this->ride->payment_method:'';
        $data['driver_id'] =  !empty($this->driver_id)?$this->driver_id:'';

        $data['total_ditance_km'] =  $this->total_ditance_km;


        $data['driver_earning'] =  round($this->driver_earning,2);

        $data['status'] =  $this->status;


        $data['updated_on'] =  date('h:i a', strtotime($this->updated_on));



        return $data;
    }
 
    // available Amount 

    public function availableAmount($id, $start_date="", $end_date="")
    {

        if ((!empty($start_date && $end_date)) || ($start_date != "" && $end_date != "")) {
            $totalEarning =  RideEarnings::find()
                ->andWhere(['driver_id' => $id])
                ->andWhere(['status' => RideEarnings::STATUS_APPROVED])->andWhere(['between', 'updated_on', $start_date, $end_date])->andWhere(['!=', 'payment_method', RideRequest::PAYMENT_METHOD_CASH])
                ->sum('driver_earning');

            $payOuts =  TaxiPayout::find()
                ->andWhere(['driver_id' => $id])
                ->andWhere(
                    [
                        'status' => [TaxiPayout::STATUS_APPROVED, TaxiPayout::STATUS_PROCESSING, TaxiPayout::STATUS_PROCESSING],
                    ]
                )->andWhere(['between', 'updated_on', $start_date, $end_date])
                ->sum('amount');
        } else {
            $totalEarning =  RideEarnings::find()
                ->andWhere(['driver_id' => $id])
                ->andWhere(['status' => RideEarnings::STATUS_APPROVED])->andWhere(['!=', 'payment_method', RideRequest::PAYMENT_METHOD_CASH])
                ->sum('driver_earning');

            $payOuts =  TaxiPayout::find()
                ->andWhere(['driver_id' => $id])
                ->andWhere(['payment_type'=>TaxiPayout::PAYMENT_TYPE_DEBIT])
                ->andWhere(
                    [
                        'status' => [TaxiPayout::STATUS_APPROVED, TaxiPayout::STATUS_PROCESSING, TaxiPayout::STATUS_PROCESSING],
                    ]
                )
                ->sum('amount');
        }

        $availableAmount = $totalEarning - $payOuts;

        return round($availableAmount, 2);
    }

    
}
 