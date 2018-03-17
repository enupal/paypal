<?php
/**
 * EnupalPaypal plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\paypal\controllers;

use Craft;
use craft\web\Controller as BaseController;

use enupal\paypal\PaypalButtons;

class WebhookController extends BaseController
{
    protected $allowAnonymous = ['actionFinished', 'actionSchedule'];

    /**
     * Webhook to listen when a backup process finish up
     *
     * @param $backupId
     *
     */
    public function actionFinished()
    {
        $backupId = Craft::$app->request->getParam('backupId');
        $backup = PaypalButtons::$app->backups->getBackupByBackupId($backupId);
        $settings = PaypalButtons::$app->settings->getSettings();
        PaypalButtons::info("Request to finish backup: ".$backupId);

        if ($backup) {
            // we could check just this backup but let's check all pending backups
            $pendingBackups = PaypalButtons::$app->backups->getPendingBackups();

            foreach ($pendingBackups as $key => $backup) {
                $result = PaypalButtons::$app->backups->updateBackupOnComplete($backup);
                // let's send a notification
                if ($result && $settings->enableNotification) {
                    PaypalButtons::$app->backups->sendNotification($backup);
                }

                PaypalButtons::info("EnupalPaypal: ".$backup->backupId." Status:".$backup->backupStatusId." (webhook)");
            }

            PaypalButtons::$app->backups->checkBackupsAmount();
            PaypalButtons::$app->backups->deleteConfigFile();
        } else {
            PaypalButtons::error("Unable to finish the webhook backup with id: ".$backupId);
        }

        return $this->asJson(['success' => true]);
    }

    /**
     * Webhook to listen when a cronjob call EnupalPaypal process
     *
     * @param $backupId
     *
     */
    public function actionSchedule()
    {
        $key = Craft::$app->request->getParam('key');
        $settings = PaypalButtons::$app->settings->getSettings();
        $response = [
            'success' => false
        ];

        if ($settings->enableWebhook) {
            if ($key == $settings->webhookSecretKey && $settings->webhookSecretKey) {
                $response = PaypalButtons::$app->backups->executeEnupalPaypal();
            } else {
                PaypalButtons::error("Wrong webhook key: ".$key);
            }
        } else {
            PaypalButtons::error("Webhook is disabled");
        }

        return $this->asJson($response);
    }
}
