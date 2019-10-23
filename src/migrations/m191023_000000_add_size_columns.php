<?php

namespace enupal\paypal\migrations;

use craft\db\Migration;

/**
 * m191023_000000_add_size_columns migration.
 */
class m191023_000000_add_size_columns extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $table = '{{%enupalpaypal_buttons}}';

        if (!$this->db->columnExists($table, 'buttonSizeCustomUrl')) {
            $this->addColumn($table, 'buttonSizeCustomUrl', $this->string());
        }

        if (!$this->db->columnExists($table, 'buttonSizeCustomName')) {
            $this->addColumn($table, 'buttonSizeCustomName', $this->string());
        }

        if (!$this->db->columnExists($table, 'buttonSizeCustomClass')) {
            $this->addColumn($table, 'buttonSizeCustomClass', $this->string());
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m191023_000000_add_size_columns cannot be reverted.\n";

        return false;
    }
}
