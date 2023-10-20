<?php

namespace eGamings\WLC\Validators;

/**
 * @class MinValidator
 */
class MinValidator extends AbstractValidator
{
    public function validate($value, $params, $data, $field)
    {
        if (empty($value)) {
            return true;
        }

        return $value >= $params;
    }
}
