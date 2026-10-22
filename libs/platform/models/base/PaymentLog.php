<?php

namespace app\modules\admin\models\base;

use Yii;
use yii\db\ActiveRecord as YiiActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "payment_logs".
 *
 * @property int $id
 * @property string $driver_id
 * @property string $merchantId
 * @property string $responseCode
 * @property string $transactionid
 * @property string $amount
 * @property string $status
 * @property string $response_data
 * @property string $created_date
 * @property bool $success
 * @property string $code
 * @property string $message
 * @property string $merchantTransactionId
 * @property string $paymentInstrument_type
 * @property string $paymentInstrument_pgTransactionId
 * @property string $paymentInstrument_pgServiceTransactionId
 * @property string $paymentInstrument_bankTransactionId
 * @property string $paymentInstrument_bankId
 
 */
class PaymentLog extends ActiveRecord
{
    use \mootensai\relation\RelationTrait;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'payment_logs';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['driver_id', 'merchantId', 'transactionid', 'amount', 'status', 'responseCode', 'success', 'code', 'message', 'merchantTransactionId', 'paymentInstrument_type', 'paymentInstrument_pgTransactionId', 'paymentInstrument_pgServiceTransactionId', 'paymentInstrument_bankTransactionId', 'paymentInstrument_bankId'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'driver_id' => 'driver_id',
            'responseCode' => 'responseCode',
            'merchantId' => 'merchantId',
            'transactionid' => 'Transactionid',
            'amount' => 'Amount',
            'status' => 'Status',
           // 'response_data' => 'Response Data',
            'created_date' => 'Created Date',
            'success' => 'Success',
            'code' => 'Code',
            'message' => 'Message',
            'merchantTransactionId' => 'Merchant Transaction ID',
            'paymentInstrument_type' => 'Payment Instrument Type',
            'paymentInstrument_pgTransactionId' => 'Payment Instrument Pg Transaction ID',
            'paymentInstrument_pgServiceTransactionId' => 'Payment Instrument Pg Service Transaction ID',
            'paymentInstrument_bankTransactionId' => 'Payment Instrument Bank Transaction ID',
            'paymentInstrument_bankId' => 'Payment Instrument Bank ID',
        ];
    }
}
