<?php
namespace eGamings\WLC\Tests\Validators;

use eGamings\WLC\Tests\BaseCase;
use eGamings\WLC\Validators\PasswordLevelValidator;
use eGamings\WLC\User;
use ReflectionClass;

class PasswordLevelValidatorTest extends BaseCase {
    private $instanceProperty = null;

    public function setUp(): void {
        $userReflectionBase = new ReflectionClass(User::class);
        $user = $userReflectionBase->newInstanceWithoutConstructor();
        $this->instanceProperty = $userReflectionBase->getProperty('_instance');
        $this->instanceProperty->setAccessible(true);
        $this->instanceProperty->setValue($user);
    }
    
    public function tearDown(): void {
        $this->instanceProperty->setValue(null);
    }
    
    public function testPasswordLevelLowCheck() {
        $rule = new PasswordLevelValidator();

        _cfg('PasswordSecureLevel', 'low');
        $result = $rule->validate('test123', [], '', 'password');

        $this->assertTrue($result, 'Password is valid for low level');
    }

    public function testPasswordLevelStrongCheck() {
        $rule = new PasswordLevelValidator();
        
        _cfg('PasswordSecureLevel', 'strong');

        $result = $rule->validate('test123', [], '', 'password');
        $this->assertFalse($result, 'Password is not valid for string level');

        $result = $rule->validate('Test123#', [], '', 'password');
        $this->assertTrue($result, 'Password is valid for string level');
        
    }

    public function testPasswordLevelSuperStrongCheck() {
        $rule = new PasswordLevelValidator();

        _cfg('PasswordSecureLevel', 'super-strong');

        $result = $rule->validate('test123$', [], '', 'password');
        $this->assertFalse($result, 'Password is not valid for string level');

        $result = $rule->validate('Test1234', [], '', 'password');
        $this->assertTrue($result, 'Password is valid for string level');

    }

    public function testPasswordLevelIncorrectCheck() {
        $rule = new PasswordLevelValidator();
        
        _cfg('PasswordSecureLevel', 'medium');
        
        $result = $rule->validate('test123', [], '', 'password');
        $this->assertTrue($result, 'Password is valid for default level');
    }
    
}
