<?php
/**
 * EnupalPaypal plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\paypal\services;

use Craft;
use craft\base\Field;
use craft\fields\Matrix;
use craft\fields\PlainText;
use craft\fields\Table;
use enupal\paypal\elements\PaypalButton;
use enupal\paypal\enums\DiscountType;
use enupal\paypal\enums\ShippingOptions;
use yii\base\Component;
use enupal\paypal\Paypal;
use enupal\paypal\elements\PaypalButton as ButtonElement;
use enupal\paypal\records\PaypalButton as PaypalButtonRecord;
use enupal\paypal\enums\PaypalSize;

use yii\base\Exception;
use craft\models\MailSettings;
use craft\helpers\MailerHelper;

class Buttons extends Component
{
    protected $buttonRecord;

    const VARIANTS_PRICED_HANDLE = 'enupalPaypalPricedVariants';
    const VARIANTS_BASIC_HANDLE = 'enupalPaypalBasicVariants';

    /**
     * Constructor
     *
     * @param object $buttonRecord
     */
    public function __construct($buttonRecord = null)
    {
        $this->buttonRecord = $buttonRecord;

        if (is_null($this->buttonRecord)) {
            $this->buttonRecord = new PaypalButtonRecord();
        }
    }

    /**
     * Returns a PaypalButton model if one is found in the database by id
     *
     * @param int $id
     * @param int $siteId
     *
     * @return null|\craft\base\ElementInterface
     */
    public function getButtonById(int $id, int $siteId = null)
    {
        $button = Craft::$app->getElements()->getElementById($id, ButtonElement::class, $siteId);

        return $button;
    }

    /**
     * Returns a PaypalButton model if one is found in the database by sku
     *
     * @param string $sku
     * @param int $siteId
     *
     * @return null|\craft\base\ElementInterface
     */
    public function getButtonBySku($sku, int $siteId = null)
    {
        $query = ButtonElement::find();
        $query->sku($sku);
        $query->siteId($siteId);

        return $query->one();
    }

    /**
     * Returns all Buttons
     *
     * @return null|ButtonElement[]
     */
    public function getAllButtons()
    {
        $query = ButtonElement::find();

        return $query->all();
    }

    /**
     * @param $button ButtonElement
     *
     * @throws \Exception
     * @return bool
     * @throws \Throwable
     */
    public function saveButton(ButtonElement $button)
    {
        $isNewForm = true;
        if ($button->id) {
            $buttonRecord = PaypalButtonRecord::findOne($button->id);
            $isNewForm = false;

            if (!$buttonRecord) {
                throw new Exception(Paypal::t('No PaypalButton exists with the ID “{id}”', ['id' => $button->id]));
            }
        }

        if (!$button->validate()) {
            return false;
        }

        $transaction = Craft::$app->db->beginTransaction();

        try {
            // Set the field context
            Craft::$app->content->fieldContext = $button->getFieldContext();
            if ($isNewForm) {
                $fieldLayout = $button->getFieldLayout();

                // Save the field layout
                Craft::$app->getFields()->saveLayout($fieldLayout);

                // Assign our new layout id info to our form model and records
                $button->fieldLayoutId = $fieldLayout->id;
                $button->setFieldLayout($fieldLayout);
                $button->fieldLayoutId = $fieldLayout->id;
            }

            if (Craft::$app->elements->saveElement($button)) {
                $transaction->commit();
            }
        } catch (\Exception $e) {
            $transaction->rollback();

            throw $e;
        }

        return true;
    }

    /**
     * Enupal PaypalButton send notification service
     *
     * @param $button ButtonElement
     */
    public function sendNotification(ButtonElement $button)
    {
        $settings = new MailSettings();
        $buttonSettings = Paypal::$app->settings->getSettings();
        $templatePath = 'enupal-paypal/_notification/email';
        $emailSettings = Craft::$app->getSystemSettings()->getSettings('email');

        $settings->fromEmail = $buttonSettings->notificationSenderEmail;
        $settings->fromName = $buttonSettings->notificationSenderName;
        $settings->template = $templatePath;
        $settings->transportType = $emailSettings['transportType'];
        $settings->transportSettings = $emailSettings['transportSettings'];

        $mailer = MailerHelper::createMailer($settings);

        $emails = explode(",", $buttonSettings->notificationRecipients);

        try {
            $emailSent = $mailer
                ->composeFromKey('enupal_paypal_notification', ['button' => $button])
                ->setTo($emails)
                ->send();
        } catch (\Throwable $e) {
            Craft::$app->getErrorHandler()->logException($e);
            $emailSent = false;
        }

        if ($emailSent) {
            Paypal::info('Notification Email sent successfully!');
        } else {
            Paypal::error('There was an error sending the Notification email');
        }

        return $emailSent;
    }

    /**
     * Removes a Paypal Button
     *
     * @param ButtonElement $button
     *
     * @throws \CDbException
     * @throws \Exception
     * @return boolean
     * @throws \Throwable
     */
    public function deleteBackup(ButtonElement $button)
    {
        $transaction = Craft::$app->db->beginTransaction();

        try {
            // Delete the Element and PaypalButton
            $success = Craft::$app->elements->deleteElementById($button->id);

            if (!$success) {
                $transaction->rollback();
                Paypal::error("Couldn’t delete Paypal Button");

                return false;
            }

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollback();

            throw $e;
        }

        return true;
    }

    /**
     * Generate a random string, using a cryptographically secure
     * pseudorandom number generator (random_int)
     *
     * For PHP 7, random_int is a PHP core function
     * For PHP 5.x, depends on https://github.com/paragonie/random_compat
     *
     * @param int    $length   How many characters do we want?
     * @param string $keyspace A string of all possible characters
     *                         to select from
     *
     * @return string
     * @throws \Exception
     */
    public function getRandomStr($length = 10, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {
        $str = '';
        $max = mb_strlen($keyspace, '8bit') - 1;

        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[random_int(0, $max)];
        }
        return $str;
    }

    /**
     * @return array
     */
    public function getColorStatuses()
    {
        $colors = [
            PaypalSize::PAY => 'green',
            PaypalSize::DONATION => 'blue',
        ];

        return $colors;
    }

    /**
     * @return bool|string
     */
    public function getEnupalPaypalPath()
    {
        $defaultTemplate = Craft::getAlias('@enupal/paypal/templates/_frontend/');

        return $defaultTemplate;
    }

    /**
     * @param ButtonElement $button
     *
     * @return ButtonElement
     */
    public function populateButtonFromPost(ButtonElement $button)
    {
        $request = Craft::$app->getRequest();

        $postFields = $request->getBodyParam('fields');

        $button->setAttributes($postFields, false);

        return $button;
    }

    /**
     * @return array
     */
    public function getCurrencies()
    {
        $currencies = [];
        $currencies['USD'] = 'U.S. Dollar - USD';
        $currencies['AUD'] = 'Australian Dollar - AUD';
        $currencies['BRL'] = 'Brazilian Real - BRL';
        $currencies['CAD'] = 'Canadian Dollar - CAD';
        $currencies['CZK'] = 'Czech Koruna - CZK';
        $currencies['DKK'] = 'Danish Krone - DKK';
        $currencies['EUR'] = 'Euro - EUR';
        $currencies['HKD'] = 'Hong Kong Dollar - HKD';
        $currencies['HUF'] = 'Hungarian Forint - HUF';
        $currencies['ILS'] = 'Israeli New Sheqel - ILS';
        $currencies['JPY'] = 'Japanese Yen - JPY';
        $currencies['MYR'] = 'Malaysian Ringgit - MYR';
        $currencies['MXN'] = 'Mexican Peso - MXN';
        $currencies['NOK'] = 'Norwegian Krone - NOK';
        $currencies['NZD'] = 'New Zealand Dollar - NZD';
        $currencies['PHP'] = 'Philippine Peso - PHP';
        $currencies['PLN'] = 'Polish Zloty - PLN';
        $currencies['GBP'] = 'Pound Sterling - GBP';
        $currencies['RUB'] = 'Russian Ruble - RUB';
        $currencies['SGD'] = 'Singapore Dollar - SGD';
        $currencies['SEK'] = 'Swedish Krona - SEK';
        $currencies['CHF'] = 'Swiss Franc - CHF';
        $currencies['TWD'] = 'Taiwan New Dollar - TWD';
        $currencies['THB'] = 'Thai Baht - THB';
        $currencies['TRY'] = 'Turkish Lira - TRY';

        return $currencies;
    }

    /**
     * @return array
     */
    public function getIsoCurrencies()
    {
        $currencies = [];
        $currencies['USD'] = 'USD';
        $currencies['AUD'] = 'AUD';
        $currencies['BRL'] = 'BRL';
        $currencies['CAD'] = 'CAD';
        $currencies['CZK'] = 'CZK';
        $currencies['DKK'] = 'DKK';
        $currencies['EUR'] = 'EUR';
        $currencies['HKD'] = 'HKD';
        $currencies['HUF'] = 'HUF';
        $currencies['ILS'] = 'ILS';
        $currencies['JPY'] = 'JPY';
        $currencies['MYR'] = 'MYR';
        $currencies['MXN'] = 'MXN';
        $currencies['NOK'] = 'NOK';
        $currencies['NZD'] = 'NZD';
        $currencies['PHP'] = 'PHP';
        $currencies['PLN'] = 'PLN';
        $currencies['GBP'] = 'GBP';
        $currencies['RUB'] = 'RUB';
        $currencies['SGD'] = 'SGD';
        $currencies['SEK'] = 'SEK';
        $currencies['CHF'] = 'CHF';
        $currencies['TWD'] = 'TWD';
        $currencies['THB'] = 'THB';
        $currencies['TRY'] = 'TRY';

        return $currencies;
    }

    /**
     * @return array
     */
    public function getLanguageOptions()
    {
        $languages = [];
        $languages['en_US'] = 'English';
        $languages['en_GB'] = 'English - UK';
        $languages['da_DK'] = 'Danish';
        $languages['nl_NL'] = 'Dutch';
        $languages['fr_CA'] = 'French';
        $languages['de_DE'] = 'German';
        $languages['he_IL'] = 'Hebrew';
        $languages['it_IT'] = 'Italian';
        $languages['ja_JP'] = 'Japanese';
        $languages['no_NO'] = 'Norwgian';
        $languages['pl_PL'] = 'Polish';
        $languages['pt_BR'] = 'Portuguese';
        $languages['ru_RU'] = 'Russian';
        $languages['es_ES'] = 'Spanish';
        $languages['sv_SE'] = 'Swedish';
        $languages['zh_CN'] = 'Simplified Chinese -China only';
        $languages['zh_HK'] = 'Traditional Chinese - Hong Kong only';
        $languages['zh_TW'] = 'Traditional Chinese - Taiwan only';
        $languages['tr_TR'] = 'Turkish';
        $languages['th_TH'] = 'Thai';

        return $languages;
    }

    /**
     * @return array
     */
    public function getSizeOptions()
    {
        $sizes = [];
        $sizes[PaypalSize::BUYSMALL] = Paypal::t('Buy Now - Small');
        $sizes[PaypalSize::BUYBIG] = Paypal::t('Buy Now - Big');
        $sizes[PaypalSize::BUYBIGCC] = Paypal::t('Buy Now - Big with Credit Cards');
        $sizes[PaypalSize::BUYGOLD] = Paypal::t('Buy Now - Gold (English Only)');
        $sizes[PaypalSize::PAYSMALL] = Paypal::t('Pay Now - Small');
        $sizes[PaypalSize::PAYBIG] = Paypal::t('Pay Now - Big');
        $sizes[PaypalSize::PAYBIGCC] = Paypal::t('Pay Now - Big with Credit Cards');

        return $sizes;
    }

    /**
     * @param null $name
     * @param null $handle
     *
     * @return ButtonElement
     * @throws \Exception
     * @throws \Throwable
     */
    public function createNewButton($name = null, $handle = null): ButtonElement
    {
        $button = new ButtonElement();
        $name = empty($name) ? 'Button' : $name;
        $handle = empty($handle) ? 'button' : $handle;

        $settings = Paypal::$app->settings->getSettings();

        $button->name = $this->getFieldAsNew('name', $name);
        $button->sku = $this->getFieldAsNew('sku', $handle);
        $button->hasUnlimitedStock = 1;
        $button->shippingOption = 0;
        $button->customerQuantity = 0;
        $button->currency = $settings->defaultCurrency ? $settings->defaultCurrency : 'USD';
        $button->enabled = 1;
        $button->language = 'en_US';

        // Set default variant
        $button = $this->addDefaultVariant($button);

        $this->saveButton($button);

        return $button;
    }


    /**
     * This service allows add the variant to a PayPal button
     *
     * @param ButtonElement $button
     *
     * @return ButtonElement|null
     */
    public function addDefaultVariant(ButtonElement $button)
    {
        if (!$button) {
            return null;
        }

        $currentFieldContext = Craft::$app->getContent()->fieldContext;
        Craft::$app->getContent()->fieldContext = 'enupalPaypal:';

        $matrixPricedField = Craft::$app->fields->getFieldByHandle(self::VARIANTS_PRICED_HANDLE);
        $matrixBasicField = Craft::$app->fields->getFieldByHandle(self::VARIANTS_BASIC_HANDLE);
        // Give back the current field context
        Craft::$app->getContent()->fieldContext = $currentFieldContext;

        if (is_null($matrixPricedField) || is_null($matrixBasicField)){
            // Can't add variants to this button (Someone delete the fields)
            // Let's not throw an exception and just return the Button element with not variants
            Paypal::error("Can't add variants to PayPal Button");
            return $button;
        }

        // Create a tab
        $tabName = "Tab1";
        $requiredFields = [];
        $postedFieldLayout = [];

        // Add our variant fields
        if ($matrixPricedField !== null && $matrixPricedField->id != null) {
            $postedFieldLayout[$tabName][] = $matrixPricedField->id;
        }

        if ($matrixBasicField !== null && $matrixBasicField->id != null) {
            $postedFieldLayout[$tabName][] = $matrixBasicField->id;
        }

        // Set the field layout
        $fieldLayout = Craft::$app->fields->assembleLayout($postedFieldLayout, $requiredFields);

        $fieldLayout->type = PaypalButton::class;
        // Set the tab to the form
        $button->setFieldLayout($fieldLayout);

        return $button;
    }

    /**
     * Add the default two Matrix fields for variants
     * @throws \Throwable
     */
    public function createDefaultVariantFields()
    {
        $fieldsService = Craft::$app->getFields();

        $matrixSettings = [
            'minBlocks' => "",
            'maxBlocks' => 1,
            'blockTypes' => [
                'new1' => [
                    'name' => 'Priced Variants',
                    'handle' => 'variants',
                    'fields' => [
                        'new1' => [
                            'type' => PlainText::class,
                            'name' => 'Name',
                            'handle' => 'variantName',
                            'instructions' => '',
                            'required' => 1,
                            'typesettings' => '{"placeholder":"Size, Color, Version, etc..","code":"","multiline":"","initialRows":"4","charLimit":"","columnType":"text"}',
                            'translationMethod' => Field::TRANSLATION_METHOD_NONE,
                        ],
                        'new2' => [
                            'type' => Table::class,
                            'name' => 'Options',
                            'handle' => 'options',
                            'required' => '1',
                            'instructions' => 'If Name is Size you can fill this table with: Small          small           10',
                            'typesettings' => '{"addRowLabel":"Add new option","maxRows":"10","minRows":"1","columns":{"col1":{"heading":"Option Label","handle":"optionLabel","width":"40%","type":"singleline"},"col2":{"heading":"Handle (Unique)","handle":"handle","width":"40%","type":"singleline"},"col3":{"heading":"Price","handle":"price","width":"20%","type":"number"}},"defaults":{"row1":{"col1":"","col2":"","col3":""}},"columnType":"text"}',
                            'translationMethod' => Field::TRANSLATION_METHOD_NONE,
                        ],
                    ]
                ]
            ]
        ];

        // Our variant is a matrix field
        $matrixPriceField = $fieldsService->createField([
            'type' => Matrix::class,
            'name' => 'Variants with priced options',
            'context' => 'enupalPaypal:',
            'handle' => self::VARIANTS_PRICED_HANDLE,
            'settings' => json_encode($matrixSettings),
            'instructions' => '',
            'translationMethod' => Field::TRANSLATION_METHOD_NONE,
        ]);

        // Basic variant (no price)
        $matrixSettingsBasic = $matrixSettings;
        $matrixSettingsBasic['blockTypes']['new1']['fields']['new2']['typesettings'] = '{"addRowLabel":"Add new option","maxRows":"10","minRows":"1","columns":{"col1":{"heading":"Option Label","handle":"optionLabel","width":"40%","type":"singleline"},"col2":{"heading":"Handle (Unique)","handle":"handle","width":"40%","type":"singleline"}},"defaults":{"row1":{"col1":"","col2":""}},"columnType":"text"}';
        $matrixSettingsBasic['maxBlocks'] = 6;
        $matrixSettingsBasic['blockTypes']['new1']['name'] = 'Basic Variants';
        $matrixBasicField = $fieldsService->createField([
            'type' => Matrix::class,
            'name' => 'Variants',
            'handle' => self::VARIANTS_BASIC_HANDLE,
            'context' => 'enupalPaypal:',
            'settings' => json_encode($matrixSettingsBasic),
            'instructions' => '',
            'translationMethod' => Field::TRANSLATION_METHOD_NONE,
        ]);

        // Save our fields
        $currentFieldContext = Craft::$app->getContent()->fieldContext;
        Craft::$app->getContent()->fieldContext = 'enupalPaypal:';
        Craft::$app->fields->saveField($matrixPriceField);
        Craft::$app->fields->saveField($matrixBasicField);
        // Give back the current field context
        Craft::$app->getContent()->fieldContext = $currentFieldContext;
    }

    public function deleteVariantFields()
    {
        // Save our fields
        $currentFieldContext = Craft::$app->getContent()->fieldContext;
        Craft::$app->getContent()->fieldContext = 'enupalPaypal:';

        $matrixPricedField = Craft::$app->fields->getFieldByHandle(self::VARIANTS_PRICED_HANDLE);
        $matrixBasicField = Craft::$app->fields->getFieldByHandle(self::VARIANTS_BASIC_HANDLE);

        if ($matrixPricedField){
            Craft::$app->fields->deleteFieldById($matrixPricedField->id);
        }

        if ($matrixBasicField){
            Craft::$app->fields->deleteFieldById($matrixBasicField->id);
        }
        // Give back the current field context
        Craft::$app->getContent()->fieldContext = $currentFieldContext;
    }

    /**
     * Create a secuencial string for the "name" and "handle" fields if they are already taken
     *
     * @param string
     * @param string
     *
     * @return null|string
     */
    public function getFieldAsNew($field, $value)
    {
        $newField = null;
        $i = 1;
        $band = true;
        do {
            $newField = $field == "sku" ? $value.$i : $value." ".$i;
            $button = $this->getFieldValue($field, $newField);
            if (is_null($button)) {
                $band = false;
            }

            $i++;
        } while ($band);

        return $newField;
    }

    /**
     * Returns the value of a given field
     *
     * @param string $field
     * @param string $value
     *
     * @return PaypalButtonRecord
     */
    public function getFieldValue($field, $value)
    {
        $result = PaypalButtonRecord::findOne([$field => $value]);

        return $result;
    }

    /**
     * @param int    $buttonSize
     *
     * @param string $language
     *
     * @return string
     * @throws Exception
     */
    public function getButtonSizeUrl($buttonSize = 0, $language = 'en_ES')
    {
        $buttonUrl = '';
        $basseUrl = 'https://www.paypalobjects.com/{language}{extra}/i/btn/';
        $extra = '';

        if ($language == 'nl_NL'){
            $extra = '/NL/';
        }
        if ($language == 'ja_JP'){
            $extra = '/JP/';
        }

        switch ($buttonSize) {
            case PaypalSize::BUYSMALL:
                {
                    $buttonUrl = $basseUrl.'btn_buynow_SM.gif';
                    break;
                }
            case PaypalSize::BUYBIG:
                {
                    $buttonUrl = $basseUrl.'btn_buynow_LG.gif';
                    break;
                }
            case PaypalSize::BUYBIGCC:
                {
                    $buttonUrl =  $basseUrl.'btn_buynowCC_LG.gif';
                    break;
                }
            case PaypalSize::BUYGOLD:
                {
                    // just english
                    $buttonUrl = 'https://www.paypalobjects.com/webstatic/en_US/i/buttons/buy-logo-medium.png';
                    break;
                }
            case PaypalSize::PAYSMALL:
                {
                    $buttonUrl = $basseUrl.'btn_paynow_SM.gif';
                    break;
                }
            case PaypalSize::PAYBIG:
                {
                    $buttonUrl = $basseUrl.'btn_paynow_LG.gif';
                    break;
                }
            case PaypalSize::PAYBIGCC:
                {
                    $buttonUrl = $basseUrl.'btn_paynowCC_LG.gif';
                    break;
                }
        }

        $buttonUrl = Craft::$app->view->renderObjectTemplate($buttonUrl, [
            'language' => $language,
            'extra' => $extra
        ]);

        return $buttonUrl;
    }

    /**
     * @return array
     */
    public function getDiscountOptions()
    {
        $types = [];
        $types[DiscountType::RATE] = Paypal::t('Rate (%)');
        $types[DiscountType::AMOUNT] = Paypal::t('Amount');

        return $types;
    }

    /**
     * @return array
     */
    public function getShippingOptions()
    {
        $options = [];
        $options[ShippingOptions::PROMPT] = Paypal::t('Prompt for an address, but do not require one.');
        $options[ShippingOptions::DONOTPROMPT] = Paypal::t('Do not prompt for an address.');
        $options[ShippingOptions::PROMPTANDREQUIRE] = Paypal::t('Prompt for an address and require one.');

        return $options;
    }
}
