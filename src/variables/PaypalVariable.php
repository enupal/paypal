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
     * @param string     $buttonHandle
     * @param array|null $options
     *
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function displaySlider($buttonHandle, array $options = null)
    {
        $slider = Paypal::$app->buttons->getButtonByHandle($buttonHandle);
        $templatePath = Slider::$app->sliders->getEnupalSliderPath();
        $sliderHtml = null;
        $settings = Slider::$app->sliders->getSettings();

        if ($slider) {
            $dataAttributes = Slider::$app->sliders->getDataAttributes($slider);
            $slidesElements = $slider->getSlides();

            $view = Craft::$app->getView();

            $view->setTemplatesPath($templatePath);

            $sliderHtml = $view->renderTemplate(
                'slider', [
                    'slider' => $slider,
                    'slidesElements' => $slidesElements,
                    'dataAttributes' => $dataAttributes,
                    'htmlHandle' => $settings['htmlHandle'],
                    'linkHandle' => $settings['linkHandle'],
                    'openLinkHandle' => $settings['openLinkHandle'],
                    'options' => $options
                ]
            );

            $view->setTemplatesPath(Craft::$app->path->getSiteTemplatesPath());
        } else {
            $sliderHtml = Slider::t("Slider {$sliderHandle} not found");
        }

        return TemplateHelper::raw($sliderHtml);
    }
}

