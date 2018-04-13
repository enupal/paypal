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
use enupal\paypal\enums\OrderStatus;
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
        $json = '{"mc_gross":"802.50","protection_eligibility":"Eligible","address_status":"confirmed","payer_id":"3RRMUYQU3XH6L","address_street":"1 Main St","payment_date":"16:35:46 Apr 12, 2018 PDT","payment_status":"Completed","charset":"windows-1252","address_zip":"95131","first_name":"test","option_selection1":"blue","option_selection2":"v1","mc_fee":"23.57","address_country_code":"US","address_name":"test buyer","notify_version":"3.9","custom":"","payer_status":"verified","business":"enupal-test@gmail.com","address_country":"United States","address_city":"San Jose","quantity":"32","verify_sign":"AdN3sWBijt-05f2cZxWD3ItyAGQHAg-JsagTXx4TxGnx2dm37CVeJUVV","payer_email":"andrelopezd-buyer@gmail.com","option_name1":"Color","option_name2":"Version","txn_id":"6PP62924KE1237707","payment_type":"instant","last_name":"buyer","address_state":"CA","receiver_email":"enupal-test@gmail.com","payment_fee":"23.57","shipping_discount":"0.00","insurance_amount":"0.00","receiver_id":"8DE7XRD6MFYUQ","txn_type":"web_accept","item_name":"Button 1","discount":"0.50","mc_currency":"USD","item_number":"button1","residence_country":"US","test_ipn":"1","shipping_method":"Default","transaction_subject":"","payment_gross":"802.50","shipping":"3.00","ipn_track_id":"eb0cdf30c304d"}
';
        $_POST = json_decode($json, true);
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

                $order->orderStatusId = OrderStatus::NEW;
                $order->transactionInfo = json_encode($_POST);
                $order->number = Paypal::$app->orders->getRandomStr();
                $order->paypalTransactionId = $this->getValue('txn_id');
                $order->email = $this->getValue('payer_email');
                $order->firstName = $this->getValue('first_name');
                $order->lastName = $this->getValue('last_name');
                $order->totalPrice = $this->getValue('mc_gross');
                $order->currency = $this->getValue('mc_currency');
                $order->quantity = $this->getValue('quantity');
                $order->shipping = $this->getValue('shipping');
                $order->tax = $this->getValue('tax');
                $order->discount = $this->getValue('discount');
                // Shipping
                $order->addressCity = $this->getValue('address_city');
                $order->addressCountry = $this->getValue('address_country');
                $order->addressState = $this->getValue('address_state');
                $order->addressCountryCode = $this->getValue('address_country_code');
                $order->addressName = $this->getValue('address_name');
                $order->addressStreet = $this->getValue('address_street');
                $order->addressZip = $this->getValue('address_zip');
                $order->testMode = 0;
                // Variants
                $variants = [];
                $search = "option_selection";
                $search_length = strlen($search);
                $pos = 1;
                foreach ($_POST as $key => $value) {
                    if (substr($key, 0, $search_length) == $search) {
                        $name = $_POST['option_name'.$pos] ?? $pos;
                        $variants[$name] = $value;
                        $pos++;
                    }
                }

                $order->variants = json_encode($variants);

                // Stock
                $saveButton = false;
                if (!$button->hasUnlimitedStock && (int)$button->quantity > 0){
                    $button->quantity -= $order->quantity;
                    $saveButton = true;
                }

                if ($this->getValue('test_ipn')){
                    $order->testMode = 1;
                }

                $receiverEmail = $this->getValue('receiver_email');
                $receiverId = $this->getValue('receiver_id');

                $result = ($settings->liveAccount ==  $receiverEmail || $settings->liveAccount != $receiverId);

                if ($order->testMode){
                    $result = ($settings->sandboxAccount ==  $receiverEmail || $settings->sandboxAccount == $receiverId);
                }

                $order->transactionInfo = json_encode($_POST);

                if (!$result){
                    Craft::error('PayPal receiverEmail does not match', __METHOD__);
                    return $this->asJson(['success' => 'false']);
                }

                if (!Paypal::$app->orders->saveOrder($order)){
                    Craft::error('Something went wrong saving the order: '.json_encode($order->getErrors()), __METHOD__);
                    return $this->asJson(['success' => 'false']);
                }
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
