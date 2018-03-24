<?php
/**
 * EnupalPaypal plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\paypal\elements;

use Craft;
use craft\base\Element;
use craft\elements\db\ElementQueryInterface;
use yii\base\ErrorHandler;
use craft\helpers\UrlHelper;
use craft\elements\actions\Delete;

use enupal\paypal\elements\db\PaypalButtonsQuery;
use enupal\paypal\records\PaypalButton as PaypalButtonRecord;
use enupal\paypal\enums\PaypalSize;
use enupal\paypal\Paypal as PaypalPlugin;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

/**
 * Order represents a entry element.
 */
class Order extends Element
{
    // General - Properties
    // =========================================================================
    public $id;
    public $type;
    public $currency;
    public $amount;
    public $itemId;
    public $options;
    public $returnUrl;
    public $cancelURL;
    public $buttonName = 'Buy now';

    /**
     * Returns the field context this element's content uses.
     *
     * @access protected
     * @return string
     */
    public function getFieldContext(): string
    {
        return 'enupalPaypal:'.$this->id;
    }

    /**
     * Returns the element type name.
     *
     * @return string
     */
    public static function displayName(): string
    {
        return PaypalPlugin::t('Paypal Buttons');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle()
    {
        return 'buttons';
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function hasStatuses(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl()
    {
        return UrlHelper::cpUrl(
            'enupal-paypal/buttons/edit/'.$this->id
        );
    }

    /**
     * Use the name as the string representation.
     *
     * @return string
     */
    /** @noinspection PhpInconsistentReturnPointsInspection */
    public function __toString()
    {
        try {
            // @todo - For some reason the Title returns null possible Craft3 bug
            return $this->name;
        } catch (\Exception $e) {
            ErrorHandler::convertExceptionToError($e);
        }
    }

    /**
     * @inheritdoc
     *
     * @return PaypalButtonsQuery The newly created [[PaypalButtonsQuery]] instance.
     */
    public static function find(): ElementQueryInterface
    {
        return new PaypalButtonsQuery(get_called_class());
    }

    /**
     *
     * @return string|null
     */
    public function getStatus()
    {
        $statusId = $this->backupStatusId;

        $colors = PaypalPlugin::$app->backups->getColorStatuses();

        return $colors[$statusId];
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context = null): array
    {
        $sources = [
            [
                'key' => '*',
                'label' => PaypalPlugin::t('All Buttons'),
            ]
        ];

        $statuses = PaypalSize::getConstants();

        $colors = PaypalPlugin::$app->backups->getColorStatuses();

        $sources[] = ['heading' => PaypalPlugin::t("PaypalButton Status")];

        foreach ($statuses as $code => $status) {
            if ($status != PaypalSize::STARTED) {
                $key = 'backupStatusId:'.$status;
                $sources[] = [
                    'status' => $colors[$status],
                    'key' => $key,
                    'label' => ucwords(strtolower($code)),
                    'criteria' => ['backupStatusId' => $status]
                ];
            }
        }

        return $sources;
    }

    /**
     * @inheritdoc
     */
    protected static function defineActions(string $source = null): array
    {
        $actions = [];

        // Delete
        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Delete::class,
            'confirmationMessage' => PaypalPlugin::t('Are you sure you want to delete the selected buttons?'),
            'successMessage' => PaypalPlugin::t('Buttons deleted.'),
        ]);

        return $actions;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return ['backupId'];
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        $attributes = [
            'elements.dateCreated' => PaypalPlugin::t('Date Created')
        ];

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        $attributes['backupId'] = ['label' => PaypalPlugin::t('PaypalButton Id')];
        $attributes['size'] = ['label' => PaypalPlugin::t('Size')];
        $attributes['dateCreated'] = ['label' => PaypalPlugin::t('Date')];
        $attributes['status'] = ['label' => PaypalPlugin::t('Status')];

        return $attributes;
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = ['backupId', 'size', 'dateCreated', 'status'];

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'size':
                {
                    return $this->getTotalSize();
                }
            case 'status':
                {
                    $message = $this->backupStatusId == PaypalSize::STARTED ?
                        PaypalPlugin::t('Started') :
                        PaypalPlugin::t('Not defined');

                    $encryted = '&nbsp;<i class="fa fa-lock" aria-hidden="true"></i>';

                    if ($this->backupStatusId == PaypalSize::FINISHED) {
                        $message = '<i class="fa fa-check-square-o" aria-hidden="true"></i>';
                    } else if ($this->backupStatusId == PaypalSize::RUNNING) {
                        $message = '<i class="fa fa-circle-o-notch fa-spin fa fa-fw"></i><span class="sr-only">Loading...</span>';
                    } else if ($this->backupStatusId == PaypalSize::ERROR) {
                        $message = '<i class="fa fa-times" aria-hidden="true"></i>';
                    }

                    if ($this->isEncrypted) {
                        $message .= $encryted;
                    }

                    return $message;
                }
            case 'dateCreated':
                {
                    return $this->dateCreated->format("Y-m-d H:i");;
                }
        }

        return parent::tableAttributeHtml($attribute);
    }

    public function getDatabaseFile()
    {
        $base = PaypalPlugin::$app->backups->getDatabasePath();

        if (!$this->databaseFileName) {
            return null;
        }

        return $base.$this->databaseFileName;
    }

    public function getTemplateFile()
    {
        $base = PaypalPlugin::$app->backups->getTemplatesPath();

        if (!$this->templateFileName) {
            return null;
        }

        return $base.$this->templateFileName;
    }

    public function getLogFile()
    {
        $base = PaypalPlugin::$app->backups->getLogsPath();

        if (!$this->logFileName) {
            return null;
        }

        return $base.$this->logFileName;
    }

    public function getAssetFile()
    {
        $base = PaypalPlugin::$app->backups->getAssetsPath();

        if (!$this->assetFileName) {
            return null;
        }

        return $base.$this->assetFileName;
    }


    public function getTotalSize()
    {
        $total = 0;

        if ($this->assetSize) {
            $total += $this->assetSize;
        }

        if ($this->templateSize) {
            $total += $this->templateSize;
        }

        if ($this->databaseSize) {
            $total += $this->databaseSize;
        }

        if ($this->logSize) {
            $total += $this->logSize;
        }

        if ($total == 0) {
            return "";
        }

        return PaypalPlugin::$app->backups->getSizeFormatted($total);
    }

    /**
     * @inheritdoc
     * @throws Exception if reasons
     */
    public function afterSave(bool $isNew)
    {
        // Get the PaypalButton record
        if (!$isNew) {
            $record = PaypalButtonRecord::findOne($this->id);

            if (!$record) {
                throw new Exception('Invalid PaypalButton ID: '.$this->id);
            }
        } else {
            $record = new PaypalButtonRecord();
            $record->id = $this->id;
        }

        $record->backupId = $this->backupId;
        $record->time = $this->time;
        $record->databaseFileName = $this->databaseFileName;
        $record->databaseSize = $this->databaseSize;
        $record->assetFileName = $this->assetFileName;
        $record->assetSize = $this->assetSize;
        $record->templateFileName = $this->templateFileName;
        $record->templateSize = $this->templateSize;
        $record->logFileName = $this->logFileName;
        $record->logSize = $this->logSize;
        $record->backupStatusId = $this->backupStatusId;
        $record->aws = $this->aws;
        $record->dropbox = $this->dropbox;
        $record->rsync = $this->rsync;
        $record->ftp = $this->ftp;
        $record->softlayer = $this->softlayer;
        $record->isEncrypted = $this->isEncrypted;
        $record->logMessage = $this->logMessage;

        $record->save(false);

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete(): bool
    {
        // Let's delete all the info
        $files = [];
        $files[] = $this->getDatabaseFile();
        $files[] = $this->getTemplateFile();
        $files[] = $this->getAssetFile();
        $files[] = $this->getLogFile();
        $files[] = PaypalPlugin::$app->backups->getLogPath($this->backupId);

        foreach ($files as $file) {
            if ($file) {
                if (file_exists($file)) {
                    unlink($file);
                } else {
                    // File not found.
                    PaypalPlugin::error(PaypalPlugin::t('Unable to delete the file: '.$file));
                }
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'handle'], 'required'],
            [['name', 'handle'], 'string', 'max' => 255],
            [
                ['handle'],
                HandleValidator::class,
                'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']
            ],
            [['name', 'handle'], UniqueValidator::class, 'targetClass' => SliderRecord::class],
        ];
    }

    public function getStatusName()
    {
        $statuses = PaypalSize::getConstants();

        $statuses = array_flip($statuses);

        return ucwords(strtolower($statuses[$this->backupStatusId]));
    }
}