<?php
namespace eGamings\WLC\Validators;

use eGamings\WLC\User;

/**
 * @class EqualsValidator
 */
class PasswordLevelValidator extends AbstractValidator
{
    public function validate($value, $params, $data, $field)
    {
        if (empty($value)) {
            return true;
        }

        return User::getInstance()->checkPassword($value);
    }
}
