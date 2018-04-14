<?php
/**
 * EnupalPaypal plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal
 */

namespace enupal\paypal\validators;

use enupal\paypal\enums\DiscountType;
use yii\validators\Validator;
use enupal\paypal\Paypal;

class DiscountValidator extends Validator
{
    public $skipOnEmpty = false;

    /**
     * Ftp validation
     *
     * @param $object
     * @param $attribute
     */
    public function validateAttribute($object, $attribute)
    {
        if ($object->discountType == DiscountType::RATE && $object->discount) {
            if ($object->discount <= 0 || $object->discount > 100){
                $this->addError($object, $attribute, Paypal::t('Discount need to have a value between >0 and 100'));
            }
        }

        if ($object->discountType == DiscountType::AMOUNT && $object->discount) {
            if ($object->discount < 0){
                $this->addError($object, $attribute, Paypal::t('Discount amount should be > 0'));
            }
        }
    }
}
