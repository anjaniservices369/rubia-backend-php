<?php

use yii\db\Schema;

class m190117_210101_hungerfix2 extends \yii\db\Migration
{
    public function safeUp()
    {
        $tables = Yii::$app->db->schema->getTableNames();
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }
        
        if (!in_array(Yii::$app->db->tablePrefix.'user', $tables))  {
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
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."user` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'addon_types', $tables))  {
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
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."addon_types` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'auth', $tables))  {
          $this->createTable('auth', [
              'id' => $this->primaryKey(),
              'user_id' => $this->integer(11)->notNull(),
              'source' => $this->string(255)->notNull(),
              'source_id' => $this->string(255)->notNull(),
              'FOREIGN KEY ([[user_id]]) REFERENCES user ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
              ], $tableOptions);
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."auth` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'auth_session', $tables))  {
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
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."auth_session` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'banner', $tables))  {
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
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."banner` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'brand', $tables))  {
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
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."brand` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'cart', $tables))  {
          $this->createTable('cart', [
              'id' => $this->primaryKey(),
              'restaurant_id' => $this->integer(11)->notNull(),
              'amount' => $this->string(255)->defaultValue('0'),
              'quantity' => $this->integer(11),
              'detail' => $this->text(),
              'item_size_id' => $this->integer(11)->notNull(),
              'url' => $this->string(255),
              'state_id' => $this->integer(11)->defaultValue(0),
              'type_id' => $this->integer(11)->defaultValue(0),
              'created_on' => $this->datetime(),
              'updated_on' => $this->datetime(),
              'cookie_id' => $this->string(255)->defaultValue('0'),
              'create_user_id' => $this->integer(11)->defaultValue(0),
              ], $tableOptions);
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."cart` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'cart_items', $tables))  {
          $this->createTable('cart_items', [
              'id' => $this->primaryKey(),
              'cart_id' => $this->integer(11)->notNull(),
              'menu_item_id' => $this->integer(11)->notNull(),
              'addon_id' => $this->string(255)->notNull(),
              'item_size_id' => $this->integer(11)->notNull(),
              'amount' => $this->string(255)->notNull(),
              'quantity' => $this->integer(11)->notNull(),
              'state_id' => $this->integer(11)->defaultValue(0),
              'type_id' => $this->integer(11)->defaultValue(0),
              'created_on' => $this->datetime(),
              'updated_on' => $this->datetime(),
              'create_user_id' => $this->integer(11)->defaultValue(0),
              ], $tableOptions);
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."cart_items` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'category', $tables))  {
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
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."category` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'catering_request', $tables))  {
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
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."catering_request` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'coupon', $tables))  {
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
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."coupon` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'coupons_applied', $tables))  {
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
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."coupons_applied` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'deal', $tables))  {
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
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."deal` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'delivery_address', $tables))  {
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
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."delivery_address` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'restaurant', $tables))  {
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
              'delivery_fee' => $this->integer(11)->notNull(),
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
              'delivery_radius' => $this->integer(11)->notNull(),
              ], $tableOptions);
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."restaurant` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'fax_broadcast', $tables))  {
          $this->createTable('fax_broadcast', [
              'id' => $this->primaryKey(),
              'restaurant_id' => $this->integer(11)->notNull(),
              'faxno' => $this->string(50)->notNull()->defaultValue(''),
              'recipname' => $this->string(32)->notNull()->defaultValue(''),
              'faxurl' => $this->string(255)->notNull()->defaultValue(''),
              'status' => $this->string(255)->notNull()->defaultValue('pending'),
              'jobid' => $this->string(255)->notNull()->defaultValue(''),
              'api_raw_response' => $this->text(),
              'created_on' => $this->datetime()->notNull()->defaultValue('0000-00-00 00:00:00'),
              'updated_on' => $this->datetime()->notNull()->defaultValue('0000-00-00 00:00:00'),
              'FOREIGN KEY ([[restaurant_id]]) REFERENCES restaurant ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
              ], $tableOptions);
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."fax_broadcast` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'keyword', $tables))  {
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
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."keyword` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'media', $tables))  {
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
              'FOREIGN KEY ([[create_user_id]]) REFERENCES user ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
              ], $tableOptions);
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."media` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'menu', $tables))  {
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
              'FOREIGN KEY ([[create_user_id]]) REFERENCES user ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
              'FOREIGN KEY ([[restaurant_id]]) REFERENCES restaurant ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
              ], $tableOptions);
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."menu` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'menu_items', $tables))  {
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
              'FOREIGN KEY ([[menu_id]]) REFERENCES menu ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
              'FOREIGN KEY ([[create_user_id]]) REFERENCES user ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
              ], $tableOptions);
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."menu_items` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'menu_item_sizes', $tables))  {
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
              'FOREIGN KEY ([[create_user_id]]) REFERENCES user ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
              'FOREIGN KEY ([[menu_item_id]]) REFERENCES menu_items ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
              ], $tableOptions);
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."menu_item_sizes` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'migration', $tables))  {
          $this->createTable('migration', [
              'version' => $this->string(180)->notNull(),
              'apply_time' => $this->integer(11),
              'PRIMARY KEY ([[version]])',
              ], $tableOptions);
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."migration` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'order', $tables))  {
          $this->createTable('order', [
              'id' => $this->primaryKey(),
              'restaurant_id' => $this->integer(14)->notNull(),
              'client_id' => $this->integer(14)->notNull(),
              'json_details' => $this->text()->notNull(),
              'trans_type' => $this->string(100)->notNull(),
              'payment_type' => $this->string(100)->notNull(),
              'sub_total' => $this->float(14,4)->notNull(),
              'tax' => $this->float(14,4)->notNull(),
              'taxable_total' => $this->decimal(14,4)->notNull(),
              'total_w_tax' => $this->float(14,4)->notNull(),
              'status' => $this->string(255)->notNull()->defaultValue('pending'),
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
              ], $tableOptions);
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."order` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'order_details', $tables))  {
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
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."order_details` already exists!\n";
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
                 if (!in_array(Yii::$app->db->tablePrefix.'page', $tables))  {
          $this->createTable('page', [
              'id' => $this->primaryKey(),
              'title' => $this->string(255)->notNull(),
              'slug' => $this->string(255)->notNull(),
              'description' => $this->text(),
              'state_id' => $this->integer(11)->defaultValue(0),
              'type_id' => $this->integer(11)->defaultValue(0),
              'created_on' => $this->datetime(),
              'updated_on' => $this->datetime(),
              'create_user_id' => $this->integer(11)->notNull(),
              'FOREIGN KEY ([[create_user_id]]) REFERENCES user ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
              ], $tableOptions);
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."page` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'sub_category', $tables))  {
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
              'FOREIGN KEY ([[category_id]]) REFERENCES category ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
              'FOREIGN KEY ([[create_user_id]]) REFERENCES user ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
              ], $tableOptions);
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."sub_category` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'product', $tables))  {
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
              'FOREIGN KEY ([[brand_id]]) REFERENCES brand ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
              'FOREIGN KEY ([[category_id]]) REFERENCES category ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
              'FOREIGN KEY ([[create_user_id]]) REFERENCES user ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
              'FOREIGN KEY ([[sub_category_id]]) REFERENCES sub_category ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
              ], $tableOptions);
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."product` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'product_price', $tables))  {
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
              'FOREIGN KEY ([[create_user_id]]) REFERENCES user ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
              'FOREIGN KEY ([[product_id]]) REFERENCES product ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
              ], $tableOptions);
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."product_price` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'restaurant_cusines', $tables))  {
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
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."restaurant_cusines` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'restaurant_timings', $tables))  {
          $this->createTable('restaurant_timings', [
              'id' => $this->primaryKey(),
              'restaurant_id' => $this->integer(11)->notNull(),
              'day' => $this->string(255)->notNull(),
              'opening_time' => $this->time(5)->notNull(),
              'closing_time' => $this->time(5)->notNull(),
              'status' => $this->integer(11)->notNull(),
              'created_date' => $this->date()->notNull(),
              'FOREIGN KEY ([[restaurant_id]]) REFERENCES restaurant ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
              ], $tableOptions);
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."restaurant_timings` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'review', $tables))  {
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
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."review` already exists!\n";
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
                 if (!in_array(Yii::$app->db->tablePrefix.'support_category', $tables))  {
          $this->createTable('support_category', [
              'id' => $this->primaryKey(),
              'parent_id' => $this->string(512),
              'title' => $this->string(512)->notNull(),
              'state_id' => $this->integer(11)->defaultValue(0),
              'type_id' => $this->integer(11)->defaultValue(0),
              'created_on' => $this->datetime(),
              'updated_on' => $this->datetime(),
              'create_user_id' => $this->integer(11)->notNull(),
              'FOREIGN KEY ([[create_user_id]]) REFERENCES user ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
              ], $tableOptions);
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."support_category` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'support_solution', $tables))  {
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
              'FOREIGN KEY ([[create_user_id]]) REFERENCES user ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
              'FOREIGN KEY ([[category_id]]) REFERENCES support_category ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
              ], $tableOptions);
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."support_solution` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'table_booking', $tables))  {
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
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."table_booking` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'transaction', $tables))  {
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
              'FOREIGN KEY ([[create_user_id]]) REFERENCES user ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
              ], $tableOptions);
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."transaction` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'user_coupon', $tables))  {
          $this->createTable('user_coupon', [
              'id' => $this->primaryKey(),
              'coupon_id' => $this->integer(11)->notNull(),
              'user_id' => $this->integer(11)->notNull(),
              'state_id' => $this->integer(11)->defaultValue(0),
              'type_id' => $this->integer(11)->defaultValue(0),
              'created_on' => $this->datetime(),
              'updated_on' => $this->datetime(),
              'create_user_id' => $this->integer(11)->notNull(),
              'FOREIGN KEY ([[coupon_id]]) REFERENCES coupon ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
              'FOREIGN KEY ([[user_id]]) REFERENCES user ([[id]]) ON DELETE CASCADE ON UPDATE CASCADE',
              ], $tableOptions);
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."user_coupon` already exists!\n";
        }
                 if (!in_array(Yii::$app->db->tablePrefix.'wishlist', $tables))  {
          $this->createTable('wishlist', [
              'id' => $this->primaryKey(),
              'product_id' => $this->integer(11),
              'type_id' => $this->integer(11)->defaultValue(0),
              'state_id' => $this->integer(11)->defaultValue(1),
              'created_on' => $this->datetime(),
              'update_on' => $this->datetime(),
              'create_user_id' => $this->integer(11),
              ], $tableOptions);
                } else {
          echo "\nTable `".Yii::$app->db->tablePrefix."wishlist` already exists!\n";
        }
                 
    }

    public function safeDown()
    {
        $this->dropTable('wishlist');
        $this->dropTable('user_coupon');
        $this->dropTable('transaction');
        $this->dropTable('table_booking');
        $this->dropTable('support_solution');
        $this->dropTable('support_category');
        $this->dropTable('shipping_rate');
        $this->dropTable('review');
        $this->dropTable('restaurant_timings');
        $this->dropTable('restaurant_cusines');
        $this->dropTable('product_price');
        $this->dropTable('product');
        $this->dropTable('sub_category');
        $this->dropTable('page');
        $this->dropTable('order_history');
        $this->dropTable('order_details');
        $this->dropTable('order');
        $this->dropTable('migration');
        $this->dropTable('menu_item_sizes');
        $this->dropTable('menu_items');
        $this->dropTable('menu');
        $this->dropTable('media');
        $this->dropTable('keyword');
        $this->dropTable('fax_broadcast');
        $this->dropTable('restaurant');
        $this->dropTable('delivery_address');
        $this->dropTable('deal');
        $this->dropTable('coupons_applied');
        $this->dropTable('coupon');
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
