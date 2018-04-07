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
use craft\behaviors\FieldLayoutBehavior;
use craft\elements\db\ElementQueryInterface;
use enupal\paypal\enums\DiscountType;
use yii\base\ErrorHandler;
use craft\helpers\UrlHelper;
use craft\elements\actions\Delete;

use enupal\paypal\elements\db\PaypalButtonsQuery;
use enupal\paypal\records\PaypalButton as PaypalButtonRecord;
use enupal\paypal\enums\PaypalSize;
use enupal\paypal\Paypal as PaypalPlugin;
use craft\validators\UniqueValidator;

/**
 * PaypalButton represents a entry element.
 */
class PaypalButton extends Element
{
    /**
     * @inheritdoc
     */
    public $id;

    /**
     * @var string Name.
     */
    public $name;

    /**
     * @var string Sku
     */
    public $sku;

    /**
     * @var string size
     */
    public $size;

    /**
     * @var string Currency
     */
    public $currency;

    /**
     * @var string Language
     */
    public $language;

    /**
     * @var int Amount
     */
    public $amount;

    /**
     * @inheritdoc
     */
    public $enabled;

    public $quantity;
    public $hasUnlimitedStock;
    public $customerQuantity;
    public $soldOut;
    public $soldOutMessage;
    public $discountType;
    public $discount;
    public $shippingOption;
    public $shippingAmount;
    public $itemWeight;
    public $itemWeightUnit;
    public $priceMenuName;
    public $priceMenuOptions;

    public $showItemName;
    public $showItemPrice;
    public $showItemCurrency;
    public $input1;
    public $input2;

    public $returnUrl;
    public $cancelUrl;
    public $buttonName;

    protected $env;
    protected $paypalUrl;
    protected $ipnUrl;
    protected $business;
    protected $settings;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'fieldLayout' => [
                'class' => FieldLayoutBehavior::class,
                'elementType' => self::class
            ],
        ]);
    }

    public function init()
    {
        parent::init();

        if (!$this->settings){
            $this->settings = PaypalPlugin::$app->settings->getSettings();
        }

        $this->env =  $this->settings->testMode ? 'www.sandbox' : 'www';

        $this->returnUrl = $this->returnUrl ? $this->returnUrl : $this->settings->returnUrl;
        $this->cancelUrl = $this->cancelUrl ? $this->cancelUrl : $this->settings->cancelUrl;
        $this->currency = $this->currency ? $this->currency : $this->settings->defaultCurrency;

        $this->business = $this->settings->testMode ? $this->settings->sandboxAccount : $this->settings->liveAccount;
    }

    /**
     * @return string
     * @throws \yii\base\Exception
     */
    public function getReturnUrl()
    {
        $returnUrl = null;

        if ($this->returnUrl){
            $returnUrl = $this->getSiteUrl($this->returnUrl);
        }

        return $returnUrl;
    }

    /**
     * @return string
     * @throws \yii\base\Exception
     */
    public function getCancelUrl()
    {
        $cancelUrl = null;

        if ($this->returnUrl){
            $cancelUrl = $this->getSiteUrl($this->cancelUrl);
        }

        return $cancelUrl;
    }

    /**
     * @return string
     */
    public function getPaypalUrl()
    {
        $this->paypalUrl = "https://".$this->env.".paypal.com/cgi-bin/webscr";

        return $this->paypalUrl;
    }

    /**
     * @return string
     * @throws \craft\errors\SiteNotFoundException
     */
    public function getIpnUrl()
    {
        $this->ipnUrl = Craft::$app->getSites()->getPrimarySite()->baseUrl.'enupalPaypalIPN';

        return $this->ipnUrl;
    }

    /**
     * @return string
     */
    public function getBusiness()
    {
        $this->business = $this->settings->testMode ? $this->settings->sandboxAccount : $this->settings->liveAccount;

        return $this->business;
    }

    /**
     * @return string
     */
    public function getTax()
    {
        $tax = $this->settings->tax ?? null;

        return $tax;
    }

    /**
     * @return string
     */
    public function getTaxType()
    {
        $taxType = null;

        switch ($this->settings->taxType) {
            case DiscountType::RATE:
                {
                    $taxType = 'tax_rate';
                    break;
                }
            case DiscountType::AMOUNT:
                {
                    $taxType = 'rate';
                    break;
                }
        }

        return $taxType;
    }

    /**
     * @return string
     */
    public function getDiscount()
    {
        $discount = $this->discount ?? null;

        return $discount;
    }

    /**
     * @return string
     */
    public function getDiscountType()
    {
        $discountType = null;

        switch ($this->discountType) {
            case DiscountType::RATE:
                {
                    $discountType = 'discount_rate';
                    break;
                }
            case DiscountType::AMOUNT:
                {
                    $discountType = 'discount_amount';
                    break;
                }
        }

        return $discountType;
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
        return true;
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
    public function getFieldLayout()
    {
        $behaviors = $this->getBehaviors();
        $fieldLayout = $behaviors['fieldLayout'];

        return $fieldLayout->getFieldLayout();
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
        return ['name', 'sku'];
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        $attributes = [
            'elements.dateCreated' => PaypalPlugin::t('Date Created'),
            'name' => PaypalPlugin::t('Name'),
            'sku' => PaypalPlugin::t('SKU')
        ];

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        $attributes['name'] = ['label' => PaypalPlugin::t('Name')];
        $attributes['sku'] = ['label' => PaypalPlugin::t('SKU')];
        $attributes['amount'] = ['label' => PaypalPlugin::t('Amount')];
        $attributes['dateCreated'] = ['label' => PaypalPlugin::t('Date Created')];

        return $attributes;
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = ['name', 'amount', 'sku', 'dateCreated'];

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
        $record->sku = $this->sku;
        $record->size = $this->size;
        $record->currency = $this->currency;
        $record->language = $this->language;
        $record->amount = $this->amount;
        $record->quantity = $this->quantity;
        $record->hasUnlimitedStock = $this->hasUnlimitedStock;
        $record->discountType = $this->discountType;
        $record->discount = $this->discount;
        $record->shippingAmount = $this->shippingAmount;
        $record->shippingOption = $this->shippingOption;
        $record->customerQuantity = $this->customerQuantity ? $this->customerQuantity : 0;

        $record->returnUrl = $this->returnUrl;
        $record->cancelUrl = $this->cancelUrl;
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
            [['name', 'sku'], 'required'],
            [['name', 'sku'], 'string', 'max' => 255],
            [['name', 'sku'], UniqueValidator::class, 'targetClass' => PaypalButtonRecord::class],
        ];
    }

    /**
     * @return string
     */
    public function getTypeName()
    {
        $statuses = PaypalSize::getConstants();

        $statuses = array_flip($statuses);

        return ucwords(strtolower($statuses[$this->type]));
    }

    /**
     * @param null   $size
     * @param string $lang
     *
     * @return string
     * @throws \yii\base\Exception
     */
    public function getButtonUrl($size = null, $language = 'en_US')
    {
        $buttonSize = $size ?? $this->size;
        // Small By default
        $buttonUrl = PaypalPlugin::$app->buttons->getButtonSizeUrl($buttonSize, $language);

        return $buttonUrl;
    }

    /**
     * @param $url
     *
     * @return string
     * @throws \yii\base\Exception
     */
    private function getSiteUrl($url)
    {
        if (UrlHelper::isAbsoluteUrl($url)){
            return $url;
        }

        return UrlHelper::siteUrl($url);
    }
}