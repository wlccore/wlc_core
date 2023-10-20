<?php
namespace eGamings\WLC\Validators;

use eGamings\WLC\System;
use eGamings\WLC\Front;
use eGamings\WLC\User;

/**
 * @class RequiredValidator
 */
class IdNumberValidator extends AbstractValidator
{
    /**
     * @codeCoverageIgnore
     * @param $value
     * @param bool $params required or not
     * @param $data
     * @return bool
     */
    public function validate($value, $params, $data, $field)
    {
        if (empty($value) || !$params) {
            return true;
        }

        $url = '/WLCAccount/IDNumber/Check?';

        $req = [
            'TID' => System::getInstance()->getApiTID($url),
            'IDNumber' => substr((string) $value, 0, 255),
        ];

        if (User::isAuthenticated()) {
            $req['Login'] = Front::User('id');
        }

        $hash = md5(implode('/', [
            'WLCAccount/IDNumber/Check/0.0.0.0', $req['TID'],
            _cfg('fundistApiKey'),
            $req['IDNumber'],
            _cfg('fundistApiPass')
        ]));
        $req['Hash'] = $hash;
        
        $url .= '&' . http_build_query($req);
        
        $resp = System::getInstance()->runFundistAPI($url);
        if (!$resp) {
            return false;
        }

        $result = explode(',', $resp, 2);
        if (empty($result) || count($result) != 2 || $result[0] !== '1' || $result[1] !== '0') {
            return false;
        }

        return true;
    }
}
