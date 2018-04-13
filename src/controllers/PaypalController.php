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
        $json = '{"mc_gross":"125.50","protection_eligibility":"Eligible","address_status":"confirmed","payer_id":"3RRMUYQU3XH6L","address_street":"1 Main St","payment_date":"17:39:33 Apr 12, 2018 PDT","payment_status":"Completed","charset":"windows-1252","address_zip":"95131","first_name":"test","option_selection1":"blue","option_selection2":"N\/A","mc_fee":"11.25","address_country_code":"US","address_name":"test buyer","notify_version":"3.9","custom":"","payer_status":"verified","business":"enupal-test@gmail.com","address_country":"United States","address_city":"San Jose","quantity":"15","verify_sign":"AKt2um9iXDK4KDM95I7LXEI3r-HRAs6.MHLtD2OatJdnMAnuQqRDwpJf","payer_email":"andrelopezd-buyer@gmail.com","option_name1":"Color","option_name2":"Version","txn_id":"37W56847TT276802M","payment_type":"instant","last_name":"buyer","address_state":"CA","receiver_email":"enupal-test@gmail.com","payment_fee":"11.25","shipping_discount":"0.00","insurance_amount":"0.00","receiver_id":"8DE7XRD6MFYUQ","txn_type":"web_accept","item_name":"Button 1","discount":"0.50","mc_currency":"USD","item_number":"button1","residence_country":"US","test_ipn":"1","shipping_method":"Default","transaction_subject":"","payment_gross":"377.50","shipping":"3.00","ipn_track_id":"21ead5e31e410","RESULTTTT":true}
';
        $_POST = json_decode($json, true);

        if (isset($_POST)) {
            $settings = Paypal::$app->settings->getSettings();
            $ipn = new PaypalIPN();
            $ipn->usePHPCerts();

            if ($settings->testMode){
                $ipn->useSandbox();
            }

            if ($ipn->verifyIPN() || true) {
                $order = Paypal::$app->orders->populateOrder();
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

                $receiverEmail = $this->getValue('receiver_email');
                $receiverId = $this->getValue('receiver_id');

                $result = ($settings->liveAccount ==  $receiverEmail || $settings->liveAccount != $receiverId);

                if ($order->testMode){
                    $result = ($settings->sandboxAccount ==  $receiverEmail || $settings->sandboxAccount == $receiverId);
                }

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
