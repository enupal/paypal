<?php
/**
 * EnupalPaypal plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\paypal\controllers;

use Craft;
use craft\web\Controller as BaseController;
use enupal\paypal\Paypal;

class SettingsController extends BaseController
{
    /**
     * Save Plugin Settings
     *
     * @return \yii\web\Response
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionSaveSettings()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $settings = $request->getBodyParam('settings');

        if (!Paypal::$app->settings->saveSettings($settings)) {
            Craft::$app->getSession()->setError(Paypal::t('Couldn’t save settings.'));

            // Send the settings back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Paypal::t('Settings saved.'));

        return $this->redirectToPostedUrl();
    }

    /**
     * @return \yii\web\Response
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionGetSizeUrl()
    {
        $this->requireAcceptsJson();
        $request = Craft::$app->getRequest();

        $size = $request->getBodyParam('size');
        $language = $request->getBodyParam('language');

        $buttonUrl = Paypal::$app->buttons->getButtonSizeUrl($size, $language);

        return $this->asJson(['buttonUrl' => $buttonUrl]);
    }
}
