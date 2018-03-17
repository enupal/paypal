<?php
/**
 * EnupalPaypal plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\paypal\variables;

use Craft;
use enupal\paypal\PaypalButtons;

/**
 * EnupalPaypal provides an API for accessing information about sliders. It is accessible from templates via `craft.enupalbackup`.
 *
 */
class BackupVariable
{

    /**
     * @return string
     */
    public function getName()
    {
        $plugin = Craft::$app->plugins->getPlugin('enupal-paypal');

        return $plugin->getName();
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        $plugin = Craft::$app->plugins->getPlugin('enupal-paypal');

        return $plugin->getVersion();
    }

    /**
     * @return mixed
     */
    public function getFtpTypes()
    {
        $options = [
            'ftp' => 'FTP',
            'sftp' => 'SFTP'
        ];

        return $options;
    }

    /**
     * @return string
     */
    public function getSettings()
    {
        return PaypalButtons::$app->settings->getSettings();
    }

    /**
     * @return string
     */
    public function getSizeFormatted($size)
    {
        return PaypalButtons::$app->backups->getSizeFormatted($size);
    }

    /**
     * @return string
     */
    public function getAllPlugins()
    {
        return PaypalButtons::$app->settings->getAllPlugins();
    }

    /**
     * @return string
     */
    public function getAllLocalVolumes()
    {
        return PaypalButtons::$app->settings->getAllLocalVolumes();
    }

    /**
     * @return string
     */
    public function getSecretKey()
    {
        return PaypalButtons::$app->backups->getRandomStr();
    }
}

