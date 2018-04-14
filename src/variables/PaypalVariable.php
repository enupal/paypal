<?php
/**
 * EnupalPaypal plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\paypal\variables;

use enupal\paypal\Paypal;
use enupal\paypal\PaypalButtons;

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
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function displayButton($sku, array $options = null)
    {
        return Paypal::$app->buttons->getButtonHtml($sku, $options);
    }

    public function getCurrencyIsoOptions()
    {
        return Paypal::$app->buttons->getIsoCurrencies();
    }

    public function getCurrencyOptions()
    {
        return Paypal::$app->buttons->getCurrencies();
    }

    public function getSizeOptions()
    {
        return Paypal::$app->buttons->getSizeOptions();
    }

    public function getLanguageOptions()
    {
        return Paypal::$app->buttons->getLanguageOptions();
    }

    public function getDiscountOptions()
    {
        return Paypal::$app->buttons->getDiscountOptions();
    }

    public function getShippingOptions()
    {
        return Paypal::$app->buttons->getShippingOptions();
    }
}

