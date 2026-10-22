<?php
use yii\db\Migration;

/**
 * Handles the creation of table `whishlist`.
 */
class m180526_174633_create_whishlist_table extends Migration {
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function safeUp() {
		$this->createTable ( 'wishlist', [ 
				'id' => $this->primaryKey (),
				'product_id' => $this->integer ( 11 )->null (),
				'type_id' => $this->integer ( 11 )->defaultValue ( 0 ),
				'state_id' => $this->integer ( 11 )->defaultValue ( 1 ),
				'created_on' => $this->dateTime ()->null (),
				'update_on' => $this->dateTime ()->null (),
				'create_user_id' => $this->integer ()->null () 
		
		] );
		
		$this->createIndex ( 'fk_wishlist_product_id', 'wishlist', 'product_id' );
		$this->addForeignKey('fk_wishlist_product_id', 'wishlist', 'product_id', 'product', 'id', 'CASCADE');
		
		$this->createIndex ( 'fk_wishlist_created_by', 'wishlist', 'create_user_id' );
		$this->addForeignKey('fk_wishlist_created_by', 'wishlist', 'create_user_id', 'user', 'id', 'CASCADE');
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 */
	public function safeDown() {
		$this->dropTable ( 'wishlist' );
	}
}
