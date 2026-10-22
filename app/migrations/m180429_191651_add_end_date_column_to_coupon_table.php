<?php

use yii\db\Migration;

/**
 * Handles adding end_date to table `coupon`.
 */
class m180429_191651_add_end_date_column_to_coupon_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('coupon', 'end_date', $this->datetime());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('coupon', 'end_date');
    }
}
