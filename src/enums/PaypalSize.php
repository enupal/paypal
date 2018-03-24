<?php
/**
 * EnupalPaypal plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\paypal\enums;

/**
 * Paypal size button
 */
abstract class PaypalSize extends BaseEnum
{
    // Constants
    // =========================================================================
    const SMALL = 0;
    const BIG = 1;
    const BIGCC = 2;
    const GOLD = 3;
}
