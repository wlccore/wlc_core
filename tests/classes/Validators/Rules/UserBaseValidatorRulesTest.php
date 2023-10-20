<?php
namespace eGamings\WLC\Tests\Validators\Rules;

use eGamings\WLC\Tests\BaseCase;
use eGamings\WLC\Validators\Rules\UserBaseValidatorRules;
use eGamings\WLC\System;
use eGamings\WLC\Tests\SystemMock;


class UserBaseValidatorRulesTest extends BaseCase
{
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
    }

    public function setUp(): void {
        $reflectionSystem = new \ReflectionClass(System::class);
        $reflectionProperty = $reflectionSystem->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(new SystemMock());
    }

    public function tearDown(): void {
        $reflectionSystem = new \ReflectionClass(System::class);
        $reflectionProperty = $reflectionSystem->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(null);
    }

    private function getUserInfo() {
        return [
            "firstName" => "Name",
            "lastName" => "Lastname",
            "gender" => "M",
            "birthDay" => "4",
            "birthMonth" => "4",
            "birthYear" => "1996",
            "countryCode" => "",
            "phoneCode" => "",
            "phoneNumber" => "",
            "phoneAltCode" => "",
            "phoneAltNumber" => "",
            "postalCode" => "",
            "city" => "",
            "address" => "",
            "agreedWithTermsAndConditions" => false,
            "ageConfirmed" => false,
            "extProfile" => [],
            "registrationPromoCode" => "",
            "smsCode" => "",
            "email" => "user@egamings.com",
            "login" => "user",
            "pre_phone" => "",
            "main_phone" => "",
            "sex" => "M",
            "country" => ""
        ];
    }

    private function getValidateFields($loginBy, $data) {
        $rules = new UserBaseValidatorRules();
        _cfg('loginBy', $loginBy);
        return $rules->getValidateFields($data);
    }

    private function validate($data, $fields) {
        $validator = new UserBaseValidatorRules();
        return $validator->validate($data, $fields);
    }

    public function testGetValidateFieldsByLogin() {
        $data = ['email' => 'test@test.com'];
        $fields = $this->getValidateFields('login', $data);
        $this->assertTrue(!empty($fields['email']['validators']));
        $emailValidators = $fields['email']['validators'];

        $this->assertTrue(sizeof($emailValidators) == 2);
        $this->assertArrayHasKey('required', $emailValidators);
        $this->assertFalse($emailValidators['required']);
        $this->assertArrayHasKey('mail', $emailValidators);
        $this->assertFalse($emailValidators['mail']);
    }

    public function testGetValidateFieldsByEmail() {
        $data = ['email' => 'test@test.com'];
        $fields = $this->getValidateFields('email', $data);
        $this->assertTrue(!empty($fields['email']['validators']));
        $emailValidators = $fields['email']['validators'];

        $this->assertTrue(sizeof($emailValidators) == 2);
        $this->assertArrayHasKey('required', $emailValidators);
        $this->assertTrue($emailValidators['required']);
        $this->assertArrayHasKey('mail', $emailValidators);
        $this->assertTrue($emailValidators['mail']);
    }

    public function testGetValidateFieldsByAll() {
        $data = ['email' => 'test@test.com'];
        $fields = $this->getValidateFields('all', $data);

        $emailValidators = $fields['email']['validators'];

        $this->assertTrue(sizeof($emailValidators) == 2);
        $this->assertArrayHasKey('required', $emailValidators);
        $this->assertTrue($emailValidators['required']);
        $this->assertArrayHasKey('mail', $emailValidators);
        $this->assertTrue($emailValidators['mail']);
    }

    public function testValidateByEmail() {
        _cfg('loginBy', 'email');

        $data = $this->getUserInfo();
        $data['email']='';
        $data['password']='12345!@#$';

        $result = $this->validate($data, ['email']);

        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('email', $result['errors']);
        $this->assertFalse($result['result']);

        $data['email']='test@test.com';
        $result = $this->validate($data, ['email']);

        $this->assertEmpty($result['errors']);
        $this->assertTrue($result['result']);

        $data['email']='test.com';
        $result = $this->validate($data, ['email']);

        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('email', $result['errors']);
        $this->assertFalse($result['result']);
    }

    public function testValidateByLogin() {
        _cfg('loginBy', 'login');

        $data = $this->getUserInfo();
        $data['email']='';
        $data['password']='12345!@#$';

        $result = $this->validate($data, ['email']);
        $this->assertArrayNotHasKey('email', $result['errors']);
        $this->assertTrue($result['result']);
    }

    public function testValidateByAll() {
        _cfg('loginBy', 'all');

        $data = $this->getUserInfo();
        $data['email']='';
        $data['password']='12345!@#$';

        $result = $this->validate($data, ['email']);
        $this->assertArrayNotHasKey('email', $result['errors']);
        $this->assertTrue($result['result']);

        $data['email']='test.com';
        $result = $this->validate($data, ['email']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('email', $result['errors']);
        $this->assertFalse($result['result']);
    }

}
