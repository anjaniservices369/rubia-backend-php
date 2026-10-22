<?php

use yii\db\Schema;

class m190130_050101_updated extends \yii\db\Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }
        
        $this->createTable('user', [
            'id' => $this->primaryKey(),
            'username' => $this->string(32)->notNull(),
            'full_name' => $this->string(32)->notNull(),
            'contact_no' => $this->string(32),
            'profile_image' => $this->string(32),
            'address' => $this->string(255),
            'latitude' => $this->string(255),
            'longitude' => $this->string(255),
            'auth_key' => $this->string(32),
            'access_token' => $this->string(40),
            'password' => $this->string(255)->notNull(),
            'oauth_client' => $this->string(255),
            'oauth_client_user_id' => $this->string(255),
            'email' => $this->string(255)->notNull(),
            'status' => $this->smallInteger(6)->notNull()->defaultValue(2),
            'created_on' => $this->datetime(),
            'updated_on' => $this->datetime(),
            'last_login' => $this->datetime(),
            'logged_at' => $this->integer(11),
            'state_id' => $this->integer(11)->defaultValue(0),
            'role_id' => $this->integer(11)->notNull()->defaultValue(0),
            'type_id' => $this->integer(11)->defaultValue(0),
            'create_user_id' => $this->integer(11)->defaultValue(0),
            ], $tableOptions);
                $this->createTable('addon_types', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'slug' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'state_id' => $this->integer(11)->defaultValue(0),
            'type_id' => $this->integer(11)->defaultValue(0),
            'amount' => $this->string(255)->defaultValue('0'),
            'menu_id' => $this->string(255)->defaultValue('0'),
            'created_on' => $this->datetime()->notNull(),
            'updated_on' => $this->datetime(),
            'create_user_id' => $this->integer(11)->notNull(),
            'FOREIGN KEY ([[create_user_id]]) REFERENCES user ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
            ], $tableOptions);
                $this->createTable('auth', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(11)->notNull(),
            'source' => $this->string(255)->notNull(),
            'source_id' => $this->string(255)->notNull(),
            'FOREIGN KEY ([[user_id]]) REFERENCES user ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
            ], $tableOptions);
                $this->createTable('auth_session', [
            'id' => $this->primaryKey(),
            'auth_code' => $this->string(256)->notNull(),
            'device_token' => $this->string(256)->notNull(),
            'type_id' => $this->integer(11)->defaultValue(0),
            'created_on' => $this->datetime()->notNull(),
            'updated_on' => $this->datetime(),
            'create_user_id' => $this->integer(11)->notNull(),
            'FOREIGN KEY ([[create_user_id]]) REFERENCES user ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
            ], $tableOptions);
                $this->createTable('banner', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'slug' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'state_id' => $this->integer(11)->defaultValue(0),
            'type_id' => $this->integer(11)->defaultValue(0),
            'created_on' => $this->datetime()->notNull(),
            'updated_on' => $this->datetime(),
            'create_user_id' => $this->integer(11)->notNull(),
            'lat' => $this->string(255),
            'lng' => $this->string(255),
            'FOREIGN KEY ([[create_user_id]]) REFERENCES user ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
            ], $tableOptions);
                $this->createTable('brand', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'slug' => $this->string(512)->notNull(),
            'state_id' => $this->integer(11)->defaultValue(0),
            'type_id' => $this->integer(11)->defaultValue(0),
            'created_on' => $this->datetime(),
            'updated_on' => $this->datetime(),
            'create_user_id' => $this->integer(11)->notNull(),
            'FOREIGN KEY ([[create_user_id]]) REFERENCES user ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
            ], $tableOptions);
                $this->createTable('cart', [
            'id' => $this->primaryKey(),
            'restaurant_id' => $this->integer(11)->notNull(),
            'amount' => $this->integer(11)->defaultValue(0),
            'quantity' => $this->integer(11),
            'detail' => $this->text(),
            'url' => $this->string(255),
            'state_id' => $this->integer(11)->defaultValue(0),
            'type_id' => $this->integer(11)->defaultValue(0),
            'created_on' => $this->datetime(),
            'updated_on' => $this->datetime(),
            'cookie_id' => $this->string(255)->defaultValue('0'),
            'create_user_id' => $this->integer(11)->defaultValue(0),
            ], $tableOptions);
                $this->createTable('cart_items', [
            'id' => $this->primaryKey(),
            'cart_id' => $this->integer(11)->notNull(),
            'menu_item_id' => $this->integer(11)->notNull(),
            'item_size_id' => $this->integer(11)->notNull(),
            'addon_id' => $this->string(255)->notNull(),
            'amount' => $this->string(255)->notNull(),
            'quantity' => $this->integer(11)->notNull(),
            'state_id' => $this->integer(11)->defaultValue(0),
            'type_id' => $this->integer(11)->defaultValue(0),
            'created_on' => $this->datetime(),
            'updated_on' => $this->datetime(),
            'create_user_id' => $this->integer(11)->defaultValue(0),
            ], $tableOptions);
                $this->createTable('category', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'slug' => $this->string(512)->notNull(),
            'description' => $this->text(),
            'state_id' => $this->integer(11)->defaultValue(0),
            'type_id' => $this->integer(11)->defaultValue(0),
            'created_on' => $this->datetime(),
            'updated_on' => $this->datetime(),
            'create_user_id' => $this->integer(11)->notNull(),
            'FOREIGN KEY ([[create_user_id]]) REFERENCES user ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
            ], $tableOptions);
                $this->createTable('catering_request', [
            'id' => $this->integer(11)->notNull(),
            'venu_type' => $this->string(255)->notNull(),
            'venu_date' => $this->datetime()->notNull(),
            'venu_time' => $this->string(255)->notNull(),
            'member_count' => $this->integer(11)->defaultValue(0),
            'restaurant_id' => $this->integer(11)->defaultValue(0),
            'state_id' => $this->integer(11)->defaultValue(0),
            'type_id' => $this->integer(11)->defaultValue(0),
            'created_on' => $this->datetime()->notNull(),
            'updated_on' => $this->datetime(),
            'create_user_id' => $this->integer(11)->notNull(),
            'PRIMARY KEY ([[id]])',
            ], $tableOptions);
                $this->createTable('comissions', [
            'id' => $this->primaryKey(),
            'rest_id' => $this->integer(11)->notNull(),
            'order_id' => $this->integer(11)->notNull(),
            'user_id' => $this->integer(11)->notNull(),
            'cart_amount' => $this->integer(11)->notNull(),
            'comission_type' => $this->integer(11)->notNull(),
            'comission_percent' => $this->integer(11)->notNull(),
            'comission_amount' => $this->integer(11)->notNull(),
            'merchant_earning' => $this->integer(11)->notNull(),
            'created_date' => $this->date()->notNull(),
            ], $tableOptions);
                $this->createTable('coupon', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'code' => $this->string(255)->notNull(),
            'discount' => $this->string(255)->notNull(),
            'max_discount' => $this->string(255)->notNull(),
            'max_use' => $this->integer(11)->defaultValue(0),
            'state_id' => $this->integer(11)->defaultValue(0),
            'type_id' => $this->integer(11)->defaultValue(0),
            'created_on' => $this->datetime(),
            'updated_on' => $this->datetime(),
            'create_user_id' => $this->integer(11)->notNull(),
            'start_date' => $this->datetime(),
            'end_date' => $this->datetime(),
            'FOREIGN KEY ([[create_user_id]]) REFERENCES user ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
            ], $tableOptions);
                $this->createTable('coupons_applied', [
            'id' => $this->primaryKey(),
            'order_id' => $this->integer(11),
            'coupon_id' => $this->integer(11),
            'state_id' => $this->integer(11)->defaultValue(1),
            'type_id' => $this->integer(11)->defaultValue(0),
            'created_on' => $this->datetime(),
            'update_on' => $this->datetime(),
            'create_user_id' => $this->integer(11),
            'FOREIGN KEY ([[coupon_id]]) REFERENCES coupon ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
            'FOREIGN KEY ([[create_user_id]]) REFERENCES user ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
            ], $tableOptions);
                $this->createTable('deal', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'slug' => $this->string(512)->notNull(),
            'description' => $this->text(),
            'state_id' => $this->integer(11)->defaultValue(0),
            'type_id' => $this->integer(11)->defaultValue(0),
            'created_on' => $this->datetime()->notNull(),
            'updated_on' => $this->datetime(),
            'create_user_id' => $this->integer(11)->notNull(),
            'FOREIGN KEY ([[create_user_id]]) REFERENCES user ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
            ], $tableOptions);
                $this->createTable('delivery_address', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(11)->notNull(),
            'address' => $this->string(255)->notNull(),
            'location' => $this->string(255)->notNull(),
            'latitude' => $this->string(255)->notNull(),
            'longitude' => $this->string(255)->notNull(),
            'address_label' => $this->string(255)->notNull(),
            'status' => $this->integer(11)->notNull(),
            'created_date' => $this->date()->notNull(),
            'FOREIGN KEY ([[user_id]]) REFERENCES user ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
            ], $tableOptions);
                $this->createTable('driver_assigned', [
            'id' => $this->primaryKey(),
            'order_id' => $this->integer(11)->notNull(),
            'driver_id' => $this->integer(11)->notNull(),
            'created_date' => $this->date()->notNull(),
            'status' => $this->integer(11)->notNull(),
            ], $tableOptions);
                $this->createTable('keyword', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'model_id' => $this->integer(11)->notNull(),
            'model_type' => $this->string(125)->notNull(),
            'state_id' => $this->integer(11)->defaultValue(0),
            'type_id' => $this->integer(11)->defaultValue(0),
            'created_on' => $this->datetime(),
            'updated_on' => $this->datetime(),
            'create_user_id' => $this->integer(11)->notNull(),
            'FOREIGN KEY ([[create_user_id]]) REFERENCES user ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
            ], $tableOptions);
                $this->createTable('media', [
            'id' => $this->primaryKey(),
            'model_id' => $this->integer(11)->notNull(),
            'model_type' => $this->string(255),
            'alt' => $this->string(512),
            'title' => $this->string(255)->notNull(),
            'size' => $this->string(255),
            'file_name' => $this->string(255),
            'thumb_file' => $this->string(255),
            'original_name' => $this->string(255),
            'extension' => $this->string(255),
            'state_id' => $this->integer(11)->defaultValue(0),
            'type_id' => $this->integer(11)->defaultValue(0),
            'created_on' => $this->datetime(),
            'updated_on' => $this->datetime(),
            'create_user_id' => $this->integer(11)->notNull(),
            ], $tableOptions);
                $this->createTable('menu', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'slug' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'state_id' => $this->integer(11)->defaultValue(0),
            'type_id' => $this->integer(11)->defaultValue(0),
            'created_on' => $this->datetime(),
            'updated_on' => $this->datetime(),
            'restaurant_id' => $this->integer(11)->notNull(),
            'create_user_id' => $this->integer(11)->notNull(),
            'category_id' => $this->integer(11),
            ], $tableOptions);
                $this->createTable('menu_item_sizes', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'amount' => $this->string(255)->notNull(),
            'state_id' => $this->integer(11)->defaultValue(0),
            'type_id' => $this->integer(11)->defaultValue(0),
            'created_on' => $this->datetime()->notNull(),
            'updated_on' => $this->datetime(),
            'menu_item_id' => $this->integer(11)->notNull(),
            'create_user_id' => $this->integer(11)->notNull(),
            ], $tableOptions);
                $this->createTable('menu_items', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'slug' => $this->string(255)->notNull(),
            'amount' => $this->string(255),
            'description' => $this->text(),
            'state_id' => $this->integer(11)->defaultValue(0),
            'type_id' => $this->integer(11)->defaultValue(0),
            'created_on' => $this->datetime(),
            'updated_on' => $this->datetime(),
            'menu_id' => $this->integer(11)->notNull(),
            'create_user_id' => $this->integer(11)->notNull(),
            ], $tableOptions);
                $this->createTable('migration', [
            'version' => $this->string(180)->notNull(),
            'apply_time' => $this->integer(11),
            'PRIMARY KEY ([[version]])',
            ], $tableOptions);
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
                $this->createTable('order', [
            'id' => $this->primaryKey(),
            'restaurant_id' => $this->integer(14)->notNull(),
            'client_id' => $this->integer(14)->notNull(),
            'json_details' => $this->text()->notNull(),
            'trans_type' => $this->integer(100)->notNull(),
            'payment_type' => $this->integer(100)->notNull(),
            'sub_total' => $this->float(14,4)->notNull(),
            'tax' => $this->float(14,4)->notNull(),
            'tip_amt' => $this->integer(11)->notNull(),
            'taxable_total' => $this->decimal(14,4)->notNull(),
            'total_w_tax' => $this->float(14,4)->notNull(),
            'status' => $this->integer(11)->notNull(),
            'stats_id' => $this->integer(14)->notNull(),
            'viewed' => $this->integer(1)->notNull()->defaultValue(1),
            'delivery_charge' => $this->float(14,4)->notNull(),
            'delivery_date' => $this->date()->notNull(),
            'delivery_time' => $this->string(100)->notNull(),
            'delivery_asap' => $this->string(14)->notNull(),
            'delivery_instruction' => $this->string(255)->notNull(),
            'voucher_code' => $this->string(100)->notNull(),
            'voucher_amount' => $this->float(14,4)->notNull(),
            'voucher_type' => $this->string(100)->notNull(),
            'cc_id' => $this->integer(14)->notNull(),
            'created_date' => $this->datetime()->notNull(),
            'date_modified' => $this->datetime()->notNull(),
            'ip_address' => $this->string(50)->notNull(),
            'order_change' => $this->float(14,4)->notNull(),
            'delivery_addr_id' => $this->integer(11),
            ], $tableOptions);
                $this->createTable('order_details', [
            'id' => $this->primaryKey(),
            'order_id' => $this->integer(14)->notNull(),
            'client_id' => $this->integer(14)->notNull(),
            'item_id' => $this->integer(14)->notNull(),
            'item_name' => $this->string(255)->notNull(),
            'order_notes' => $this->text()->notNull(),
            'normal_price' => $this->float(14,4)->notNull(),
            'discounted_price' => $this->float(14,4)->notNull(),
            'size' => $this->string(255)->notNull(),
            'qty' => $this->integer(14)->notNull(),
            'cooking_ref' => $this->string(255)->notNull(),
            'addon' => $this->text()->notNull(),
            ], $tableOptions);
                $this->createTable('page', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'state_id' => $this->integer(11)->defaultValue(0),
            'type_id' => $this->integer(11)->defaultValue(0),
            'created_on' => $this->datetime(),
            'updated_on' => $this->datetime(),
            'create_user_id' => $this->integer(11)->notNull(),
            ], $tableOptions);
                $this->createTable('product', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'slug' => $this->string(512)->notNull(),
            'category_id' => $this->integer(11)->notNull(),
            'product_ids' => $this->text(),
            'youtube_link' => $this->text(),
            'sub_category_id' => $this->integer(11)->notNull(),
            'brand_id' => $this->integer(11)->notNull(),
            'deal_id' => $this->integer(11),
            'part_number' => $this->string(255)->notNull(),
            'amount' => $this->string(255)->notNull(),
            'discount' => $this->string(255),
            'description' => $this->text(),
            'state_id' => $this->integer(11)->defaultValue(0),
            'type_id' => $this->integer(11)->defaultValue(0),
            'created_on' => $this->datetime(),
            'updated_on' => $this->datetime(),
            'create_user_id' => $this->integer(11)->notNull(),
            'package_detail' => $this->string(255),
            ], $tableOptions);
                $this->createTable('product_price', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'product_id' => $this->integer(11)->notNull(),
            'min_quantity' => $this->integer(11)->notNull(),
            'max_quantity' => $this->integer(11)->notNull(),
            'price' => $this->string(255)->notNull(),
            'state_id' => $this->integer(11)->defaultValue(0),
            'type_id' => $this->integer(11)->defaultValue(0),
            'created_on' => $this->datetime(),
            'updated_on' => $this->datetime(),
            'create_user_id' => $this->integer(11)->notNull(),
            ], $tableOptions);
                $this->createTable('restaurant', [
            'id' => $this->primaryKey(),
            'slug' => $this->string(255)->notNull(),
            'name' => $this->string(255)->notNull(),
            'phone' => $this->string(100)->notNull(),
            'contact_name' => $this->string(255)->notNull(),
            'contact_phone' => $this->string(100)->notNull(),
            'contact_email' => $this->string(255)->notNull(),
            'country_code' => $this->string(3),
            'street' => $this->text()->notNull(),
            'city' => $this->string(255)->notNull(),
            'state' => $this->string(255)->notNull(),
            'post_code' => $this->string(100)->notNull(),
            'cuisine' => $this->text(),
            'service' => $this->string(255),
            'free_delivery' => $this->integer(1)->notNull()->defaultValue(2),
            'delivery_estimation' => $this->string(100),
            'lat' => $this->string(100),
            'lng' => $this->string(100),
            'status' => $this->string(100)->notNull()->defaultValue('0'),
            'created_date' => $this->datetime()->notNull(),
            'date_modified' => $this->datetime(),
            'date_activated' => $this->datetime(),
            'last_login' => $this->datetime(),
            'is_featured' => $this->integer(1)->notNull()->defaultValue(1),
            'is_ready' => $this->integer(1)->notNull()->defaultValue(1),
            'create_user_id' => $this->integer(11),
            'is_catering_available' => $this->integer(11)->notNull()->defaultValue(0),
            'is_table_booking' => $this->integer(11)->notNull()->defaultValue(0),
            'is_delivery_boy' => $this->integer(11)->notNull()->defaultValue(0),
            'logo' => $this->string(255),
            'featured' => $this->string(255),
            'is_open' => $this->integer(11)->notNull()->defaultValue(1),
            'opening_time' => $this->string(255),
            'closing_time' => $this->string(255),
            'avg_rating' => $this->string(255)->notNull()->defaultValue('5'),
            'min_order_amount' => $this->string(255)->defaultValue('0'),
            'commission_type' => $this->string(255)->notNull(),
            'commission' => $this->integer(11)->notNull(),
            'delivery_fee' => $this->integer(11)->notNull(),
            'delivery_radius' => $this->integer(11)->notNull(),
            ], $tableOptions);
                $this->createTable('restaurant_cusines', [
            'id' => $this->primaryKey(),
            'restaurant_id' => $this->integer(11),
            'cusines_id' => $this->integer(11),
            'state_id' => $this->integer(11)->defaultValue(0),
            'type_id' => $this->integer(11)->defaultValue(0),
            'created_on' => $this->datetime()->notNull(),
            'updated_on' => $this->datetime(),
            'create_user_id' => $this->integer(11)->notNull(),
            ], $tableOptions);
                $this->createTable('restaurant_timings', [
            'id' => $this->primaryKey(),
            'restaurant_id' => $this->integer(11)->notNull(),
            'day' => $this->string(255)->notNull(),
            'opening_time' => $this->time(5)->notNull(),
            'closing_time' => $this->time(5)->notNull(),
            'status' => $this->integer(11)->notNull(),
            'created_date' => $this->date()->notNull(),
            ], $tableOptions);
                $this->createTable('review', [
            'id' => $this->primaryKey(),
            'product_id' => $this->integer(11),
            'name' => $this->string(255),
            'email' => $this->string(255),
            'comments' => $this->text(),
            'ratings' => $this->float(),
            'created_on' => $this->datetime(),
            'update_on' => $this->datetime(),
            ], $tableOptions);
                $this->createTable('sub_category', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'slug' => $this->string(512)->notNull(),
            'category_id' => $this->integer(11)->notNull(),
            'sub_category_id' => $this->integer(11)->notNull()->defaultValue(0),
            'description' => $this->text(),
            'state_id' => $this->integer(11)->defaultValue(0),
            'type_id' => $this->integer(11)->defaultValue(0),
            'created_on' => $this->datetime(),
            'updated_on' => $this->datetime(),
            'create_user_id' => $this->integer(11)->notNull(),
            ], $tableOptions);
                $this->createTable('support_category', [
            'id' => $this->primaryKey(),
            'parent_id' => $this->string(512),
            'title' => $this->string(512)->notNull(),
            'state_id' => $this->integer(11)->defaultValue(0),
            'type_id' => $this->integer(11)->defaultValue(0),
            'created_on' => $this->datetime(),
            'updated_on' => $this->datetime(),
            'create_user_id' => $this->integer(11)->notNull(),
            ], $tableOptions);
                $this->createTable('support_solution', [
            'id' => $this->primaryKey(),
            'category_id' => $this->integer(11)->notNull(),
            'title' => $this->string(512)->notNull(),
            'description' => $this->text()->notNull(),
            'state_id' => $this->integer(11)->defaultValue(0),
            'type_id' => $this->integer(11)->defaultValue(0),
            'created_on' => $this->datetime(),
            'updated_on' => $this->datetime(),
            'create_user_id' => $this->integer(11)->notNull(),
            ], $tableOptions);
                $this->createTable('table_booking', [
            'id' => $this->primaryKey(),
            'first_name' => $this->string(255)->notNull(),
            'telephone' => $this->string(255)->notNull(),
            'email' => $this->string(255)->notNull(),
            'no_of_guest' => $this->integer(11)->defaultValue(0),
            'booking_date' => $this->datetime()->notNull(),
            'booking_time' => $this->string(255)->notNull(),
            'restaurant_id' => $this->integer(11)->defaultValue(0),
            'state_id' => $this->integer(11)->defaultValue(0),
            'type_id' => $this->integer(11)->defaultValue(0),
            'created_on' => $this->datetime()->notNull(),
            'updated_on' => $this->datetime(),
            'create_user_id' => $this->integer(11)->notNull(),
            ], $tableOptions);
                $this->createTable('temp_order', [
            'id' => $this->primaryKey(),
            'cart_id' => $this->integer(11)->notNull(),
            'address_id' => $this->integer(11)->notNull(),
            'delivery_type' => $this->integer(11)->notNull(),
            'created_time' => $this->date()->notNull(),
            'created_user' => $this->integer(11)->notNull(),
            'status' => $this->integer(11)->notNull(),
            'tip' => $this->integer(11)->notNull(),
            ], $tableOptions);
                $this->createTable('tip', [
            'id' => $this->primaryKey(),
            'tip_amt' => $this->integer(11)->notNull(),
            'driver_id' => $this->integer(11),
            'cart_id' => $this->integer(11)->notNull(),
            'restaurant_id' => $this->integer(11)->notNull(),
            'order_id' => $this->integer(11)->notNull(),
            'created_user_id' => $this->integer(11)->notNull(),
            'created_time' => $this->time()->notNull(),
            ], $tableOptions);
                $this->createTable('transaction', [
            'id' => $this->primaryKey(),
            'invoice_no' => $this->string(255),
            'order_id' => $this->integer(11)->notNull(),
            'amount' => $this->string(125)->notNull(),
            'transactions_no' => $this->string(125),
            'created_on' => $this->datetime()->notNull(),
            'updated_on' => $this->datetime(),
            'status' => $this->integer(11)->notNull(),
            'create_user_id' => $this->integer(11)->notNull(),
            ], $tableOptions);
                $this->createTable('user_coupon', [
            'id' => $this->primaryKey(),
            'coupon_id' => $this->integer(11)->notNull(),
            'user_id' => $this->integer(11)->notNull(),
            'state_id' => $this->integer(11)->defaultValue(0),
            'type_id' => $this->integer(11)->defaultValue(0),
            'created_on' => $this->datetime(),
            'updated_on' => $this->datetime(),
            'create_user_id' => $this->integer(11)->notNull(),
            ], $tableOptions);
                $this->createTable('wishlist', [
            'id' => $this->primaryKey(),
            'product_id' => $this->integer(11),
            'type_id' => $this->integer(11)->defaultValue(0),
            'state_id' => $this->integer(11)->defaultValue(1),
            'created_on' => $this->datetime(),
            'update_on' => $this->datetime(),
            'create_user_id' => $this->integer(11),
            ], $tableOptions);
                
    }

    public function down()
    {
        $this->dropTable('wishlist');
        $this->dropTable('user_coupon');
        $this->dropTable('transaction');
        $this->dropTable('tip');
        $this->dropTable('temp_order');
        $this->dropTable('table_booking');
        $this->dropTable('support_solution');
        $this->dropTable('support_category');
        $this->dropTable('sub_category');
        $this->dropTable('review');
        $this->dropTable('restaurant_timings');
        $this->dropTable('restaurant_cusines');
        $this->dropTable('restaurant');
        $this->dropTable('product_price');
        $this->dropTable('product');
        $this->dropTable('page');
        $this->dropTable('order_details');
        $this->dropTable('order');
        $this->dropTable('notification');
        $this->dropTable('migration');
        $this->dropTable('menu_items');
        $this->dropTable('menu_item_sizes');
        $this->dropTable('menu');
        $this->dropTable('media');
        $this->dropTable('keyword');
        $this->dropTable('driver_assigned');
        $this->dropTable('delivery_address');
        $this->dropTable('deal');
        $this->dropTable('coupons_applied');
        $this->dropTable('coupon');
        $this->dropTable('comissions');
        $this->dropTable('catering_request');
        $this->dropTable('category');
        $this->dropTable('cart_items');
        $this->dropTable('cart');
        $this->dropTable('brand');
        $this->dropTable('banner');
        $this->dropTable('auth_session');
        $this->dropTable('auth');
        $this->dropTable('addon_types');
        $this->dropTable('user');
    }
}
