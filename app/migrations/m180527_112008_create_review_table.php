<?php

use yii\db\Migration;

/**
 * Handles the creation of table `review`.
 */
class m180527_112008_create_review_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('review', [
            'id' => $this->primaryKey(),
        	'product_id'=>$this->integer(11),
        	'name'=>$this->string(255)->null(),
        	'email'=>$this->string(255)->null(),
        	'comments'=>$this->text()->null(),
        	'ratings'=>$this->float()->null(),
        	'created_on'=>$this->dateTime()->null(),
        	'update_on'=>$this->dateTime()->null()	
        		
        		
        ]);
        
        $this->createIndex('fk_review_product_id', 'review', 'product_id');
        $this->addForeignKey('fk_review_product_id', 'review', 'product_id', 'product', 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('review');
    }
}
