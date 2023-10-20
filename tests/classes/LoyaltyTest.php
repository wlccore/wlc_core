<?php
namespace eGamings\WLC\Tests;

use eGamings\WLC\Loyalty;
use eGamings\WLC\Tests\BaseCase;
use ReflectionClass;

class LoyaltyTest extends BaseCase
{
    public function setUp(): void {
    }

    public function tearDown(): void {
        $reflectionCore = new ReflectionClass(Loyalty::class);
        $reflectionProperty = $reflectionCore->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(null);
    }

    public function testGetInstance() {
        $loyalty = Loyalty::getInstance();
        $this->assertTrue(is_object($loyalty), 'Check loyalty object');
    }

    public function testSend() {
        $loyalty = Loyalty::getInstance();
        $result = $loyalty->send('http://localhost:8091/loylaty-test');
        $this->assertFalse($result);
    }

    public function testRequest() {
        $mock = $this->getMockBuilder(Loyalty::class)
            ->setMethods(['send'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $mock->expects($this->exactly(1))->method('send')->willReturn('{"data": true}');

        $reflectionCore = new ReflectionClass(Loyalty::class);
        $reflectionProperty = $reflectionCore->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($mock);

        $result = Loyalty::Request("test", []);
        $this->assertTrue(!empty($result), 'Check that result not empty');
        $this->assertTrue(is_array($result), 'Check that result is array');
        $this->assertTrue(!empty($result['data']), 'Check that result data not empty');
        $this->assertEquals(true, $result['data'], 'Check that result data is true');
    }

    public function testHashParamsString() {
        $hash = Loyalty::prepareHashParams('HashParamsString');
        $this->assertEquals($hash, '', 'Hash must be empty if string passed');
    }

    public function testHashParams() {
        $hash = Loyalty::prepareHashParams(['IDUser'=>1, 'Test'=>2]);
        $this->assertEquals($hash, 'IDUser=1/Test=2', 'Check correct hash creation');
    }

    public function testHashParamsNested() {
        $hashParams = [
            'IDUser'=>1,
            'Test'=> [
                "ID" => 1,
                "Demo" => true
            ]
        ];
        $hash = Loyalty::prepareHashParams($hashParams);
        $this->assertEquals($hash, 'IDUser=1/Test[Demo]=1/Test[ID]=1', 'Check correct hash creation');
    }
}
