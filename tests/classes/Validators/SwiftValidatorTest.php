<?php

namespace eGamings\WLC\Tests\Validators;

use eGamings\WLC\Tests\BaseCase;
use eGamings\WLC\Validators\SwiftValidator;

class SwiftValidatorTest extends BaseCase
{
    public function testValidate()
    {
        $validator = new SwiftValidator();
        $data = ['Swift' => ''];
        $this->assertTrue($validator->validate('', $data['Swift'], $data, 'swift'));

        $data['Swift'] = 'qwe12';
        $this->assertFalse($validator->validate('', $data['Swift'], $data, 'swift'));

        $data['Swift'] = 'SABRRUMM';
        $this->assertTrue($validator->validate('', $data['Swift'], $data, 'swift'));

        $data['Swift'] = 'SABRRPMM';
        $this->assertFalse($validator->validate('', $data['Swift'], $data, 'swift'));

        $data['Swift'] = 'PUNB0333800';
        $this->assertFalse($validator->validate('', $data['Swift'], $data, 'swift'));

        $data['country'] = 'ind';
        $this->assertTrue($validator->validate('', $data['Swift'], $data, 'swift'));

        $data['Swift'] = 'SABRRUMM';
        $this->assertFalse($validator->validate('', $data['Swift'], $data, 'swift'));
    }

}
