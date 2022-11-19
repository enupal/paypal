<?php
/**
 * EnupalPaypal plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\paypal\fields;

use craft\fields\BaseRelationField;
use enupal\paypal\elements\PaypalButton;
use enupal\paypal\Paypal as PaypalPlugin;

/**
 * Class Buttons
 *
 */
class Buttons extends BaseRelationField
{
    /**
     * @inheritdoc
     */
    public bool $allowMultipleSources = false;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return PaypalPlugin::t('PayPal Buy Now Buttons');
    }

    /**
     * @inheritdoc
     */
    protected static function elementType(): string
    {
        return PaypalButton::class;
    }

    /**
     * @inheritdoc
     */
    public static function defaultSelectionLabel(): string
    {
        return PaypalPlugin::t('Add a PayPal Buy Now Button');
    }
}
