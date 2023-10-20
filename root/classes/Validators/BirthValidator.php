<?php

namespace eGamings\WLC\Validators;

use eGamings\WLC\System;

/**
 * @class RequiredValidator
 */
class BirthValidator extends AbstractValidator
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
        $dtNow = date_create();
        $dt = date_create($birthDateString);
        if (!is_object($dtNow) || !is_object($dt)) {
            return false;
        }

        $dtDiff = $dtNow->diff($dt);

        $country = $data['country'] ?? $data['countryCode'] ?? null;
        $age = $this->getCountryAgeBanNumber($country);

        if (is_object($dtDiff) && $dtDiff->y >= $age) {
            return true;
        }

        return false;
    }

    public function getCountryAgeBanNumber(string $country = null): int
    {
        $countryAgeBan = _cfg('countryAgeBan');
        $userCountry = empty($country) ? System::getGeoData() : $country;

        if (isset($countryAgeBan[$userCountry])) {
            return $countryAgeBan[$userCountry];
        } else {
            return 18;
        }
    }
}
