<?php
namespace eGamings\WLC\Validators;

use eGamings\WLC\Db;

class UniquephoneValidator extends AbstractValidator
{
    public function validate($value, $params, $data, $field)
    {
        if (empty($data['phoneCode'])) {
            return false;
        }

        $phoneCode = Db::escape($data['phoneCode']);
        $value = Db::escape($value);

        if (_cfg('REGISTER_UNIQUE_PHONE_WHITE_LIST')) {
            $phoneWhiteList = explode(',', _cfg('REGISTER_UNIQUE_PHONE_WHITE_LIST'));
            if (in_array($value, $phoneWhiteList)) {
                return true;
            }
        }

        $query = "SELECT id FROM `users` WHERE `phone1` = '{$phoneCode}' AND `phone2` = '{$value}' LIMIT 1";
        $result = Db::fetchRow($query);

        if ($result === false) {
            return true;
        }

        return false;
    }
}
