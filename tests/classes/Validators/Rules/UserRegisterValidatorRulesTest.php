<?php
namespace eGamings\WLC\Tests\Validators\Rules;

use eGamings\WLC\Validators\Rules\UserRegisterValidatorRules;
use eGamings\WLC\Validators\UniquephoneValidator;

class UserRegisterValidatorRulesTest extends UserBaseValidatorRulesTest {
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
    }

    public function testGetValidateFields() {
        global $cfg;
        $rules = new UserRegisterValidatorRules();

        $data = ['email' => 'test@test.com'];
        $fields = $rules->getValidateFields('all', $data);
        $this->assertFalse(empty($fields), 'Check rules fields are not empty');
        $this->assertFalse(empty($fields['password']['validators']['password-level']), 'Check password has password-level validation rule');
    }

    public function testLowestSecurePassword() {
        global $cfg;
        $rules = new UserRegisterValidatorRules();
        $cfg['PasswordSecureLevel'] = 'custom:lowest';

        $data = [
            'email' => 'test@test.com',
            'password' => 'fffff',
        ];
        $fields = $rules->getValidateFields('all', $data);
        $this->assertEquals(5, $fields['password']['validators']['size']);
        $cfg['PasswordSecureLevel'] = 'medium';

    }

    public function testPhoneUniqueValidator() {
        global $cfg;
        $cfg['REGISTER_UNIQUE_PHONE_WHITE_LIST'] = '20203020';
        $cfg['registerUniquePhone'] = true;
        $data = [
            'phoneCode' => '+371',
            'phoneNumber' => '20203020'
        ];

        $validator = new UniquephoneValidator;
        $result = $validator->validate('20203020', [], $data, '');

        $this->assertTrue($result);
    }
}
