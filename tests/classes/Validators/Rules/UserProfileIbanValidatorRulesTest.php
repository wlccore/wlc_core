<?php
namespace eGamings\Tests\Validators\Rules;

use eGamings\WLC\System;
use eGamings\WLC\Tests\BaseCase;
use eGamings\WLC\User;
use eGamings\WLC\Validators\Rules\UserProfileIbanValidatorRules;
use ReflectionClass;

class UserProfileIbanValidatorRulesTest extends BaseCase
{
    public function testValidateOldValue()
    {
        $userMock = $this->getMockBuilder(User::class)
            ->setMethods(['isUser'])
            ->disableOriginalConstructor()
            ->getMock();

        $userReflectionBase = new ReflectionClass(User::class);
        $user = $userMock;
        $user->userData = new \stdClass();
        $user->userData->Iban = '222wwwweee111';
        $this->instanceProperty = $userReflectionBase->getProperty('_instance');
        $this->instanceProperty->setAccessible(true);
        $this->instanceProperty->setValue($user);
        $userMock->expects($this->any())->method('isUser')->willReturn(true);

        $data = [];
        $data['oldIban'] = '222wwwweee111';
        $data['Iban'] = '222wwwweee111';
        $validator = new UserProfileIbanValidatorRules();

        $result = $validator->validate($data);
        $this->assertEmpty($result['errors']);
        $this->assertTrue($result['result']);
    }

    public function testValidate()
    {
        $mock = $this->getMockBuilder(System::class)
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->disableOriginalConstructor()
            ->getMock();

        $reflectionSystem = new \ReflectionClass(System::class);
        $reflectionProperty = $reflectionSystem->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($mock);

        $mock->expects($this->any())->method('getApiTID')->willReturn('test_123');
        $mock->expects($this->any())->method('runFundistAPI')->willReturn(
            '0,zzzz',
            '1,iban',
            '1,iban',
            '1,none',
            '1,numeric',
            '1,alphanumeric',
            '1,blabla',
            '1,iban'
        );

        $data = [];
        $data['Iban'] = 'GB29 NWBK 6016 1331 9268 19 000 FFF';
        $validator = new UserProfileIbanValidatorRules();

        $result = $validator->validate($data);
        $this->assertEmpty($result['errors']);
        $this->assertTrue($result['result']);

        $result = $validator->validate($data);
        $this->assertArrayHasKey('errors', $result);
        $this->assertFalse($result['result']);

        $data['Iban'] = 'GB29 NWBK 6016 1331 9268 19';
        $result = $validator->validate($data);
        $this->assertEmpty($result['errors']);
        $this->assertTrue($result['result']);

        $result = $validator->validate($data);
        $this->assertEmpty($result['errors']);
        $this->assertTrue($result['result']);

        $result = $validator->validate($data);
        $this->assertArrayHasKey('errors', $result);
        $this->assertFalse($result['result']);

        $result = $validator->validate($data);
        $this->assertEmpty($result['errors']);
        $this->assertTrue($result['result']);

        $result = $validator->validate($data);
        $this->assertEmpty($result['errors']);
        $this->assertTrue($result['result']);

        $data['Iban'] = 'GB29 NWBK 6016 1331 9268 19 000 FFF';
        $data['country'] = 'mng';
        $result = $validator->validate($data);
        $this->assertEmpty($result['errors']);
        $this->assertTrue($result['result']);
    }

}
