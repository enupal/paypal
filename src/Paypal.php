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
use enupal\paypal\events\OrderCompleteEvent;
use enupal\paypal\services\App;
use enupal\paypal\services\Orders;
use yii\base\Event;
use craft\web\twig\variables\CraftVariable;

use enupal\paypal\variables\PaypalVariable;
use enupal\paypal\models\Settings;
use craft\base\Plugin;

class Paypal extends Plugin
{
    /**
     * Enable use of Paypal::$app-> in place of Craft::$app->
     *
     * @var App
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

        Event::on(Orders::class, Orders::EVENT_AFTER_ORDER_COMPLETE, function(OrderCompleteEvent $e) {
            Paypal::$app->orders->sendCustomerNotification($e->order);
        });
    }

    /**
     * @inheritdoc
     */
    protected function afterInstall()
    {
        Paypal::$app->buttons->createDefaultVariantFields();
    }

    /**
     * @inheritdoc
     */
    protected function afterUninstall()
    {
        Paypal::$app->buttons->deleteVariantFields();
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem()
    {
        $parent = parent::getCpNavItem();
        return array_merge($parent, [
            'subnav' => [
                'orders' => [
                    "label" => self::t("Orders"),
                    "url" => 'enupal-paypal/orders'
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
     * @inheritdoc
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

            'enupal-paypal/orders/edit/<orderId:\d+>' =>
                'enupal-paypal/orders/edit-order',

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
                'enupal-paypal/paypal/ipn'
        ];
    }
}

