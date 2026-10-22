<?php

use yii\db\Schema;

class m190716_120101_DeliveryBoyreview extends \yii\db\Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }
        
        $this->createTable('{{%deliveryboy_review}}', [
            'id' => $this->primaryKey(),
            'order_id' => $this->integer(11)->notNull(),
            'rest_id' => $this->integer(11)->notNull(),
            'delivery_boy_id' => $this->integer(11)->notNull(),
            'created_user_id' => $this->integer(11)->notNull(),
            'comment' => $this->text()->notNull(),
            'rating' => $this->integer(11)->notNull(),
            'created_date' => $this->date()->notNull(),
            'status' => $this->integer(11)->notNull(),
            'FOREIGN KEY ([[delivery_boy_id]]) REFERENCES {{%user}} ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
            'FOREIGN KEY ([[order_id]]) REFERENCES {{%orders}} ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
            'FOREIGN KEY ([[rest_id]]) REFERENCES {{%restaurant}} ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
            ], $tableOptions);
                
    }

    public function safeDown()
    {
        $this->execute('SET foreign_key_checks = 0');
        $this->dropTable('{{%deliveryboy_review}}');
        $this->execute('SET foreign_key_checks = 1');
    }
}
