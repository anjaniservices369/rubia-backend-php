<?php

namespace app\modules\admin\forms;

use yii\base\Model;

class BankDetailsForm extends Model
{
    public $account_holder_name;
    public $account_number;
    public $ifsc;
    public $bank_name;
    public $upi;
    public $branch_name;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['account_holder_name', 'account_number', 'ifsc', 'bank_name', 'upi'], 'required'],
            [['branch_name'], 'string', 'max' => 255],
            [['branch_name'], 'safe'],
            // Add additional validation rules if needed
        ];
    }
}
