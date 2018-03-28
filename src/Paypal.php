<?php
/**
 * EnupalPaypal plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\paypal;

use Craft;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;
use craft\events\DefineComponentsEvent;
use craft\web\twig\variables\CraftVariable;
use craft\services\SystemMessages;
use craft\events\RegisterEmailMessagesEvent;

use enupal\paypal\variables\PaypalVariable;
use enupal\paypal\models\Settings;

class Paypal extends \craft\base\Plugin
{
    /**
     * Enable use of PaypalButton::$app-> in place of Craft::$app->
     *
     * @var [type]
     */
    public static $app;

    public $hasCpSection = true;
    public $hasCpSettings = true;
    public $schemaVersion = '1.0.0';

    public function init()
    {
        parent::init();
        self::$app = $this->get('app');

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, $this->getCpUrlRules());
        }
        );

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, $this->getSiteUrlRules());
        }
        );

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function(Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('paypalButton', PaypalVariable::class);
            }
        );

        Event::on(
            SystemMessages::class,
            SystemMessages::EVENT_REGISTER_MESSAGES,
            function(RegisterEmailMessagesEvent $event) {
                array_push($event->messages,
                    [
                        'key' => 'enupal_paypal_notification',
                        'subject' => 'You have received a payment',
                        'body' => 'We are happy to inform you that the you have received payment with id: {{payment.transactionId}}'
                    ]
                );
            }
        );
    }

    protected function createSettingsModel()
    {
        return new Settings();
    }

    public function getCpNavItem()
    {
        $parent = parent::getCpNavItem();
        return array_merge($parent, [
            'subnav' => [
                'payments' => [
                    "label" => self::t("Order"),
                    "url" => 'enupal-paypal/payments'
                ],
                'buttons' => [
                    "label" => self::t("Buttons"),
                    "url" => 'enupal-paypal/buttons'
                ],
                'settings' => [
                    "label" => self::t("Settings"),
                    "url" => 'enupal-paypal/settings'
                ]
            ]
        ]);
    }

    /**
     * Settings HTML
     *
     * @return string
     */
    protected function settingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('enupal-paypal/settings/index');
    }

    /**
     * @param string $message
     * @param array  $params
     *
     * @return string
     */
    public static function t($message, array $params = [])
    {
        return Craft::t('enupal-paypal', $message, $params);
    }

    public static function log($message, $type = 'info')
    {
        Craft::$type(self::t($message), __METHOD__);
    }

    public static function info($message)
    {
        Craft::info(self::t($message), __METHOD__);
    }

    public static function error($message)
    {
        Craft::error(self::t($message), __METHOD__);
    }

    /**
     * @return array
     */
    private function getCpUrlRules()
    {
        return [
            'enupal-paypal/buttons/new' =>
                'enupal-paypal/buttons/edit-button',

            'enupal-paypal/buttons/edit/<buttonId:\d+>' =>
                'enupal-paypal/buttons/edit-button',

            'enupal-paypal/payments/new' =>
                'enupal-paypal/payments/edit-button',

            'enupal-paypal/payments/edit/<paymentId:\d+>' =>
                'enupal-paypal/payments/edit-button',
        ];
    }

    /**
     * @return array
     */
    private function getSiteUrlRules()
    {
        return [
            'enupal-paypal/ipn' =>
                'enupal-paypal/webhook/ipn'
        ];
    }
}

