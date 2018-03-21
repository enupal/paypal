<?php
/**
 * EnupalPaypal plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\paypal\services;

use craft\base\Component;

class App extends Component
{
    public $settings;
    public $buttons;

    public function init()
    {
        $this->settings = new Settings();
        $this->buttons = new Buttons();
    }
}