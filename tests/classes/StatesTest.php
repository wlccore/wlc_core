<?php

namespace eGamings\WLC\Tests;

use eGamings\WLC\Core;
use eGamings\WLC\Cache;
use eGamings\WLC\States;
use eGamings\WLC\System;

/**
 * Class StatesTest
 * @package eGamings\WLC\Tests
 */
class StatesTest extends BaseCase
{
    public function setUp(): void {
        $reflectionCore = new \ReflectionClass(Core::class);
        $reflectionProperty = $reflectionCore->getProperty('_instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(new CoreMock());
        Cache::clearMiddleware();
    }

    public function tearDown(): void {
        $reflectionCore = new \ReflectionClass(Core::class);
        $reflectionProperty = $reflectionCore->getProperty('_instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(null);
    }

    public function testFetchStatesList() {
        $mock = $this->getMockBuilder(System::class)
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->disableOriginalConstructor()
            ->getMock();

        $reflectionSystem = new \ReflectionClass(System::class);
        $reflectionProperty = $reflectionSystem->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($mock);

        $mock
            ->expects($this->exactly(2))
            ->method('getApiTID')
            ->willReturn('test_123');

        $mock
            ->expects($this->exactly(2))
            ->method('runFundistAPI')
            ->willReturn('0,DummyFalse');

        $states = States::getStatesList('desc', 'en');
        $result = States::fetchStateList(true);

        $reflectionProperty->setValue(null);
        $this->assertFalse($result, 'States update must fail');
    }
}
