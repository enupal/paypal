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
use enupal\paypal\elements\Order;
use enupal\paypal\Paypal;

class PaypalController extends BaseController
{
    // Disable CSRF validation for the entire controller
    public $enableCsrfValidation = false;

    protected $allowAnonymous = ['actionIpn'];

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

            if ($settings->testMode){
                $ipn->useSandbox();
            }

            //@todo remove test
            if ($ipn->verifyIPN() || true) {
                $order = new Order();
                $button = Paypal::$app->buttons->getButtonBySku($this->getValue('item_number'));
                if ($button){
                    $order->buttonId = $button->id;
                }

                $order->transactionInfo = json_encode($_POST);
                $order->number = Paypal::$app->orders->getRandomStr();
                $order->paypalTransactionId = $this->getValue('txn_id');
                $order->email = $this->getValue('payer_email');
                $order->firstName = $this->getValue('first_name');
                $order->lastName = $this->getValue('last_name');
                $order->total = $this->getValue('mc_gross');
                $order->currency = $this->getValue('mc_currency');
                $order->quantity = $this->getValue('quantity');
                $order->shipping = $this->getValue('shipping');
                $order->tax = $this->getValue('tax');
                //Shipping
                $order->addressCity = $this->getValue('address_city');
                $order->addressCountry = $this->getValue('address_country');
                $order->addressState = $this->getValue('address_state');
                $order->addressCountryCode = $this->getValue('address_country_code');
                $order->addressName = $this->getValue('address_name');
                $order->addressStreet = $this->getValue('address_street');
                $order->addressZip = $this->getValue('address_zip');

                if ($this->getValue('test_ipn')){
                    $order->testMode = 1;
                }

                $receiverEmail = $this->getValue('receiver_email');
                $receiverId = $this->getValue('receiver_id');

                if (($settings->liveAccount !=  $receiverEmail || $settings->liveAccount != $receiverId) || ($settings->sandboxAccount !=  $receiverEmail || $settings->sandboxAccount != $receiverId)){
                    Craft::error('PayPal receiverEmail does not match', __METHOD__);
                    return $this->asJson(['success' => 'false']);
                }

                if (!Paypal::$app->orders->saveOrder($order)){
                    Craft::error('Something went wrong saving the order: '.json_encode($order->getErrors()), __METHOD__);
                    return $this->asJson(['success' => 'false']);

                }
            }else{
                Craft::error('PayPal fail to verifyIPN', __METHOD__);
                return $this->asJson(['success' => 'false']);
            }
        }

        return $this->asJson(['success' => 'true']);
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
