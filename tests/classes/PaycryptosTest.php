<?php
namespace classes;

use eGamings\WLC\Paycryptos;
use eGamings\WLC\Tests\BaseCase;
use ReflectionClass;

class PaycryptosTest extends BaseCase
{
    public function tearDown(): void
    {
        $reflectionCore = new \ReflectionClass(Paycryptos::class);
        $reflectionProperty = $reflectionCore->getProperty('instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(null);
    }

    public function testGetInstance()
    {
        $paycryptos = Paycryptos::getInstance();

        $this->assertTrue(is_object($paycryptos) && $paycryptos instanceof Paycryptos, 'Check paycryptos object');
    }

    public function testSend()
    {
        $env = _cfg('env');
        _cfg('env', 'test');
        $apiUrl = 'test-api-url';

        $postRequest = Paycryptos::getInstance()->send($apiUrl, false);
        $this->assertEquals($postRequest, '1,success');
        $getRequest = Paycryptos::getInstance()->send($apiUrl, true);
        $this->assertEquals($getRequest, '1,success');

        _cfg('env', $env);
    }
}