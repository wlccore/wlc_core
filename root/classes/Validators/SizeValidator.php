<?php

namespace eGamings\WLC\Validators;

/**
 * @class SizeValidator
 */
class SizeValidator extends AbstractValidator
{
    public function validate($value, $params, $data, $field)
    {
        if (empty($value)) {
            return true;
        }

        return strlen($value) >= $params;
    }
}
