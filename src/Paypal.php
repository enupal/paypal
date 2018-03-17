<?php
/**
 * EnupalPaypal plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\paypal;

use Craft;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;
use craft\events\DefineComponentsEvent;
use craft\web\twig\variables\CraftVariable;
use craft\services\SystemMessages;
use craft\events\RegisterEmailMessagesEvent;

use enupal\paypal\variables\BackupVariable;
use enupal\paypal\models\Settings;

class Paypal extends \craft\base\Plugin
{
    /**
     * Enable use of PaypalButton::$app-> in place of Craft::$app->
     *
     * @var [type]
     */
    public static $app;

    public $hasCpSection = true;
    public $hasCpSettings = true;
    public $schemaVersion = '1.0.0';

    public function init()
    {
        parent::init();
        self::$app = $this->get('app');

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, $this->getCpUrlRules());
        }
        );

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, $this->getSiteUrlRules());
        }
        );

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function(Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('enupalbackup', BackupVariable::class);
            }
        );

        Event::on(
            SystemMessages::class,
            SystemMessages::EVENT_REGISTER_MESSAGES,
            function(RegisterEmailMessagesEvent $event) {
                array_push($event->messages,
                    [
                        'key' => 'enupal_backup_notification',
                        'subject' => 'PaypalButton process completed',
                        'body' => 'We are happy to inform you that the backup process has been completed. PaypalButton Id: {{backup.backupId}}'
                    ]
                );
            }
        );
    }

    protected function afterInstall()
    {
        self::$app->backups->installDefaultValues();
    }

    /**
     * Performs actions before the plugin is Uninstalled.
     *
     * @return bool Whether the plugin should be Uninstalled
     */
    protected function beforeUninstall(): bool
    {
        $backups = self::$app->backups->getAllBackups();

        foreach ($backups as $key => $backup) {
            Craft::$app->elements->deleteElementById($backup->id);
        }

        return true;
    }

    protected function createSettingsModel()
    {
        return new Settings();
    }

    public function getCpNavItem()
    {
        $parent = parent::getCpNavItem();
        return array_merge($parent, [
            'subnav' => [
                'backups' => [
                    "label" => PaypalButtons::t("Backups"),
                    "url" => 'enupal-paypal/backups'
                ],
                'settings' => [
                    "label" => PaypalButtons::t("Settings"),
                    "url" => 'enupal-paypal/settings'
                ]
            ]
        ]);
    }

    /**
     * Settings HTML
     *
     * @return string
     */
    protected function settingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('enupal-paypal/settings/index');
    }

    /**
     * @param string $message
     * @param array  $params
     *
     * @return string
     */
    public static function t($message, array $params = [])
    {
        return Craft::t('enupal-paypal', $message, $params);
    }

    public static function log($message, $type = 'info')
    {
        Craft::$type(self::t($message), __METHOD__);
    }

    public static function info($message)
    {
        Craft::info(self::t($message), __METHOD__);
    }

    public static function error($message)
    {
        Craft::error(self::t($message), __METHOD__);
    }

    /**
     * @return array
     */
    private function getCpUrlRules()
    {
        return [
            'enupal-paypal/run' =>
                'enupal-paypal/backups/run',

            'enupal-paypal/backup/new' =>
                'enupal-paypal/backups/edit-backup',

            'enupal-paypal/backup/view/<backupId:\d+>' =>
                'enupal-paypal/backups/view-backup',
        ];
    }

    /**
     * @return array
     */
    private function getSiteUrlRules()
    {
        return [
            'enupal-paypal/finished' =>
                'enupal-paypal/webhook/finished',

            'enupal-paypal/schedule' =>
                'enupal-paypal/webhook/schedule'
        ];
    }
}

