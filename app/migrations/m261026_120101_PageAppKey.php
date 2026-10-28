<?php

use yii\db\Schema;

/**
 * Terms and Conditions, Privacy Policy and Refund Policy are separate for every app
 * (customer app, driver partner app, each taxi partner app): `page.app_key` says whose page it is.
 * Existing pages belong to the customer app.
 */
class m261026_120101_PageAppKey extends \yii\db\Migration
{
    public function safeUp()
    {
        $this->addColumn('page', 'app_key', $this->string(30)->notNull()->defaultValue('customer')->after('type_id'));
        $this->createIndex('idx_page_app_type', 'page', ['app_key', 'type_id']);
    }

    public function safeDown()
    {
        $this->dropIndex('idx_page_app_type', 'page');
        $this->dropColumn('page', 'app_key');
    }
}
