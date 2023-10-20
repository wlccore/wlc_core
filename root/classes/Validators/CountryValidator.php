<?php
namespace eGamings\WLC\Validators;

use eGamings\WLC\Classifier;

/**
 * Class RangeValidator
 * @package eGamings\WLC\Validators
 */
class CountryValidator
{
    /**
     * @param $value
     * @param $params
     * @param $data
     * @return bool
     */
    public function validate($value, $params, $data, $field)
    {
        $countries = Classifier::getCountryCodes('asc', _cfg('language'));

        if (empty($value)) {
            return true;
        }

        return (is_array($countries) && !empty($countries)) ? in_array($value, $countries) : true;
    }
}
