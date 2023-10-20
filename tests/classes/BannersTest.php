<?php

namespace eGamings\WLC\Tests;

use eGamings\WLC\System;
use eGamings\WLC\Banners;

class BannersTest extends BaseCase
{
    public function testFetchBanners()
    {
        $mock = $this->getMockBuilder(System::class)->disableOriginalConstructor()
            ->setMethods(['getApiTID', 'runFundistAPI'])
            ->getMock();

        $iProp = (new \ReflectionClass(System::class))->getProperty('instance');
        $iProp->setAccessible(true);
        $iProp->setValue($mock);

        $mock->expects($this->exactly(1))->method('getApiTID')->willReturn('test_123');
        $mock->expects($this->exactly(1))->method('runFundistAPI')->willReturn('1,{"banner1": {}}');

        $this->assertIsArray(Banners::fetchBanners(), 'Check that result is array');
        $iProp->setValue(null);
    }
}
