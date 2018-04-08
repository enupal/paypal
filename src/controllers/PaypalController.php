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
use enupal\paypal\Paypal;
use enupal\paypal\PaypalButtons;

class PaypalController extends BaseController
{
    // Disable CSRF validation for the entire controller
    public $enableCsrfValidation = false;

    protected $allowAnonymous = ['actionIpn'];

    /**
     * @throws \Exception
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

            if ($ipn->verifyIPN()) {
                $item_name = $_POST['item_name'];
                $item_number = $_POST['item_number'];
                $payment_status = $_POST['payment_status'];
                $payment_amount = $_POST['mc_gross'];
                $payment_currency = $_POST['mc_currency'];
                $txn_id = $_POST['txn_id'];
                $receiver_email = $_POST['receiver_email'];
                $payer_email = $_POST['payer_email'];
            }else{
                return $this->asJson(['success' => 'false']);
            }
        }

        return $this->asJson(['success' => 'true']);
    }

}
