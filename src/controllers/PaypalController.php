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

use enupal\paypal\contracts\PaypalIPN;
use yii\web\NotFoundHttpException;
use enupal\paypal\Paypal;

class PaypalController extends BaseController
{
    // Disable CSRF validation for the entire controller
    public $enableCsrfValidation = false;

    protected array|int|bool $allowAnonymous = true;

    /**
     * @return \yii\web\Response
     * @throws \Exception
     * @throws \Throwable
     */
    public function actionIpn()
    {
        if (isset($_POST)) {
            $settings = Paypal::$app->settings->getSettings();
            $ipn = new PaypalIPN();
            $ipn->usePHPCerts();

	        Craft::info("IPN Received:  ".json_encode($_POST), __METHOD__);

            if ($settings->testMode){
                $ipn->useSandbox();
            }

            if ($ipn->verifyIPN()) {
	            Craft::info("IPN validated", __METHOD__);
                $order = Paypal::$app->orders->populateOrder();

                if ($order->id) {
                    Craft::info('IPN already processed with order id: '.$order->id, __METHOD__);
                    return $this->asJson(['success' => 'true']);
                }

                $button = Paypal::$app->buttons->getButtonBySku($this->getValue('item_number'));

                if ($button){
                    $order->buttonId = $button->id;
                }

                // Stock
                $saveButton = false;
                if (!$button->hasUnlimitedStock && (int)$button->quantity > 0){
                    $button->quantity -= $order->quantity;
                    $saveButton = true;
                }

                if (!Paypal::$app->orders->saveOrder($order)){
                    Craft::error('Something went wrong saving the order: '.json_encode($order->getErrors()), __METHOD__);
                    return $this->asJson(['success' => 'false']);
                }

	            Craft::info("Paypal Order created id: ".$order->id, __METHOD__);
                // Let's update the stock
                if ($saveButton){
                    if (!Paypal::$app->buttons->saveButton($button)){
                        Craft::error('Something went wrong updating the button stock: '.json_encode($button->getErrors()), __METHOD__);
                        return $this->asJson(['success' => 'false']);
                    }
                }
            }else{
                Craft::error('PayPal fail to verifyIPN', __METHOD__);
                return $this->asJson(['success' => 'false']);
            }
        }

        return $this->asJson(['success' => 'true']);
    }

    /**
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws \yii\base\Exception
     */
    public function actionCompletePayment()
    {
        $txnId = Craft::$app->getRequest()->getParam('txn_id');
        $order = null;
        // By default return to home page
        $returnUrl = '/?order={number}';
        // Lets wait 10 seconds until IPN is done
        sleep(10);

        if ($txnId){
            $order = Paypal::$app->orders->getOrderByPaypalTransactionId($txnId);

            if (is_null($order)) {
                throw new NotFoundHttpException(Craft::t('enupal-paypal', 'Order does not exists'));
            }

            $button = $order->getButton();

            if ($button->returnUrl){
                $returnUrl = $button->returnUrl;
            }
        }

        $url = Craft::$app->getView()->renderObjectTemplate($returnUrl, $order);

        return $this->redirect($url);
    }

    /**
     * @param $key
     *
     * @return string|null
     */
    private function getValue($key)
    {
        if (!isset($_POST[$key])){
            return null;
        }

        return $_POST[$key];
    }

}
