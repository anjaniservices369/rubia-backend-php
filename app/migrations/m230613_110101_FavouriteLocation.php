<?php

use yii\db\Schema;

class m230613_110101_FavouriteLocation extends \yii\db\Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }
        
        $this->createTable('favourite_location', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(11),
            'latitude' => $this->string(255),
            'logitude' => $this->string(255),
            'address_type' => $this->string(255),
            'address' => $this->string(255),
            'pincode' => $this->integer(11),
            'status' => $this->integer(11)->notNull()->defaultValue(1),
            'created_on' => $this->datetime(),
            'updated_on' => $this->datetime(),
            'update_user_id' => $this->integer(11),
            'create_user_id' => $this->integer(11),
            ], $tableOptions);
                
    }

    public function safeDown()
    {
        $this->dropTable('favourite_location');
    }
}
