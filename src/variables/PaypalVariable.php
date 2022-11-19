<?php
/**
 * EnupalPaypal plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\paypal\variables;

use enupal\paypal\elements\db\OrdersQuery;
use enupal\paypal\elements\db\PaypalButtonsQuery;
use enupal\paypal\elements\Order;
use enupal\paypal\elements\PaypalButton;
use enupal\paypal\enums\OrderStatus;
use enupal\paypal\Paypal;
use enupal\paypal\PaypalButtons;
use Craft;

/**
 * EnupalPaypal provides an API for accessing information about paypal buttons. It is accessible from templates via `craft.enupalPaypal`.
 *
 */
class PaypalVariable
{
    /**
     * @return string
     */
    public function getName()
    {
        $plugin = Paypal::$app->settings->getPlugin();

        return $plugin->getName();
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        $plugin = Paypal::$app->settings->getPlugin();

        return $plugin->getVersion();
    }

    /**
     * @return string
     */
    public function getSettings()
    {
        return Paypal::$app->settings->getSettings();
    }

    /**
     * Returns a complete PayPal Button for display in template
     *
     * @param string     $sku
     * @param array|null $options
     *
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \yii\base\Exception
     */
    public function displayButton($sku, array $options = null)
    {
        return Paypal::$app->buttons->getButtonHtml($sku, $options);
    }

    /**
     * @return array
     */
    public function getCurrencyIsoOptions()
    {
        return Paypal::$app->buttons->getIsoCurrencies();
    }

    /**
     * @return array
     */
    public function getCurrencyOptions()
    {
        return Paypal::$app->buttons->getCurrencies();
    }

    /**
     * @return array
     */
    public function getSizeOptions()
    {
        return Paypal::$app->buttons->getSizeOptions();
    }

    /**
     * @return array
     */
    public function getLanguageOptions()
    {
        return Paypal::$app->buttons->getLanguageOptions();
    }

    /**
     * @return array
     */
    public function getDiscountOptions()
    {
        return Paypal::$app->buttons->getDiscountOptions();
    }

    /**
     * @return array
     */
    public function getShippingOptions()
    {
        return Paypal::$app->buttons->getShippingOptions();
    }

    /**
     * @return array
     */
    public function getOrderStatuses()
    {
        $options = [];
        $options[OrderStatus::NEW] = Paypal::t('New');
        $options[OrderStatus::SHIPPED] = Paypal::t('Shipped');

        return $options;
    }

    /**
     * @return array
     */
    public function getOpenInOptions()
    {
        return Paypal::$app->buttons->getOpenOptions();
    }

    /**
     * @param $number
     * @return array|\craft\base\ElementInterface
     */
    public function getOrderByNumber($number)
    {
        $order = Paypal::$app->orders->getOrderByNumber($number);

        return $order;
    }

    /**
     * @param $id
     * @return \craft\base\ElementInterface|null
     */
    public function getOrderById($id)
    {
        $order = Paypal::$app->orders->getOrderById($id);

        return $order;
    }

    /**
     * @return \enupal\paypal\elements\Order[]|null
     */
    public function getAllOrders()
    {
        $orders = Paypal::$app->orders->getAllOrders();

        return $orders;
    }

    /**
     * @return OrdersQuery
     */
    public function getOrders()
    {
        $query = Order::find();

        return $query;
    }

    /**
     * @param $sku
     * @return array|\craft\base\ElementInterface|null
     */
    public function getButton($sku)
    {
        $button = Paypal::$app->buttons->getButtonBySku($sku);

        return $button;
    }
}

