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
            'enupalpaypal_buttons.id',
            'enupalpaypal_buttons.number',
            'enupalpaypal_buttons.currency',
            'enupalpaypal_buttons.amount',
            'enupalpaypal_buttons.buttonId',
            'enupalpaypal_buttons.quantity',
            'enupalpaypal_buttons.paypalTransactionId',
            'enupalpaypal_buttons.buyerEmail',
            'enupalpaypal_buttons.buyerName'
        ]);

        if ($this->number) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalpaypal_buttons.number', $this->number)
            );
        }

        if ($this->paypalTransactionId) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalpaypal_buttons.paypalTransactionId', $this->paypalTransactionId)
            );
        }

        if ($this->orderBy !== null && empty($this->orderBy) && !$this->structureId && !$this->fixedOrder) {
            $this->orderBy = 'elements.dateCreated desc';
        }

        return parent::beforePrepare();
    }
}
