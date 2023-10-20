<?php
namespace eGamings\WLC\Validators;

use eGamings\WLC\System;
use eGamings\WLC\User;

class IbanValidator extends AbstractValidator
{
    public function validate($value, $params, $data, $field)
    {
        if (isset($data['country']) && (strtolower($data['country']) == 'mng' || strtolower($data['country']) == 'mn')) {
            return true;
        }
        if ($data['Iban'] == '') {
            return true;
        }


        if (User::isAuthenticated()) {
            $User = User::getInstance();
            if (is_object($User->userData) && !is_null($User->userData->Iban) && $data['Iban'] == $User->userData->Iban) {
                return true;
            }
        } elseif (isset($data['oldIban']) && $data['oldIban'] == $data['Iban']) {
            return true;
        }

        $system = System::getInstance();
        $url = '/WLCClassifier/BankAccountNumberValidation/';
        $transactionId = $system->getApiTID($url);
        $hash = md5('WLCClassifier/BankAccountNumberValidation/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . _cfg('fundistApiPass'));
        $params = [
            'TID' => $transactionId,
            'Hash' => $hash,
        ];
        $url .= '?&' . http_build_query($params);
        $response = $system->runFundistAPI($url);
        $result = explode(',', $response, 2);
        if ($result[0] == '1') {
            $validationType = $result[1];
        } else {
            $validationType = 'none';
        }

        $iban = strtolower(str_replace(' ', '', $data['Iban']));

        switch ($validationType) {
            case 'none':
                return true;

            case 'numeric':
                return (bool)preg_match('/^[0-9]{0,30}$/', $iban);

            case 'alphanumeric':
                return (bool)preg_match('/^[0-9a-zA-Z]{0,30}$/', $iban);

            case 'iban':
                $Countries = ['al' => 28, 'ad' => 24, 'at' => 20, 'az' => 28, 'bh' => 22, 'be' => 16, 'ba' => 20, 'br' => 29, 'bg' => 22, 'cr' => 21, 'hr' => 21,
                    'cy' => 28, 'cz' => 24, 'dk' => 18, 'do' => 28, 'ee' => 20, 'fo' => 18, 'fi' => 18, 'fr' => 27, 'ge' => 22, 'de' => 22, 'gi' => 23, 'gr' => 27,
                    'gl' => 18, 'gt' => 28, 'hu' => 28, 'is' => 26, 'ie' => 22, 'il' => 23, 'it' => 27, 'jo' => 30, 'kz' => 20, 'kw' => 30, 'lv' => 21, 'lb' => 28,
                    'li' => 21, 'lt' => 20, 'lu' => 20, 'mk' => 19, 'mt' => 31, 'mr' => 27, 'mu' => 30, 'mc' => 27, 'md' => 24, 'me' => 22, 'nl' => 18, 'no' => 15,
                    'pk' => 24, 'ps' => 29, 'pl' => 28, 'pt' => 25, 'qa' => 29, 'ro' => 24, 'sm' => 27, 'sa' => 24, 'rs' => 22, 'sk' => 24, 'si' => 19, 'es' => 24,
                    'se' => 24, 'ch' => 21, 'tn' => 24, 'tr' => 26, 'ae' => 23, 'gb' => 22, 'vg' => 24];

                $Chars = ['a' => 10, 'b' => 11, 'c' => 12, 'd' => 13, 'e' => 14, 'f' => 15, 'g' => 16, 'h' => 17, 'i' => 18, 'j' => 19, 'k' => 20, 'l' => 21, 'm' => 22,
                    'n' => 23, 'o' => 24, 'p' => 25, 'q' => 26, 'r' => 27, 's' => 28, 't' => 29, 'u' => 30, 'v' => 31, 'w' => 32, 'x' => 33, 'y' => 34, 'z' => 35];

                if (strlen($iban) == $Countries[substr($iban, 0, 2)]) {
                    $MovedChar = substr($iban, 4) . substr($iban, 0, 4);
                    $MovedCharArray = str_split($MovedChar);
                    $NewString = "";

                    foreach ($MovedCharArray as $key => $value) {
                        if (!is_numeric($MovedCharArray[$key])) {
                            $MovedCharArray[$key] = $Chars[$MovedCharArray[$key]];
                        }
                        $NewString .= $MovedCharArray[$key];
                    }

                    if (bcmod($NewString, '97') == 1) {
                        return true;
                    }
                }
                return false;

            default:
                return true;
        }
    }
}
