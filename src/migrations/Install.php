<?php
/**
 * EnupalPaypal plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\paypal\migrations;

use craft\db\Migration;
use enupal\paypal\enums\PaypalSize;

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
        $this->dropTableIfExists('{{%enupalpaypal_orders}}');

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
            'size' => $this->integer()->defaultValue(PaypalSize::BUYBIGCC),
            'currency' => $this->string()->defaultValue('USD'),
            'language' => $this->string()->defaultValue('en_US'),
            'amount' => $this->decimal(14, 4)->unsigned(),
            'sku' => $this->string(),
            // Inventory
            'quantity' => $this->integer(),
            'customerQuantity' => $this->boolean(),
            'soldOut' => $this->boolean(),
            'soldOutMessage' => $this->string(),
            // Discounts
            'percentDiscount' => $this->decimal(14, 4)->notNull()->defaultValue(0),
            'rateDiscount' => $this->decimal(14, 4)->notNull()->defaultValue(0),
            // Shipping
            'shippingAmount' => $this->decimal(14, 4)->notNull()->defaultValue(0),
            // Weight
            'itemWeight' => $this->decimal(14, 4)->notNull()->defaultValue(0),
            'itemWeightUnit' => $this->string(),
            // Price menu
            'priceMenuName' => $this->string(),
            'priceMenuOptions' => $this->text(),
            // Customer
            'showItemName' => $this->boolean()->defaultValue(0),
            'showItemPrice' => $this->boolean()->defaultValue(0),
            'showItemCurrency' => $this->boolean()->defaultValue(0),
            'input1' => $this->string(),
            'input2' => $this->string(),
            'returnUrl' => $this->string(),
            'cancelUrl' => $this->string(),
            'buttonName' => $this->string(),
            //
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%enupalpaypal_orders}}', [
            'id' => $this->primaryKey(),
            'transactionId' => $this->string(),
            'firstName' => $this->string(),
            'lastName' => $this->string(),
            'email' => $this->string(),
            'address' => $this->string(),
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
                '{{%enupalpaypal_orders}}', 'id'
            ),
            '{{%enupalpaypal_orders}}', 'id',
            '{{%elements}}', 'id', 'CASCADE', null
        );
    }
}