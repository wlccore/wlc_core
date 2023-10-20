<?php
namespace eGamings\WLC\Tests\Validators;

use eGamings\WLC\Tests\BaseCase;
use eGamings\WLC\Validators\BirthValidator;

class BirthValidatorTest extends BaseCase {
    public function testBirthValidatorEmptyValues() {
        $rule = new BirthValidator();

        $result = $rule->validate('', false, [], '');
        $this->assertTrue($result, 'Success with empty data values');

        $result = $rule->validate('', true, [], '');
        $this->assertFalse($result, 'Failure with empty data values and strict check');
    }

    public function testBirthValidatorEmptyDayMonthYear() {
        $rule = new BirthValidator();
        
        $result = $rule->validate('', false, ['birthDay' => 1], '');
        $this->assertFalse($result, 'Failure with empty year/month');

        $result = $rule->validate('', false, ['birthDay' => 1, 'birthMonth' => 1], '');
        $this->assertFalse($result, 'Failure with empty year');

        $result = $rule->validate('', false, ['birthYear' => 1, 'birthMonth' => 1], '');
        $this->assertFalse($result, 'Failure with empty day');
    }

    public function testBirthValidatorInvalidDayMonthYear() {
        $rule = new BirthValidator();
        
        $result = $rule->validate('', false, ['birthYear' => -1, 'birthMonth' => 1, 'birthDay' => 1], '');
        $this->assertFalse($result, 'Failure with invalid year');
        
        $result = $rule->validate('', false, ['birthYear' => 1900, 'birthMonth' => 13, 'birthDay' => 1], '');
        $this->assertFalse($result, 'Failure with invalid month');

        $result = $rule->validate('', false, ['birthYear' => 1900, 'birthMonth' => 12, 'birthDay' => 32], '');
        $this->assertFalse($result, 'Failure with empty invalid day');
    }
    
    public function testBirthValidatorLess18() {
        $rule = new BirthValidator();
        
        $result = $rule->validate('', false, ['birthDay' => 1, 'birthMonth' => 1, 'birthYear' => date("Y")], '');
        $this->assertFalse($result, 'Failure with less 18');
    }
    
    public function testBirthValidatorGreaterOrEqual18() {
        $rule = new BirthValidator();
        
        $result = $rule->validate('', false, ['birthDay' => date("d"), 'birthMonth' => date("m"), 'birthYear' => date("Y") - 18], '');
        $this->assertTrue($result, 'Success with greater 18');
    }
}
