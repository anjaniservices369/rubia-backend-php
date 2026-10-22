<?php

use yii\db\Migration;

/**
 * Handles adding start_date to table `coupon`.
 */
class m180429_191351_add_start_date_column_to_coupon_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('coupon', 'start_date', $this->datetime()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('coupon', 'start_date');
    }
}
