<?php

namespace eGamings\WLC\Validators;

/**
 * @class RequiredValidator
 */
class NameValidator extends AbstractValidator
{
    /**
     * @param $value
     * @param bool $params required or not
     * @param $data
     * @return bool
     */
    public function validate($value, $params, $data, $field)
    {
        if (!$params || empty($value)) {
            return true;
        }

        // Replace allowed symbols 
        $value = str_replace(['-', '.'], '', $value);
        return (preg_match("/[0-9]/", $value) || preg_match("/[[:punct:]]/", $value)) ? false : true;
    }
}
