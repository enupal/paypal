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
    public mixed $id;
    public mixed $dateCreated;
    public $name;
    public $sku;

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
    public function sku($value)
    {
        $this->sku = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSku()
    {
        return $this->sku;
    }

    /**
     * @inheritdoc
     */
    public function name($value)
    {
        $this->name = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->name;
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
            'enupalpaypal_buttons.id',
            'enupalpaypal_buttons.name',
            'enupalpaypal_buttons.size',
            'enupalpaypal_buttons.buttonSizeCustomUrl',
            'enupalpaypal_buttons.buttonSizeCustomName',
            'enupalpaypal_buttons.buttonSizeCustomClass',
            'enupalpaypal_buttons.currency',
            'enupalpaypal_buttons.language',
            'enupalpaypal_buttons.amount',
            'enupalpaypal_buttons.sku',
            'enupalpaypal_buttons.quantity',
            'enupalpaypal_buttons.hasUnlimitedStock',
            'enupalpaypal_buttons.customerQuantity',
            'enupalpaypal_buttons.soldOut',
            'enupalpaypal_buttons.soldOutMessage',
            'enupalpaypal_buttons.discountType',
            'enupalpaypal_buttons.discount',
            'enupalpaypal_buttons.shippingAmount',
            'enupalpaypal_buttons.shippingOption',
            'enupalpaypal_buttons.itemWeight',
            'enupalpaypal_buttons.itemWeightUnit',
            'enupalpaypal_buttons.priceMenuName',
            'enupalpaypal_buttons.priceMenuOptions',
            'enupalpaypal_buttons.showItemName',
            'enupalpaypal_buttons.showItemPrice',
            'enupalpaypal_buttons.showItemCurrency',
            'enupalpaypal_buttons.input1',
            'enupalpaypal_buttons.input2',
            'enupalpaypal_buttons.returnUrl',
            'enupalpaypal_buttons.cancelUrl',
            'enupalpaypal_buttons.buttonName',
            'enupalpaypal_buttons.openIn',
        ]);

        if ($this->name) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalpaypal_buttons.name', $this->name)
            );
        }

        if ($this->sku) {
            $this->subQuery->andWhere(Db::parseParam(
                'enupalpaypal_buttons.sku', $this->sku)
            );
        }

        if ($this->orderBy !== null && empty($this->orderBy) && !$this->structureId && !$this->fixedOrder) {
            $this->orderBy = 'dateCreated desc';
        }

        return parent::beforePrepare();
    }
}
