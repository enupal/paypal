<?php
/**
 * EnupalPaypal plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\paypal\events;

use enupal\paypal\elements\Order;
use yii\base\Event;

/**
 * OrderCompleteEvent class.
 */
class OrderCompleteEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var Order
     */
    public $order;
}
