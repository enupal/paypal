<?php
/**
 * EnupalPaypal plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\paypal\elements;

use Craft;
use craft\base\Element;
use craft\elements\db\ElementQueryInterface;
use yii\base\ErrorHandler;
use craft\helpers\UrlHelper;
use craft\elements\actions\Delete;

use enupal\paypal\elements\db\OrdersQuery;
use enupal\paypal\records\Order as OrderRecord;
use enupal\paypal\enums\OrderStatus;
use enupal\paypal\Paypal as PaypalPlugin;
use craft\validators\UniqueValidator;

/**
 * Order represents a entry element.
 */
class Order extends Element
{
    // General - Properties
    // =========================================================================
    public $id;

    /**
     * @var string Number
     */
    public $number;

    /**
     * @var string Paypal Transaction Id
     */
    public $paypalTransactionId;

    /**
     * @var int Number
     */
    public $quantity;

    public $buttonId;
    public $currency;
    public $amount;
    public $buyerEmail;
    public $buyerName;

    /**
     * Returns the element type name.
     *
     * @return string
     */
    public static function displayName(): string
    {
        return PaypalPlugin::t('Orders');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle()
    {
        return 'orders';
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function hasStatuses(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl()
    {
        return UrlHelper::cpUrl(
            'enupal-paypal/orders/edit/'.$this->id
        );
    }

    /**
     * Use the name as the string representation.
     *
     * @return string
     */
    /** @noinspection PhpInconsistentReturnPointsInspection */
    public function __toString()
    {
        try {
            return $this->number;
        } catch (\Exception $e) {
            ErrorHandler::convertExceptionToError($e);
        }
    }

    /**
     * @inheritdoc
     *
     * @return OrdersQuery The newly created [[OrdersQuery]] instance.
     */
    public static function find(): ElementQueryInterface
    {
        return new OrdersQuery(get_called_class());
    }

    /**
     *
     * @return string|null
     */
    public function getStatus()
    {
        $statusId = $this->status;

        $colors = PaypalPlugin::$app->orders->getColorStatuses();

        return $colors[$statusId];
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context = null): array
    {
        $sources = [
            [
                'key' => '*',
                'label' => PaypalPlugin::t('All Buttons'),
            ]
        ];

        $statuses = OrderStatus::getConstants();

        $colors = PaypalPlugin::$app->orders->getColorStatuses();

        $sources[] = ['heading' => PaypalPlugin::t("Order Status")];

        foreach ($statuses as $code => $status) {
            $key = 'orderStatusId:'.$status;
            $sources[] = [
                'status' => $colors[$status],
                'key' => $key,
                'label' => ucwords(strtolower($code)),
                'criteria' => ['orderStatusId' => $status]
            ];
        }

        return $sources;
    }

    /**
     * @inheritdoc
     */
    protected static function defineActions(string $source = null): array
    {
        $actions = [];

        // Delete
        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Delete::class,
            'confirmationMessage' => PaypalPlugin::t('Are you sure you want to delete the selected orders?'),
            'successMessage' => PaypalPlugin::t('Orders deleted.'),
        ]);

        return $actions;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return ['number', 'paypalTransactionId'];
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        $attributes = [
            'elements.dateCreated' => PaypalPlugin::t('Date Created')
        ];

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        $attributes['number'] = ['label' => PaypalPlugin::t('Order Number')];
        $attributes['amount'] = ['label' => PaypalPlugin::t('Total')];
        $attributes['dateCreated'] = ['label' => PaypalPlugin::t('Date Ordered')];
        $attributes['status'] = ['label' => PaypalPlugin::t('Status')];

        return $attributes;
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = ['number', 'amount', 'dateCreated', 'status'];

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'dateCreated':
                {
                    return $this->dateCreated->format("Y-m-d H:i");
                }
        }

        return parent::tableAttributeHtml($attribute);
    }

    /**
     * @inheritdoc
     * @throws Exception if reasons
     */
    public function afterSave(bool $isNew)
    {
        // Get the Order record
        if (!$isNew) {
            $record = OrderRecord::findOne($this->id);

            if (!$record) {
                throw new Exception('Invalid Order ID: '.$this->id);
            }
        } else {
            $record = new OrderRecord();
            $record->id = $this->id;
        }

        $record->number = $this->number;
        $record->currency = $this->currency;
        $record->amount = $this->amount;
        $record->buttonId = $this->buttonId;
        $record->quantity = $this->quantity;
        $record->paypalTransactionId = $this->paypalTransactionId;
        $record->buyerEmail = $this->buyerEmail;
        $record->buyerName = $this->buyerName;
        $record->save(false);

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['number'], 'required'],
            [['number'], UniqueValidator::class, 'targetClass' => OrderRecord::class],
        ];
    }

    public function getStatusName()
    {
        $statuses = OrderStatus::getConstants();

        $statuses = array_flip($statuses);

        return ucwords(strtolower($statuses[$this->status]));
    }
}