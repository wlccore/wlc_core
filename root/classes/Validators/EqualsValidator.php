<?php

namespace eGamings\WLC\Validators;

/**
 * @class EqualsValidator
 */
class EqualsValidator extends AbstractValidator
{
    public function validate($value, $params, $data, $field)
    {
        $toValue = array_key_exists($params, $data) ? $data[$params] : '';
        
        return $value == $toValue;
    }
}
