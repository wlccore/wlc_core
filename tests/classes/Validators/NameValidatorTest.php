<?php
namespace eGamings\WLC\Tests\Validators;

use eGamings\WLC\Tests\BaseCase;
use eGamings\WLC\Validators\NameValidator;

class NameValidatorTest extends BaseCase {
    public function testNameValidatorEmpty() {
        $rule = new NameValidator();

        $result = $rule->validate('', true, ['name' => ''], 'name');
        $this->assertTrue($result, 'Success with empty data values');
    }

    public function testNameValidator() {
        $rule = new NameValidator();
        
        $result = $rule->validate('test', true, ['name' => 'test'], 'name');
        $this->assertTrue($result, 'Name is correct');
    }

    public function testNameValidatorSpaces() {
        $rule = new NameValidator();
        
        $result = $rule->validate('test name', true, ['name' => 'test'], 'name');
        $this->assertTrue($result, 'Name is correct with space');
    }

    public function testNameValidatorUnicode() {
        $rule = new NameValidator();
        
        $result = $rule->validate('Тест-Test', true, ['name' => 'Тест-Test'], 'name');
        $this->assertTrue($result, 'Name is correct in unicode');
    }

    public function testNameValidatorFailure() {
        $rule = new NameValidator();
        
        $result = $rule->validate('test!', true, ['name' => 'test!'], 'name');
        $this->assertFalse($result, 'Name is incorrect');
    }

    public function testNameValidatorFailureDigits() {
        $rule = new NameValidator();
        
        $result = $rule->validate('test123', true, ['name' => 'test123'], 'name');
        $this->assertFalse($result, 'Name is incorrect digits');
    }

    public function testNameValidatorFailureUnicode() {
        $rule = new NameValidator();
        
        $result = $rule->validate('Тест!', true, ['name' => 'Тест!'], 'name');
        $this->assertFalse($result, 'Name is incorrect in unicode');
    }

    public function testNameValidatorFailureUnicodeDigits() {
        $rule = new NameValidator();
        
        $result = $rule->validate('Тест1', true, ['name' => 'Тест1'], 'name');
        $this->assertFalse($result, 'Name is incorrect in unicode digits');
    }
}
