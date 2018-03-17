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

class PaypalButtonsQuery extends ElementQuery
{

    // General - Properties
    // =========================================================================
    public $id;
    public $dateCreated;
    public $name;
    public $handle;

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
    public function __construct($elementType, array $config = [])
    {
        // Default orderBy
        if (!isset($config['orderBy'])) {
            $config['orderBy'] = 'enupalpaypal_buttons.dateCreated';
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
        $this->joinElementTable('enupalpaypal_buttons');

        $this->query->select([
            'enupalpaypal_buttons.name',
            'enupalpaypal_buttons.handle',
            'enupalpaypal_buttons.type',
            'enupalpaypal_buttons.currency',
            'enupalpaypal_buttons.amount',
            'enupalpaypal_buttons.itemId',
            'enupalpaypal_buttons.options',
            'enupalpaypal_buttons.returnUrl',
            'enupalpaypal_buttons.cancelURL',
            'enupalpaypal_buttons.buttonName'
        ]);

        if ($this->name) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalpaypal_buttons.name', $this->name)
            );
        }

        if ($this->handle) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalpaypal_buttons.handle', $this->handle)
            );
        }

        if ($this->orderBy !== null && empty($this->orderBy) && !$this->structureId && !$this->fixedOrder) {
            $this->orderBy = 'elements.dateCreated desc';
        }

        return parent::beforePrepare();
    }
}
