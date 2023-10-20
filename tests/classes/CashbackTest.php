<?php

declare(strict_types=1);

namespace eGamings\WLC\Tests;

use eGamings\WLC\Cashback;
use eGamings\WLC\RestApi\ApiException;
use eGamings\WLC\User;
use eGamings\WLC\System;

class CashbackTest extends BaseCase
{
    /**
    * @var \PHPUnit\Framework\MockObject\MockObject
    */
    private $userMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->userMock = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->setMethods(['isUser'])
            ->getMock();

        $reflectionProperty = new \ReflectionProperty(User::class, '_instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->userMock);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $reflectionProperty = new \ReflectionProperty(User::class, '_instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(null);
    }

    public function testGetListForUser(): void
    {
        $mock = $this->getMockBuilder(System::class)->disableOriginalConstructor()
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->getMock();

        $iProp = (new \ReflectionClass(System::class))->getProperty('instance');
        $iProp->setAccessible(true);
        $iProp->setValue($mock);

        $this->userMock->userData = new \stdClass();
        $this->userMock->userData->id = 123;
        $this->userMock->userData->api_password = 'Test123!';

        $mock->expects($this->exactly(2))
            ->method('getApiTID')
            ->willReturn('test_123');

        $mock->expects($this->exactly(2))
            ->method('runFundistAPI')
            ->will($this->onConsecutiveCalls('1,[]', '1,['));

        $cashback = new Cashback();

        $this->assertTrue(is_array($cashback->getListForUser($this->userMock->userData)), 'Check that result is array');

        $this->expectException(ApiException::class);
        $cashback->getListForUser($this->userMock->userData);

        $iProp->setValue(null);
    }

    public function testPayForUser(): void
    {
        $mock = $this->getMockBuilder(System::class)->disableOriginalConstructor()
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->getMock();

        $iProp = (new \ReflectionClass(System::class))->getProperty('instance');
        $iProp->setAccessible(true);
        $iProp->setValue($mock);

        $this->userMock->userData = new \stdClass();
        $this->userMock->userData->id = 123;
        $this->userMock->userData->api_password = 'Test123!';

        $mock->expects($this->exactly(2))
            ->method('getApiTID')
            ->willReturn('test_123');

        $mock->expects($this->exactly(2))
            ->method('runFundistAPI')
            ->will($this->onConsecutiveCalls('1,[]', '123,Error'));

        $cashback = new Cashback();

        $this->assertTrue(is_array($cashback->payForUser($this->userMock->userData, 1)), 'Check that result is array');

        $this->expectException(ApiException::class);
        $cashback->payForUser($this->userMock->userData, 1);

        $iProp->setValue(null);
    }
}
