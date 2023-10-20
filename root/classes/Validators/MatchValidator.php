<?php

namespace eGamings\WLC\Validators;

/**
 * @class MatchValidator
 */
class MatchValidator extends AbstractValidator
{
    public function validate($value, $params, $data, $field)
    {
        if (empty($value)) {
            return true;
        }

        return preg_match($params, $value);
    }
}
