<?php
namespace eGamings\WLC\Tests\Validators\Rules;

use eGamings\WLC\Validators\Rules\UserProfilePartialValidatorRules;
use eGamings\WLC\User;

class UserProfilePartialValidatorRulesTest extends UserBaseValidatorRulesTest {
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
    }

    public function testGetValidateFields() {
        $mockUser = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        $reflectionCache = new \ReflectionClass(User::class);
        $reflectionProperty = $reflectionCache->getProperty('_instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($mockUser);
        $mockUser->userData = new \stdClass();
        $mockUser->userData->id = 1;
        $_SESSION['user'] = ['id' => 1];

        $rules = new UserProfilePartialValidatorRules();

        $data = ['email' => 'test@test.com'];
        $fields = $rules->getValidateFields('all', $data);

        $reflectionProperty->setValue(null);
        unset($_SESSION['user']);
        
        $this->assertFalse(empty($fields), 'Check rules fields are not empty');
    }
}
