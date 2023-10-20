<?php

namespace eGamings\Tests\Validators\Rules;

use eGamings\WLC\System;
use eGamings\WLC\Tests\BaseCase;
use eGamings\WLC\User;
use eGamings\WLC\Validators\Rules\EtheriumSignatureValidatorRules;
use eGamings\WLC\Validators\Rules\UserProfileIbanValidatorRules;
use ReflectionClass;

class EtheriumSignatureValidatorRulesTest extends BaseCase
{
    public function testValidate()
    {
        $validator = new EtheriumSignatureValidatorRules();
        $data = [
            'message' => 'I like signatures',
            'walletAddress' => '0x5a214a45585b336a776b62a3a61dbafd39f9fa2a',
            'signature' => '0xacb175089543ac060ed48c3e25ada5ffeed6f008da9eaca3806e4acb707b9481401409ae1f5f9f290f54f29684e7bac1d79b2964e0edcb7f083bacd5fc48882e1b',
        ];

        $result = $validator->validate($data);
        $this->assertEmpty($result['errors']);
        $this->assertTrue($result['result']);
    }
}
