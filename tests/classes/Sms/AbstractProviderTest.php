<?php
namespace eGamings\WLC\Tests\Sms;

use eGamings\WLC\Tests\BaseCase;
use eGamings\WLC\Sms\AbstractProvider;

class AbstractProviderMock extends AbstractProvider {
    function __construct(array $config) {
        parent::__construct($config);
    }

    public function SendOne($phoneNumber, $sender, $content, $phoneCode, $concatMsg = 0, $unicodeMsg = 1, $sendTime = null, $validity = null) {
        return false;
    }

    public function SendMultiple($sender, Array $content, $countryCode, $concatMsg = 0, $unicodeMsg = 1, $sendTime = null, $validity = null) {
        return false;
    }

    protected function parseResponse($response, $errno = 0, $error = null, $http_code = 200, $batch = false) {
        return false;
    }

    public function getSmsStatus($token) {
        $result = false;
        $tokenData = self::decodeToken($token);
        return false;
    }
}

class AbstractProviderTest extends BaseCase {
    public function testToken() {
        $privateKey = 'this_is_secret_key';
        $provider = new AbstractProviderMock([
            'privateKey' => $privateKey
        ]);

        $tokenData = 'this_is_token_data';
        $token = $provider->encodeToken($tokenData);
        $tokenResult = $provider->decodeToken($token);
        $this->assertEquals($tokenData, $tokenResult, 'Token result equals encoded data');
    }

    public function testTokenFailure() {
        $privateKey = 'this_is_secret_key';
        $provider = new AbstractProviderMock([
            'privateKey' => $privateKey
        ]);
        
        $tokenData = 'this_is_token_data';
        $token = $provider->encodeToken($tokenData) . '_failure';
        $tokenResult = $provider->decodeToken($token);
        $this->assertFalse($tokenResult, 'Token decode failure produces false');
    }

    public function testTokenArray() {
        $privateKey = 'this_is_secret_key';
        $provider = new AbstractProviderMock([
            'privateKey' => $privateKey
        ]);

        $tokenData = ['id' => 'this_is_token_data'];
        $token = $provider->encodeToken($tokenData);
        $tokenResult = $provider->decodeToken($token);
        $this->assertEquals($tokenResult, $tokenData, 'Token decode array equals');
    }

    public function testTokenArrayAuthenticated() {
        $privateKey = 'this_is_secret_key';
        $provider = new AbstractProviderMock([
            'privateKey' => $privateKey
        ]);

        $_SESSION['user']['id'] = 1;
        $tokenData = ['id' => 'this_is_token_data'];
        $token = $provider->encodeToken($tokenData);
        $tokenResult = $provider->decodeToken($token);
        $this->assertEquals($tokenResult, $tokenData + ['uid' => $_SESSION['user']['id']], 'Token decode array equals');
        unset($_SESSION['user']['id']);
    }

    
    public function testTokenArrayEncAnonymous() {
        $privateKey = 'this_is_secret_key';
        $provider = new AbstractProviderMock([
            'privateKey' => $privateKey
        ]);

        _cfg('smsUseEncryption', true);
        $tokenData = ['id' => 'this_is_token_data'];
        $token = $provider->encodeToken($tokenData);
        $tokenResult = $provider->decodeToken($token);
        $this->assertEquals($tokenResult, $tokenData, 'Token decode array equals');
        _cfg('smsUseEncryption', null);
    }

    public function testValidateSMS () {
        $privateKey = 'this_is_secret_key';
        $provider = new AbstractProviderMock([
            'privateKey' => $privateKey,
            'codeTTL' => 300,
        ]);
        $_SESSION['user']['id'] = 1;
        $tokenData = ['id' => 'this_is_token_data'];
        $token = $provider->encodeToken($tokenData);

        $result = $provider->ValidateSms('12345', $token);
        $this->assertFalse($result);
    }

    public function testCheckConfig() {
        $provider = new AbstractProviderMock([]);
        $result = $provider->CheckConfig();
        $this->assertFalse($result);
    }
}
