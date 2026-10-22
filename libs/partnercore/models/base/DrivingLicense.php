<?php


namespace app\modules\admin\models\base;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
/**
 * This is the base model class for table "driving_license".
 *
 * @property integer $id
 * @property integer $driver_id
 * @property string $driving_license_front
 * @property string $driving_license_back
 * @property string $driving_license_number
 * @property string $issued_date
 * @property string $expire_date
 * @property integer $status
 * @property integer $update_user_id
 * @property integer $create_user_id
 * @property string $updated_on
 * @property string $created_on
 *
 * @property \app\modules\admin\models\User $driver
 * @property \app\modules\admin\models\User $createUser
 * @property \app\modules\admin\models\User $updateUser
 */
class DrivingLicense extends \yii\db\ActiveRecord
{
    use \mootensai\relation\RelationTrait;


    /**
    * This function helps \mootensai\relation\RelationTrait runs faster
    * @return array relation names of this model
    */
    public function relationNames()
    {
        return [
            'driver',
            'createUser',
            'updateUser'
        ];
    }

    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_DELETE = 2;

    const IS_FEATURED = 1;
    const IS_NOT_FEATURED = 0;
 
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['driver_id', 'driving_license_front', 'driving_license_back', 'driving_license_number', 'issued_date', 'expire_date'], 'required'],
            [['driver_id', 'status', 'update_user_id', 'create_user_id'], 'integer'],
            [['issued_date', 'expire_date', 'updated_on', 'created_on'], 'safe'],
             [['driving_license_front', 'driving_license_back'], 'file', 'skipOnEmpty' => false, 'extensions' => 'png, jpg, jpeg'],
            [['driving_license_front', 'driving_license_back'], 'validateFileSize'],
            [[ 'driving_license_number'], 'string', 'max' => 255]
            
        ];
    }

  

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'driving_license';
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
        }elseif ($this->status == self::STATUS_DELETE) {
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
            'driver_id' => Yii::t('app', 'Driver ID'),
            'driving_license_front' => Yii::t('app', 'Driving License Front'),
            'driving_license_back' => Yii::t('app', 'Driving License Back'),
            'driving_license_number' => Yii::t('app', 'Driving License Number'),
            'issued_date' => Yii::t('app', 'Issued Date'),
            'expire_date' => Yii::t('app', 'Expire Date'),
            'status' => Yii::t('app', 'Status'),
            'update_user_id' => Yii::t('app', 'Update User ID'),
            'create_user_id' => Yii::t('app', 'Create User ID'),
            'updated_on' => Yii::t('app', 'Updated On'),
            'created_on' => Yii::t('app', 'Created On'),
        ];
    }
    
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDriver()
    {
        return $this->hasOne(\app\modules\admin\models\User::className(), ['id' => 'driver_id']);
    }
        
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreateUser()
    {
        return $this->hasOne(\app\modules\admin\models\User::className(), ['id' => 'create_user_id']);
    }
        
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdateUser()
    {
        return $this->hasOne(\app\modules\admin\models\User::className(), ['id' => 'update_user_id']);
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



    /**
     * @inheritdoc
     * @return \app\modules\admin\models\DrivingLicenseQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\modules\admin\models\DrivingLicenseQuery(get_called_class());
    }
public function asJson(){
    $data = [] ; 
            $data['id'] =  $this->id;
        
                $data['driver_id'] =  $this->driver_id;
        
                $data['driving_license_front'] =  $this->driving_license_front;
        
                $data['driving_license_back'] =  $this->driving_license_back;
        
                $data['driving_license_number'] =  $this->driving_license_number;
        
                $data['issued_date'] =  $this->issued_date;
        
                $data['expire_date'] =  $this->expire_date;
        
                $data['status'] =  $this->status;
        
                $data['update_user_id'] =  $this->update_user_id;
        
                $data['create_user_id'] =  $this->create_user_id;
        
                $data['updated_on'] =  $this->updated_on;
        
                $data['created_on'] =  $this->created_on;
        
            return $data;
}


public function validateFileSize($attribute, $params)
    {
        $maxSize = 2 * 1024 * 1024; // 2 MiB

        $file = $this->$attribute;

        if ($file instanceof UploadedFile && $file->size > $maxSize) {
            $this->addError($attribute, 'File size cannot exceed 2 MiB.');
        }
    }



}


