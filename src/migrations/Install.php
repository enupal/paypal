<?php
/**
 * EnupalPaypal plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\paypal\migrations;

use craft\db\Migration;

/**
 * Installation Migration
 */
class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTables();
        $this->addForeignKeys();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropTableIfExists('{{%enupalpaypal_buttons}}');
        $this->dropTableIfExists('{{%enupalpaypal_payments}}');

        return true;
    }

    /**
     * Creates the tables.
     *
     * @return void
     */
    protected function createTables()
    {
        $this->createTable('{{%enupalpaypal_buttons}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'type' => $this->string(),
            'currency' => $this->string(),
            'amount' => $this->money(10, 4),
            'itemId' => $this->string(),
            'options' => $this->string(),
            'returnUrl' => $this->string(),
            'cancelURL' => $this->string(),
            'buttonName' => $this->string(),
            //
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%enupalpaypal_payments}}', [
            'id' => $this->primaryKey(),
            'transactionId' => $this->string(),
            'firstName' => $this->string(),
            'lastName' => $this->string,
            'email' => $this->string,
            'address' => $this->string,
            'total' => $this->money(10, 4),
            'paymentStatus' => $this->string(),
            //
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
    }

    /**
     * Adds the foreign keys.
     *
     * @return void
     */
    protected function addForeignKeys()
    {
        $this->addForeignKey(
            $this->db->getForeignKeyName(
                '{{%enupalpaypal_buttons}}', 'id'
            ),
            '{{%enupalpaypal_buttons}}', 'id',
            '{{%elements}}', 'id', 'CASCADE', null
        );

        $this->addForeignKey(
            $this->db->getForeignKeyName(
                '{{%enupalpaypal_payments}}', 'id'
            ),
            '{{%enupalpaypal_payments}}', 'id',
            '{{%elements}}', 'id', 'CASCADE', null
        );
    }
}