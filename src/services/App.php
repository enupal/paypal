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
    public $backups;
    public $settings;

    public function init()
    {
        $this->backups = new Backups();
        $this->settings = new Settings();
    }
}