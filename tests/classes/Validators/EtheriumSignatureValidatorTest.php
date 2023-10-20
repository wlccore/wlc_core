<?php

namespace eGamings\WLC\Tests\Validators;

use eGamings\WLC\Tests\BaseCase;
use eGamings\WLC\Validators\EtheriumSignatureValidator;

class EtheriumSignatureValidatorTest extends BaseCase
{
    public function testValidate()
    {
        $validator = new EtheriumSignatureValidator();
        $data = [
            'message' => 'I like signatures',
            'walletAddress' => '0x5a214a45585b336a776b62a3a61dbafd39f9fa2a',
            'signature' => '0xacb175089543ac060ed48c3e25ada5ffeed6f008da9eaca3806e4acb707b9481401409ae1f5f9f290f54f29684e7bac1d79b2964e0edcb7f083bacd5fc48882e1b',
        ];

        $this->assertTrue($validator->validate('', '', $data, ''));

        $data['signature'] = '0xacb175089543ac060ed48c3e25ada5ffeed6f008da9eaca3806e4acb707b9481401409ae1f5f9f290f54f29684e7bac1d79b2964e0edcb7f083bacd5fc48882e11';
        $this->assertFalse($validator->validate('', '', $data, ''));

        unset($data['walletAddress']);
        $this->assertFalse($validator->validate('', '', $data, ''));
    }
}
