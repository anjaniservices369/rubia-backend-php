<?php

use yii\db\Schema;

class m190117_210101_order_history extends \yii\db\Migration
{
    public function safeUp()
    {
        $tables = Yii::$app->db->schema->getTableNames();
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }
        
        if (!in_array(Yii::$app->db->tablePrefix.'order_history', $tables))  {
          $this->createTable('order_history', [
              'id' => $this->primaryKey(),
              'order_id' => $this->integer(14)->notNull()->defaultValue(0),
              'status' => $this->string(255)->notNull()->defaultValue(''),
              'remarks' => $this->text(),
              'created_on' => $this->datetime()->notNull()->defaultValue('0000-00-00 00:00:00'),
              'ip_address' => $this->string(50)->notNull()->defaultValue(''),
              'task_id' => $this->integer(14)->notNull()->defaultValue(0),
              'reason' => $this->text(),
              'customer_signature' => $this->string(255)->notNull()->defaultValue(''),
              'notification_viewed' => $this->integer(1)->notNull()->defaultValue(2),
              'driver_id' => $this->integer(14)->notNull()->defaultValue(0),
              'driver_location_lat' => $this->string(50)->notNull()->defaultValue(''),
              'driver_location_lng' => $this->string(50)->notNull()->defaultValue(''),
              'remarks2' => $this->string(255)->notNull()->defaultValue(''),
              'remarks_args' => $this->string(255)->notNull()->defaultValue(''),
              'notes' => $this->string(255)->notNull()->defaultValue(''),
              'photo_task_id' => $this->integer(14)->notNull()->defaultValue(0),
              'receive_by' => $this->string(255)->notNull()->defaultValue(''),
              'signature_base30' => $this->text(),
              'FOREIGN KEY ([[driver_id]]) REFERENCES user ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
              'FOREIGN KEY ([[order_id]]) REFERENCES order ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
              ], $tableOptions);
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."order_history` already exists!\n";
        }
                 
    }

    public function safeDown()
    {
        $this->dropTable('order_history');
    }
}
