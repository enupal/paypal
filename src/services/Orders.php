<?php
/**
 * EnupalPaypal plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\paypal\services;

use Craft;
use craft\mail\Message;
use enupal\paypal\elements\Order;
use enupal\paypal\enums\OrderStatus;
use yii\base\Component;
use enupal\paypal\Paypal;
use enupal\paypal\records\Order as OrderRecord;

class Orders extends Component
{
    /**
     * Returns a Order model if one is found in the database by id
     *
     * @param int $id
     * @param int $siteId
     *
     * @return null|\craft\base\ElementInterface
     */
    public function getOrderById(int $id, int $siteId = null)
    {
        $order = Craft::$app->getElements()->getElementById($id, Order::class, $siteId);

        return $order;
    }

    /**
     * Returns a Order model if one is found in the database by number
     *
     * @param string $number
     * @param int $siteId
     *
     * @return array|\craft\base\ElementInterface
     */
    public function getOrderByNumber($number, int $siteId = null)
    {
        $query = Order::find();
        $query->sku($number);
        $query->siteId($siteId);

        return $query->one();
    }

    /**
     * Returns all orders
     *
     * @return null|Order[]
     */
    public function getAllOrders()
    {
        $query = Order::find();

        return $query->all();
    }

    /**
     * @param $order Order
     *
     * @throws \Exception
     * @return bool
     * @throws \Throwable
     */
    public function saveOrder(Order $order)
    {
        if ($order->id) {
            $orderRecord = OrderRecord::findOne($order->id);

            if (!$orderRecord) {
                throw new \Exception(Paypal::t('No Order exists with the ID “{id}”', ['id' => $order->id]));
            }
        }

        if (!$order->validate()) {
            return false;
        }

        try {
            $transaction = Craft::$app->db->beginTransaction();
            if (Craft::$app->elements->saveElement($order)) {
                $transaction->commit();
            }
        } catch (\Exception $e) {
            $transaction->rollback();

            throw $e;
        }

        return true;
    }


    /**
     * @param Order $order
     *
     * @return bool
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\db\Exception
     */
    public function deleteOrder(Order $order)
    {
        $transaction = Craft::$app->db->beginTransaction();

        try {
            // Delete the Order Element
            $success = Craft::$app->elements->deleteElementById($order->id);

            if (!$success) {
                $transaction->rollback();
                Paypal::error("Couldn’t delete Paypal Order");

                return false;
            }

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollback();

            throw $e;
        }

        return true;
    }

    /**
     * Generate a random string, using a cryptographically secure
     * pseudorandom number generator (random_int)
     *
     * For PHP 7, random_int is a PHP core function
     * For PHP 5.x, depends on https://github.com/paragonie/random_compat
     *
     * @param int    $length   How many characters do we want?
     * @param string $keyspace A string of all possible characters
     *                         to select from
     *
     * @return string
     * @throws \Exception
     */
    public function getRandomStr($length = 12, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {
        $str = '';
        $max = mb_strlen($keyspace, '8bit') - 1;

        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[random_int(0, $max)];
        }
        return $str;
    }

    /**
     * @return array
     */
    public function getColorStatuses()
    {
        $colors = [
            OrderStatus::NEW => 'green',
            OrderStatus::SHIPPED => 'blue',
        ];

        return $colors;
    }

    /**
     * @param Order $order
     *
     * @return Order
     */
    public function populateButtonFromPost(Order $order)
    {
        $request = Craft::$app->getRequest();

        $postFields = $request->getBodyParam('fields');

        $order->setAttributes($postFields, false);

        return $order;
    }

    /**
     * @param Order $order
     *
     * @return bool
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function sendCustomerNotification(Order $order)
    {
        $settings = Paypal::$app->settings->getSettings();

        if ($settings->enableCustomerNotification){
            return false;
        }

        $message = new Message();

        $message->setFrom([$settings->customerNotificationSenderEmail => $settings->customerNotificationSenderName]);

        $view = Craft::$app->getView();

        $variables = [];

        $variables['order'] = $order;

        $subject = $view->renderString($settings->customerNotificationSubject, $variables);
        $textBody = $view->renderString("Thank you! your order number is: {{order.number}}", $variables);

        // @todo add support to users change the email

        $htmlBody = $view->renderTemplate('enupal-paypal/_emails/customer', $variables);
        $message->setSubject($subject);
        $message->setHtmlBody($htmlBody);
        $message->setTextBody($textBody);
        $message->setReplyTo($settings->customerNotificationReplyToEmail);

        $emails = explode(",", $settings->customerNotificationRecipients);

        $mailer = Craft::$app->getMailer();

        try {
            $result = $mailer->setTo($emails)->send($message);
        } catch (\Throwable $e) {
            Craft::$app->getErrorHandler()->logException($e);
        }

        if ($result) {
            Craft::info('Customer email sent succesfully',__METHOD__);
        } else {
            Craft::error('Unable to send customer email',__METHOD__);
        }

        return $result;
    }
}
