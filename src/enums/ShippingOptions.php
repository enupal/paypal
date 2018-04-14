<?php
/**
 * EnupalPaypal plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\paypal\enums;

/**
 * Paypal shipping options
 */
abstract class ShippingOptions extends BaseEnum
{
    // Constants
    // =========================================================================
    const PROMPT = 0;
    const DONOTPROMPT = 1;
    const PROMPTANDREQUIRE  = 2;
}
