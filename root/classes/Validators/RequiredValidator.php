<?php

namespace eGamings\WLC\Validators;

/**
 * @class RequiredValidator
 */
class RequiredValidator extends AbstractValidator
{
    /**
     * @param $value
     * @param bool $params required or not
     * @param $data
     * @return bool
     */
    public function validate($value, $params, $data, $field)
    {
        if (!$params) {
            return true;
        }

        return !empty($value);
    }
}
