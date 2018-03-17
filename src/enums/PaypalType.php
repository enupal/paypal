<?php
/**
 * EnupalPaypal plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\paypal\enums;

/**
 * Paypal type button
 */
abstract class PaypalType extends BaseEnum
{
    // Constants
    // =========================================================================
    const PAY = 0;
    const DONATION = 1;
}
