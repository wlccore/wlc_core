<?php
namespace eGamings\WLC\Tests\Validators\Rules;

use eGamings\WLC\Validators\Rules\UserProfileValidatorRules;

class UserProfileValidatorRulesTest extends UserBaseValidatorRulesTest {
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
    }

    public function testGetValidateFields() {
        $rules = new UserProfileValidatorRules();

        $data = ['email' => 'test@test.com'];
        $fields = $rules->getValidateFields('all', $data);

        $this->assertFalse(empty($fields), 'Check rules fields are not empty');
    }

    public function testGetValidateFieldsCustomLowest() {
        global $cfg;
        $cfg['PasswordSecureLevel'] = 'custom:lowest';
        $rules = new UserProfileValidatorRules();

        $data = ['email' => 'test@test.com'];
        $fields = $rules->getValidateFields('all', $data);

        $this->assertFalse(empty($fields), 'Check rules fields are not empty');
    }
}
