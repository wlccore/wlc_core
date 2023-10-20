<?php

namespace eGamings\WLC\Validators;

/**
 * @class MailValidator
 */
class MailValidator extends AbstractValidator
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

        return (bool)filter_var($value, FILTER_VALIDATE_EMAIL);
    }
}
