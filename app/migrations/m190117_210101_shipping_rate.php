<?php

use yii\db\Schema;

class m190117_210101_shipping_rate extends \yii\db\Migration
{
    public function safeUp()
    {
        $tables = Yii::$app->db->schema->getTableNames();
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }
        
        if (!in_array(Yii::$app->db->tablePrefix.'shipping_rate', $tables))  {
          $this->createTable('shipping_rate', [
              'id' => $this->primaryKey(),
              'restaurant_id' => $this->integer(11)->notNull(),
              'distance_from' => $this->integer(14)->notNull()->defaultValue(0),
              'distance_to' => $this->integer(14)->notNull()->defaultValue(0),
              'shipping_units' => $this->string(5)->notNull()->defaultValue(''),
              'distance_price' => $this->float(14,4)->notNull()->defaultValue(0),
              'FOREIGN KEY ([[restaurant_id]]) REFERENCES restaurant ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
              ], $tableOptions);
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."shipping_rate` already exists!\n";
        }
                 
    }

    public function safeDown()
    {
        $this->dropTable('shipping_rate');
    }
}
