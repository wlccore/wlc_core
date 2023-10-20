<?php
namespace eGamings\WLC\Tests;

use eGamings\WLC\HotEvents;
use eGamings\WLC\User;
use eGamings\WLC\System;
use eGamings\WLC\Tests\BaseCase;

class HotEventsTest extends BaseCase
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

    public function testGetEvents(): void
    {
        $mock = $this->getMockBuilder(System::class)->disableOriginalConstructor()
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->getMock();

        $iProp = (new \ReflectionClass(System::class))->getProperty('instance');
        $iProp->setAccessible(true);
        $iProp->setValue($mock);

        $this->userMock->expects($this->any())->method('isUser')->will($this->onConsecutiveCalls(true));
        $this->userMock->userData = new \stdClass();
        $this->userMock->userData->currency = 'EUR';

        $mock->expects($this->exactly(2))->method('getApiTID')->willReturn('test_123');
        $mock->expects($this->exactly(2))->method('runFundistAPI')->will($this->onConsecutiveCalls('1,[]', '1,['));

        $hotEvent = new HotEvents();
        $this->assertTrue(is_array($hotEvent->getEvents('merchantName', 'en')), 'Check that result is array');
        
        $this->assertNull($hotEvent->getEvents('merchantName', 'en'), 'Check that result is null, bad json');
        $iProp->setValue(null);
    }
}
