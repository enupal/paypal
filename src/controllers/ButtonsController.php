<?php
/**
 * EnupalPaypal plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\paypal\controllers;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller as BaseController;
use enupal\paypal\Paypal;
use yii\web\NotFoundHttpException;
use yii\base\Exception;

use enupal\paypal\enums\PaypalSize;
use enupal\paypal\PaypalButtons;
use enupal\paypal\elements\PaypalButton as ButtonElement;

class ButtonsController extends BaseController
{
    /**
     * Save a Button
     *
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionSaveButton()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $button = new ButtonElement;

        $buttonId = $request->getBodyParam('buttonId');

        if ($buttonId) {
            $button = Paypal::$app->buttons->getButtonById($buttonId);
        }

        $button = Paypal::$app->buttons->populateButtonFromPost($button);

        // Save it
        if (!Paypal::$app->buttons->saveButton($button)) {
            Craft::$app->getSession()->setError(Paypal::t('Couldn’t save button.'));

            Craft::$app->getUrlManager()->setRouteParams([
                    'button' => $button
                ]
            );

            return null;
        }

        Craft::$app->getSession()->setNotice(Paypal::t('Button saved.'));

        return $this->redirectToPostedUrl($button);
    }

    /**
     * Edit a Button.
     *
     * @param int|null           $buttonId The button's ID, if editing an existing button.
     * @param ButtonElement|null $button   The button send back by setRouteParams if any errors on saveButton
     *
     * @return \yii\web\Response
     * @throws HttpException
     * @throws Exception
     */
    public function actionEditButton(int $buttonId = null, ButtonElement $button = null)
    {
        // Immediately create a new Slider
        if ($buttonId === null) {
            $button = Paypal::$app->buttons->createNewButton();

            if ($button->id) {
                $url = UrlHelper::cpUrl('enupal-paypal/buttons/edit/'.$button->id);
                return $this->redirect($url);
            } else {
                throw new Exception(Paypal::t('Error creating Button'));
            }
        } else {
            if ($buttonId !== null) {
                if ($button === null) {
                    // Get the button
                    $button = Paypal::$app->buttons->getButtonById($buttonId);

                    if (!$button) {
                        throw new NotFoundHttpException(Paypal::t('Button not found'));
                    }
                }
            }
        }

        $variables['buttonId'] = $buttonId;
        $variables['paypalButton'] = $button;

        // Set the "Continue Editing" URL
        $variables['continueEditingUrl'] = 'enupal-paypal/buttons/edit/{id}';

        $variables['settings'] = Paypal::$app->settings->getSettings();

        return $this->renderTemplate('enupal-paypal/buttons/_edit', $variables);
    }

    /**
     * Delete a Paypal Button.
     *
     * @return void
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionDeleteButton()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $buttonId = $request->getRequiredBodyParam('id');
        $button = Paypal::$app->buttons>getButtonById($buttonId);

        // @TODO - handle errors
        $success = Paypal::$app->sliders->deleteButton($button);

        return $success;
    }
}
