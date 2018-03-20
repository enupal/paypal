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
use enupal\paypal\Paypal;
use yii\base\ErrorHandler;
use craft\helpers\UrlHelper;
use craft\elements\actions\Delete;

use enupal\paypal\elements\db\PaypalButtonsQuery;
use enupal\paypal\records\PaypalButton as PaypalButtonRecord;
use enupal\paypal\enums\PaypalType;
use enupal\paypal\Paypal as PaypalPlugin;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

/**
 * PaypalButton represents a entry element.
 */
class PaypalButton extends Element
{
    // General - Properties
    // =========================================================================
    public $id;
    public $type;
    public $currency;
    public $amount;
    public $itemId;
    public $options;
    public $returnUrl;
    public $cancelURL;
    public $buttonName;

    protected $sandboxUrl = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
    protected $liveUrl = 'https://www.paypal.com/cgi-bin/webscr';
    protected $env;

    public function init()
    {
        parent::init();
        if (!$this->buttonName){
            $this->buttonName = $this->type == PaypalType::PAY ? 'Buy Now' : 'Donate';
        }
        $settings = Paypal::$app->settings->getSettings();
        $this->env = $settings->testMode ? 'www.sandbox' : 'www' ;
    }

    /**
     * Returns the field context this element's content uses.
     *
     * @access protected
     * @return string
     */
    public function getFieldContext(): string
    {
        return 'enupalPaypal:'.$this->id;
    }

    /**
     * Returns the element type name.
     *
     * @return string
     */
    public static function displayName(): string
    {
        return PaypalPlugin::t('Paypal Buttons');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle()
    {
        return 'paypal-buttons';
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
            'enupal-paypal/buttons/edit/'.$this->id
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
            // @todo - For some reason the Title returns null possible Craft3 bug
            return $this->name;
        } catch (\Exception $e) {
            ErrorHandler::convertExceptionToError($e);
        }
    }

    /**
     * @inheritdoc
     *
     * @return PaypalButtonsQuery The newly created [[PaypalButtonsQuery]] instance.
     */
    public static function find(): ElementQueryInterface
    {
        return new PaypalButtonsQuery(get_called_class());
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

        // @todo add groups

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
            'confirmationMessage' => PaypalPlugin::t('Are you sure you want to delete the selected buttons?'),
            'successMessage' => PaypalPlugin::t('Payapal Buttons deleted.'),
        ]);

        return $actions;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return ['name', 'handle'];
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        $attributes = [
            'elements.dateCreated' => PaypalPlugin::t('Date Created'),
            'name' => PaypalPlugin::t('Name'),
            'handle' => PaypalPlugin::t('Handle')
        ];

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        $attributes['name'] = ['label' => PaypalPlugin::t('Name')];
        $attributes['handle'] = ['label' => PaypalPlugin::t('Handle')];
        $attributes['type'] = ['label' => PaypalPlugin::t('Type')];
        $attributes['dateCreated'] = ['label' => PaypalPlugin::t('Date Created')];

        return $attributes;
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = ['name', 'handle', 'type','dateCreated'];

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
        $record = new PaypalButtonRecord();
        // Get the PaypalButton record
        if (!$isNew) {
            $record = PaypalButtonRecord::findOne($this->id);

            if (!$record) {
                throw new \Exception('Invalid PaypalButton ID: '.$this->id);
            }
        } else {
            $record->id = $this->id;
        }

        $record->name = $this->name;
        $record->handle = $this->handle;
        $record->type = $this->type;
        $record->currency = $this->currency;
        $record->amount = $this->amount;
        $record->itemId = $this->itemId;
        $record->options = $this->options;
        $record->returnUrl = $this->returnUrl;
        $record->cancelURL = $this->cancelURL;
        $record->buttonName = $this->buttonName;

        $record->save(false);

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'handle'], 'required'],
            [['name', 'handle'], 'string', 'max' => 255],
            [
                ['handle'],
                HandleValidator::class,
                'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']
            ],
            [['name', 'handle'], UniqueValidator::class, 'targetClass' => SliderRecord::class],
        ];
    }

    public function getTypeName()
    {
        $statuses = PaypalType::getConstants();

        $statuses = array_flip($statuses);

        return ucwords(strtolower($statuses[$this->type]));
    }
}