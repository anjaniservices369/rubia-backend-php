<?php

use yii\db\Migration;
use yii\db\Schema;
;

/**
 * Handles the creation of table `coupons_applied`.
 */
class m180429_172439_create_coupons_applied_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('coupons_applied', [
            'id' => $this->primaryKey(),
            'order_id'=>$this->integer(11)->null(),
            'coupon_id'=>$this->integer(11)->null(),
            'state_id'=>$this->integer(11)->defaultValue(1),
            'type_id'=>$this->integer(11)->defaultValue(0),
            'created_on'=>$this->dateTime(),
            'update_on'=>$this->dateTime(),
            'create_user_id'=>$this->integer(11)->null(),
        ]);

        $this->createIndex(
            'idx-coupons-applied-coupon_id',
            'coupons_applied',
            'coupon_id'
        );

        $this->createIndex(
            'idx-coupons-applied-create_user_id',
            'coupons_applied',
            'create_user_id'
        );
        $this->addForeignKey(
            'fk-post_tag-coupon_id',
            'coupons_applied',
            'coupon_id',
            'coupon',
            'id'
        );

        $this->addForeignKey(
            'fk-post_tag-create_user_id',
            'coupons_applied',
            'create_user_id',
            'user',
            'id'
        );


    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('coupons_applied');
    }
}
