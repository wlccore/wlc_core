<?php
namespace eGamings\WLC;

use eGamings\WLC\System;
use eGamings\WLC\User;

class FundistEmailTemplate {

    private $system;

    public function __construct() {
        $this->system = System::getInstance();
    }

    public function sendRegistrationUrl($data, $url) {
        $transactionId = $this->system->getApiTID($url);
        $completeRegstrationUrl = _cfg('completeRegstrationUrl') ?: '';
        $data['reg_lang'] = !empty($data['reg_lang']) ? $data['reg_lang'] : _cfg('language'); 
        $completeRegstrationUrl = str_replace('%language%', $data['reg_lang'], $completeRegstrationUrl);
        $redirectUrl = $data['reg_site'] . '/'.$completeRegstrationUrl.'?message=COMPLETE_REGISTRATION&code='. $data['code'];
        $hash = md5('WLCAccount/SendMail/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . $data['email'] . '/' . _cfg('fundistApiPass'));
        $params = Array(
            'Url' => $redirectUrl,
            'first_name' => $data['firstName'],
            'last_name' => $data['lastName'],
            'email' => $data['email'],
            'currency' => $data['currency'],
            'TID' => $transactionId,
            'Hash' => $hash,
            'UserIP' => $data['reg_ip'],
            'password' => base64_encode($data['password']),
        );
        
        $url .= '&' . http_build_query($params);
        
        $response = $this->system->runFundistAPI($url);
        $response = explode(',', $response);
        if ($response[0] != 1) {
            User::logRegistrationMailError($data, $response);
            return '0;'. $response[1];
        }
        return true;
    }

    public function sendTrustDeviceConfirmationEmail(array $data): bool
    {
        $url = '/WLCAccount/SendMail/ConfirmationTrustDevice?';
        $transactionId = $this->system->getApiTID($url);
        $hash = md5('WLCAccount/SendMail/0.0.0.0/' . $transactionId . '/' . _cfg('fundistApiKey') . '/' . ($data['email'] ?? 'no@email.com') . '/' . _cfg('fundistApiPass'));
        $params = array_merge([
            'TID' => $transactionId,
            'Hash' => $hash,
        ], $data);

        $url .= '&' . http_build_query($params);

        $response = $this->system->runFundistAPI($url . '&' . http_build_query($params));
        $response = explode(',', $response, 2);

        if ($response[0] != 1) {
            Logger::log(sprintf("Sending a confirmation message has failed: %s", $response[1]), 'error', $data);
            return false;
        }

        return true;
    }

    public function sendRegistration($data) {
        return $this->sendRegistrationUrl($data, '/WLCAccount/SendMail/Registration?');
    }

    public function sendRegistrationReminder($data) {
        return $this->sendRegistrationUrl($data, '/WLCAccount/SendMail/RegistrationReminder?');
    }
}
