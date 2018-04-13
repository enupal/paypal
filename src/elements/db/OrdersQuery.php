<?php
/**
 * EnupalPaypal plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\paypal\elements\db;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class OrdersQuery extends ElementQuery
{
    // General - Properties
    // =========================================================================
    public $id;
    public $dateCreated;
    public $number;
    public $buttonId;
    public $paypalTransactionId;
    public $totalPrice;
    public $tax;
    public $dateOrdered;

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        parent::__set($name, $value);
    }

    /**
     * @inheritdoc
     */
    public function number($value)
    {
        $this->number = $value;
    }

    /**
     * @inheritdoc
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @inheritdoc
     */
    public function totalPrice($value)
    {
        $this->totalPrice = $value;
    }

    /**
     * @inheritdoc
     */
    public function getTotalPrice()
    {
        return $this->totalPrice;
    }

    /**
     * @inheritdoc
     */
    public function paypalTransactionId($value)
    {
        $this->paypalTransactionId = $value;
    }

    /**
     * @inheritdoc
     */
    public function getPaypalTransactionId()
    {
        return $this->paypalTransactionId;
    }

    /**
     * @inheritdoc
     */
    public function __construct($elementType, array $config = [])
    {
        // Default orderBy
        if (!isset($config['orderBy'])) {
            $config['orderBy'] = 'enupalpaypal_orders.dateCreated';
        }

        parent::__construct($elementType, $config);
    }


    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        $this->joinElementTable('enupalpaypal_orders');

        $this->query->select([
            'enupalpaypal_orders.id',
            'enupalpaypal_orders.testMode',
            'enupalpaypal_orders.number',
            'enupalpaypal_orders.currency',
            'enupalpaypal_orders.totalPrice',
            'enupalpaypal_orders.tax',
            'enupalpaypal_orders.discount',
            'enupalpaypal_orders.shipping',
            'enupalpaypal_orders.buttonId',
            'enupalpaypal_orders.quantity',
            'enupalpaypal_orders.paypalTransactionId',
            'enupalpaypal_orders.transactionInfo',
            'enupalpaypal_orders.email',
            'enupalpaypal_orders.firstName',
            'enupalpaypal_orders.lastName',
            'enupalpaypal_orders.orderStatusId',
            'enupalpaypal_orders.addressCity',
            'enupalpaypal_orders.addressCountry',
            'enupalpaypal_orders.addressState',
            'enupalpaypal_orders.addressCountryCode',
            'enupalpaypal_orders.addressName',
            'enupalpaypal_orders.addressStreet',
            'enupalpaypal_orders.addressZip',
            'enupalpaypal_orders.variants',
            'enupalpaypal_orders.dateOrdered'
        ]);

        if ($this->number) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalpaypal_orders.number', $this->number)
            );
        }

        if ($this->paypalTransactionId) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalpaypal_orders.paypalTransactionId', $this->paypalTransactionId)
            );
        }

        if ($this->dateCreated) {
            $this->subQuery->andWhere(Db::parseDateParam('enupalpaypal_orders.dateCreated', $this->dateCreated));
        }

        if ($this->dateOrdered) {
            $this->subQuery->andWhere(Db::parseDateParam('enupalpaypal_orders.dateOrdered', $this->dateOrdered));
        }

        if ($this->orderBy !== null && empty($this->orderBy) && !$this->structureId && !$this->fixedOrder) {
            $this->orderBy = 'elements.dateCreated desc';
        }

        return parent::beforePrepare();
    }
}
