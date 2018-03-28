<?php
/**
 * EnupalPaypal plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\paypal\models;

use craft\base\Model;
use enupal\paypal\enums\DiscountType;

class Settings extends Model
{
    // General
    public $liveAccount = '';
    public $sandboxAccount = '';
    public $testMode = 1;
    // Globals
    public $returnUrl = '';
    public $cancelUrL = '';
    public $defaultCurrency = '';
    public $returnToMerchantText = '';
    public $weightUnit = 'g';
    // Tax
    public $taxType = DiscountType::RATE;
    public $tax = '';
    // Notification
    public $enableNotification = '';
    public $notificationRecipients = '';
    public $notificationSenderName = '';
    public $notificationSenderEmail = '';
    public $notificationReplyToEmail = '';

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                ['liveAccount'],
                'required', 'on' => 'general'
            ],
        ];
    }
}