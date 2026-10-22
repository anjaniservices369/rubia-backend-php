<?php

use yii\db\Schema;

class m190130_050101_notification extends \yii\db\Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }
        
        $this->createTable('notification', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'module' => $this->string(255)->notNull(),
            'icon' => $this->string(20)->notNull(),
            'order_id' => $this->integer(14),
            'created_user_id' => $this->integer(11)->notNull(),
            'created_date' => $this->datetime()->notNull(),
            'mark_read' => $this->tinyint(1)->notNull()->defaultValue(0),
            'status' => $this->tinyint(1)->notNull()->defaultValue(1),
            'model_type' => $this->string(255),
            'check_on_ajax' => $this->integer(11)->notNull()->defaultValue(0),
            ], $tableOptions);
                
    }

    public function down()
    {
        $this->dropTable('notification');
    }
}
