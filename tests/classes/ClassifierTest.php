<?php
namespace eGamings\WLC\Tests;

use eGamings\WLC\Core;
use eGamings\WLC\Cache;
use eGamings\WLC\System;
use eGamings\WLC\Classifier;
use eGamings\WLC\Tests\BaseCase;

class ClassifierTest extends BaseCase
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

    public function testFetchCountryList() {
        $mock = $this->getMockBuilder(System::class)
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->disableOriginalConstructor()
            ->getMock();

        $reflectionSystem = new \ReflectionClass(System::class);
        $reflectionProperty = $reflectionSystem->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($mock);

        $mock
            ->expects($this->exactly(1))
            ->method('getApiTID')
            ->willReturn('test_123');

        $mock
            ->expects($this->exactly(1))
            ->method('runFundistAPI')
            ->willReturn('0,DummyFalse');

        $result = Classifier::fetchCountryList(true);
        $reflectionProperty->setValue(null);
        $this->assertFalse($result, 'Country update must fail');
    }

    public function testGetCountryListCodesAsc() {
        $countries = Classifier::getCountryCodes('asc');
        $this->assertTrue(is_array($countries), 'CountryList must be array');
        $this->assertEquals($countries[0], 'afg', 'First country code must be afg');
    }

    public function testGetCountryListDesc() {
        $countries = Classifier::getCountryCodes('desc');
        $this->assertTrue(is_array($countries), 'CountryList must be array');
        $this->assertEquals($countries[0], 'zwe', 'First country code must be zwe');
    }
}
