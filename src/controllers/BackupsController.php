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
use yii\web\NotFoundHttpException;
use yii\base\Exception;
use yii\base\ErrorException;
use craft\helpers\FileHelper;
use mikehaertl\shellcommand\Command as ShellCommand;
use ZipArchive;

use enupal\paypal\enums\PaypalType;
use enupal\paypal\PaypalButtons;

class BackupsController extends BaseController
{
    /*
     * Download backup
    */
    public function actionDownload()
    {
        $this->requirePostRequest();
        $backupId = Craft::$app->getRequest()->getRequiredBodyParam('backupId');
        $type = Craft::$app->getRequest()->getRequiredBodyParam('type');
        $backup = PaypalButtons::$app->backups->getBackupById($backupId);

        if ($backup && $type) {
            $filePath = null;

            switch ($type) {
                case 'all':

                    $zipPath = Craft::$app->getPath()->getTempPath().DIRECTORY_SEPARATOR.$backup->backupId.'.zip';

                    if (is_file($zipPath)) {
                        try {
                            FileHelper::removeFile($zipPath);
                        } catch (ErrorException $e) {
                            PaypalButtons::error("Unable to delete the file \"{$zipPath}\": ".$e->getMessage());
                        }
                    }

                    $zip = new ZipArchive();

                    if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
                        throw new Exception('Cannot create zip at '.$zipPath);
                    }

                    if ($backup->getDatabaseFile()) {
                        $filename = pathinfo($backup->getDatabaseFile(), PATHINFO_BASENAME);

                        $zip->addFile($backup->getDatabaseFile(), $filename);
                    }

                    if ($backup->getTemplateFile()) {
                        $filename = pathinfo($backup->getTemplateFile(), PATHINFO_BASENAME);

                        $zip->addFile($backup->getTemplateFile(), $filename);
                    }

                    if ($backup->getAssetFile()) {
                        $filename = pathinfo($backup->getAssetFile(), PATHINFO_BASENAME);

                        $zip->addFile($backup->getAssetFile(), $filename);
                    }

                    if ($backup->getLogFile()) {
                        $filename = pathinfo($backup->getLogFile(), PATHINFO_BASENAME);

                        $zip->addFile($backup->getLogFile(), $filename);
                    }

                    $zip->close();

                    $filePath = $zipPath;
                    break;
                case 'database':
                    $filePath = $backup->getDatabaseFile();
                    break;
                case 'template':
                    $filePath = $backup->getTemplateFile();
                    break;
                case 'logs':
                    $filePath = $backup->getLogFile();
                    break;
                case 'asset':
                    $filePath = $backup->getAssetFile();
                    break;
            }

            if (!is_file($filePath)) {
                throw new NotFoundHttpException(PaypalButtons::t('Invalid backup name: {filename}', [
                    'filename' => $filePath
                ]));
            }
        } else {
            throw new NotFoundHttpException(PaypalButtons::t('Invalid backup parameters'));
        }

        // Ajax call from element index
        if (Craft::$app->request->getAcceptsJson()) {
            return $this->asJson([
                'backupFile' => $filePath
            ]);
        }

        return Craft::$app->getResponse()->sendFile($filePath);
    }

    public function actionRun()
    {
        $this->requirePostRequest();

        $response = PaypalButtons::$app->backups->executeEnupalPaypal();

        return $this->asJson($response);
    }

    /**
     * View a PaypalButton.
     *
     * @param int|null $backupId The backup's ID
     *
     * @throws HttpException
     * @throws Exception
     */
    public function actionViewBackup(int $backupId = null)
    {
        // Get the PaypalButton
        $backup = PaypalButtons::$app->backups->getBackupById($backupId);

        if (!$backup) {
            throw new NotFoundHttpException(PaypalButtons::t('PaypalButton not found'));
        }

        if ($backup->backupStatusId == PaypalType::RUNNING) {
            PaypalButtons::$app->backups->updateBackupOnComplete($backup);
            PaypalButtons::$app->backups->checkBackupsAmount();
        }

        if (!is_file($backup->getDatabaseFile())) {
            $backup->databaseFileName = null;
        }

        if (!is_file($backup->getTemplateFile())) {
            $backup->templateFileName = null;
        }

        if (!is_file($backup->getLogFile())) {
            $backup->logFileName = null;
        }

        if (!is_file($backup->getAssetFile())) {
            $backup->assetFileName = null;
        }

        $variables['backup'] = $backup;

        $logPath = PaypalButtons::$app->backups->getLogPath($backup->backupId);

        if (is_file($logPath)) {
            $log = file_get_contents($logPath);
            $variables['log'] = $log;
        }

        return $this->renderTemplate('enupal-paypal/backups/_viewBackup', $variables);
    }

    /**
     * Delete a backup.
     *
     * @return void
     */
    public function actionDeleteBackup()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $backupId = $request->getRequiredBodyParam('id');
        $backup = PaypalButtons::$app->backups->getBackupById($backupId);

        // @TODO - handle errors
        $success = PaypalButtons::$app->backups->deleteBackup($backup);

        if ($success) {
            Craft::$app->getSession()->setNotice(PaypalButtons::t('PaypalButton deleted.'));
        } else {
            Craft::$app->getSession()->setNotice(PaypalButtons::t('Couldn’t delete backup.'));
        }

        return $this->redirectToPostedUrl($backup);
    }
}
