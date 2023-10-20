<?php
namespace eGamings\WLC\Tests\Validators;

use eGamings\WLC\Tests\BaseCase;
use eGamings\WLC\Validators\UniqueValidator;

class UniqueValidatorTest extends BaseCase {
    public function testUniqueValidatorEmptyTable() {
        $rule = new UniqueValidator();

        $result = $rule->validate('test123', [], '', 'password');
        $this->assertTrue($result, 'Unique success with empty table');
    }

    public function testUniqueValidatorEmptyValue() {
        $rule = new UniqueValidator();
        
        $result = $rule->validate('', ['table' => 'users'], '', 'login');
        $this->assertTrue($result, 'Unique success with empty value');
    }
}
