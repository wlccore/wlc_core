<?php

namespace eGamings\WLC;

use eGamings\WLC\System;

class Fundist
{
    public static function userUpdate($data)
    {
        //Updating user in Fundist
        $url = '/User/Update/?&Login=' . (int)$data['id'];

        $system = System::getInstance();

        $transactionId = $system->getApiTID($url);

        $hash = md5('User/Update/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $data['id'] . '/' . $data['api_password'] . '/' . $data['currency'] . '/' . _cfg('fundistApiPass'));
        $params = Array(
            'Password' => $data['api_password'],
            'Currency' => $data['currency'],
            'Name' => $data['first_name'],
            'LastName' => $data['last_name'],
            'Phone' => $data['phone1'] . '-' . $data['phone2'],
            'Country' => $data['country'],
            'City' => (isset($data['city']) ? Db::escape($data['city']) : null),
            'Address' => (isset($data['address']) ? Db::escape($data['address']) : null),
            'Email' => $data['email'],
            'Gender' => $system->getFundistValue('Gender', $data['sex']),
            'AlternativePhone' => (isset($data['alternate_phone1']) && isset($data['alternate_phone2']) ? $data['alternate_phone1'] . '-' . $data['alternate_phone2'] : null),
            'DateOfBirth' => sprintf("%04d-%02d-%02d", (int)$data['birth_year'],
                (int)$data['birth_month'], (int)$data['birth_day']),
            'TID' => $transactionId,
            'Hash' => $hash,
            'UserIP' => System::getUserIP(),
            'AdditionalUserIP' => json_encode(System::getUserIP(true)),
        );

        if (isset($data['email_verified']) && $data['email_verified'] == 1) {
            $params['EmailVerified'] = 1;
            $params['EmailVerifiedStamp'] = strtotime($data['email_verified_datetime']);
        }

        if (isset($data['phone_verified']) && $data['phone_verified'] == 1) {
            $params['PhoneVerified'] = 1;
        }

        $url .= '&' . http_build_query($params);

        $response = $system->runFundistAPI($url);

        $brakedown = explode(',', $response);

        if ($brakedown[0] != 1) {
            return $response;
        }

        return true;
    }
}
