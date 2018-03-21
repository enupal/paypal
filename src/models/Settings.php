<?php
/**
 * EnupalPaypal plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\paypal\models;

use craft\base\Model;

class Settings extends Model
{
    // General
    public $paypalEmail = '';
    public $returnUrl = '';
    public $cancelUrL = '';
    public $testMode = 0;
    public $defaultCurrency = '';

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['paypalEmail'], ['required', 'email']]
        ];
    }
}