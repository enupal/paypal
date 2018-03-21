<?php
/**
 * EnupalPaypal plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\paypal\services;

use Craft;
use yii\base\Component;

class Settings extends Component
{

    /**
     * Saves Settings
     *
     * @param string $scenario
     * @param array  $postSettings
     *
     * @return bool
     */
    public function saveSettings(array $postSettings, string $scenario = null): bool
    {
        $backupPlugin = $this->getPlugin();

        $backupPlugin->getSettings()->setAttributes($postSettings, false);

        if ($scenario) {
            $backupPlugin->getSettings()->setScenario($scenario);
        }

        // Validate them, now that it's a model
        if ($backupPlugin->getSettings()->validate() === false) {
            return false;
        }

        $success = Craft::$app->getPlugins()->savePluginSettings($backupPlugin, $postSettings);

        return $success;
    }

    public function getSettings()
    {
        $backupPlugin = $this->getPlugin();

        return $backupPlugin->getSettings();
    }

    public function getPlugin()
    {
        return Craft::$app->getPlugins()->getPlugin('enupal-paypal');
    }
}
