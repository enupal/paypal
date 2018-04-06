<?php
/**
 * EnupalPaypal plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\paypal\variables;

use Craft;
use enupal\paypal\Paypal;
use enupal\paypal\PaypalButtons;
use craft\helpers\Template as TemplateHelper;

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
     * Returns a complete Paypal Button for display in template
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
        $button = Paypal::$app->buttons->getButtonBySku($sku);
        $templatePath = Paypal::$app->buttons->getEnupalPaypalPath();
        $buttonHtml = null;
        $settings = Paypal::$app->settings->getSettings();

        if (!$settings->liveAccount || !$settings->sandboxAccount){
            return Paypal::t("Please add a valid PayPal account on the plugin settings");
        }

        if ($button) {
            $view = Craft::$app->getView();

            $view->setTemplatesPath($templatePath);

            $buttonHtml = $view->renderTemplate(
                'button', [
                    'button' => $button,
                    'settings' => $settings,
                    'options' => $options
                ]
            );

            $view->setTemplatesPath(Craft::$app->path->getSiteTemplatesPath());
        } else {
            $buttonHtml = Paypal::t("PayPal Button not found or disabled");
        }

        return TemplateHelper::raw($buttonHtml);
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

