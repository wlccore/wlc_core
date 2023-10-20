<?php

namespace eGamings\WLC\Validators;

/**
 * @class RequiredValidator
 */
class BirthFormatValidator extends AbstractValidator
{
    /**
     * @param $value
     * @param bool $params required or not
     * @param $data
     * @return bool
     */
    public function validate($value, $params, $data, $field)
    {
        $checkVars = ['birthDay', 'birthMonth', 'birthYear'];
        $hasVars = [];
        foreach($checkVars as $checkVar) {
            if (empty($data[$checkVar]) || !intval($data[$checkVar])) {
                continue;
            }
            $hasVars[$checkVar] = true;
        }

        if (empty($hasVars) && $params === false) {
            return true;
        }

        if (count($hasVars) != count($checkVars)) {
            return false;
        }

        $birthDateString = "{$data['birthYear']}-{$data['birthMonth']}-{$data['birthDay']}";

        $dt = date_create($birthDateString);

        if ($dt && is_object($dt)) {
            return true;
        }

        return false;
    }
}
