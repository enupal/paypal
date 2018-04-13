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
    public $liveAccount;
    public $sandboxAccount;
    public $testMode = 1;
    // Globals
    public $returnUrl;
    public $cancelUrl;
    public $defaultCurrency = 'USD';
    public $returnToMerchantText;
    public $weightUnit = 'g';
    // Tax
    public $taxType = DiscountType::RATE;
    public $tax;
    // Notification Customer
    public $enableCustomerNotification;
    public $customerNotificationRecipients;
    public $customerNotificationSubject;
    public $customerNotificationSenderName;
    public $customerNotificationSenderEmail;
    public $customerNotificationReplyToEmail;
    public $customerNotificationTemplate;
    // Notification Admin
    public $enableAdminNotification;
    public $adminNotificationRecipients;
    public $adminNotificationSenderName;
    public $adminNotificationSubject;
    public $adminNotificationSenderEmail;
    public $adminNotificationReplyToEmail;
    public $adminNotificationTemplate;

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
            [
                ['customerNotificationSenderEmail', 'customerNotificationReplyToEmail'],
                'email', 'on' => 'customerNotification'
            ],
            [
                ['adminNotificationSenderEmail', 'adminNotificationReplyToEmail'],
                'email', 'on' => 'adminNotification'
            ],
        ];
    }
}