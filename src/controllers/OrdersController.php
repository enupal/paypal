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
use yii\web\NotFoundHttpException;
use yii\base\Exception;

use enupal\paypal\elements\PaypalButton as ButtonElement;

class OrdersController extends BaseController
{

    /**
     * Save an Order
     *
     * @return null|\yii\web\Response
     * @throws NotFoundHttpException
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionSaveOrder()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $order = new ButtonElement;

        $orderId = $request->getBodyParam('orderId');

        if ($orderId) {
            $order = Paypal::$app->orders->getOrderById($orderId);
        }

        if (!$order) {
            throw new NotFoundHttpException(Paypal::t('Order not found'));
        }

        $order = Paypal::$app->orders->populateButtonFromPost($order);

        // Save it
        if (!Paypal::$app->orders->saveOrder($order)) {
            Craft::$app->getSession()->setError(Paypal::t('Couldn’t save Order.'));

            Craft::$app->getUrlManager()->setRouteParams([
                    'order' => $order
                ]
            );

            return null;
        }

        Craft::$app->getSession()->setNotice(Paypal::t('Order saved.'));

        return $this->redirectToPostedUrl($order);
    }

    /**
     * Edit a Button.
     *
     * @param int|null           $orderId The button's ID, if editing an existing button.
     * @param ButtonElement|null $order   The order send back by setRouteParams if any errors on saveButton
     *
     * @return \yii\web\Response
     * @throws HttpException
     * @throws Exception
     */
    public function actionEditOrder(int $orderId = null, ButtonElement $order = null)
    {
        if ($order === null) {
            $order = Paypal::$app->orders->getOrderById($orderId);
        }

        if (!$order) {
            throw new NotFoundHttpException(Paypal::t('Order not found'));
        }

        $variables['order'] = $order;

        // Set the "Continue Editing" URL
        $variables['continueEditingUrl'] = 'enupal-paypal/orders/edit/{id}';

        $variables['settings'] = Paypal::$app->settings->getSettings();

        return $this->renderTemplate('enupal-paypal/orders/_edit', $variables);
    }

    /**
     *  Delete a Order.
     *
     * @return \yii\web\Response
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\db\Exception
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionDeleteOrder()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $orderId = $request->getRequiredBodyParam('orderId');
        $order = Paypal::$app->orders->getOrderById($orderId);

        // @TODO - handle errors
        Paypal::$app->orders->deleteOrder($order);

        return $this->redirectToPostedUrl($order);
    }
}
