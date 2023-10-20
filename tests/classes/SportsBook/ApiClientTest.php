<?php
namespace eGamings\WLC\Tests\SportsBook;

use eGamings\WLC\Tests\BaseCase;
use eGamings\WLC\SportsBook\ApiClient;
use eGamings\WLC\SportsBook\ApiClientConfig;
use eGamings\WLC\SportsBook\ApiClientException;

class ApiClientTest extends BaseCase
{
    public function testConstructor()
    {
        $config = new ApiClientConfig();
        $client = new ApiClient($config);

        $this->assertEquals($this->invokeMethod($client, 'getConfig'), $config);
    }

    public function testBuildURI()
    {
        $config = new ApiClientConfig();
        $config->setURL('https://test.com');
        $client = new ApiClient($config);

        $this->assertEquals(
            $this->invokeMethod($client, 'buildURI', ['/abstract/path', ['a' => 1, 'b' => '2']]),
            'https://test.com/abstract/path?a=1&b=2'
        );

        $this->assertEquals(
            $this->invokeMethod($client, 'buildURI', ['/abstract/path']),
            'https://test.com/abstract/path'
        );
    }

    public function testGetWidget()
    {
        $config = new ApiClientConfig();
        $config->setURL('https://test.com')->setClientId(111);

        $mock = $this->getMockBuilder('eGamings\WLC\SportsBook\ApiClient')
            ->setMethods(['request'])
            ->setConstructorArgs([$config])
            ->getMock();

        $mock->expects($this->exactly(1))
            ->method('request')
            ->with(
                $this->equalTo('GET'),
                $this->equalTo($config->getEndPoint('widgets') . '/get/widget-type'),
                $this->equalTo([
                    'clientId' => 111,
                    'language' => 'ru',
                    'output' => 'html'
                ])
            )
            ->willReturn(true);

        $this->assertTrue($mock->getWidget('widget-type', 'ru', 'html'));
    }

    public function testExecuteRequest()
    {
        $config = new ApiClientConfig();

        $mock = $this->getMockBuilder('eGamings\WLC\SportsBook\ApiClient')
            ->setMethods(['getResponseCode'])
            ->setConstructorArgs([$config])
            ->getMock();

        $mock->expects($this->exactly(1))
            ->method('getResponseCode')
            ->willReturn(200);
        
        $this->assertFalse($this->invokeMethod($mock, 'executeRequest', [curl_init()]));
    }

    public function testExecuteRequestException()
    {
        $config = new ApiClientConfig();

        $mock = $this->getMockBuilder('eGamings\WLC\SportsBook\ApiClient')
            ->setMethods(['getResponseCode'])
            ->setConstructorArgs([$config])
            ->getMock();

        $mock->expects($this->exactly(1))
            ->method('getResponseCode')
            ->willReturn(400);
        
        $this->expectException(ApiClientException::class);
        $this->invokeMethod($mock, 'executeRequest', [curl_init()]);
    }

    public function testGetResponseCode()
    {
        $client = new ApiClient(new ApiClientConfig());

        $this->assertEquals($this->invokeMethod($client, 'getResponseCode', [curl_init()]), 0);
    }

    public function testRequest()
    {
        $config = new ApiClientConfig();

        $mock = $this->getMockBuilder('eGamings\WLC\SportsBook\ApiClient')
            ->setMethods(['executeRequest'])
            ->setConstructorArgs([$config])
            ->getMock();

        $mock->expects($this->exactly(2))
            ->method('executeRequest')
            ->willReturn('success');
        
        $this->assertEquals(
            $this->invokeMethod($mock, 'request', ['GET', '/test', []]),
            'success'
        );

        $this->assertEquals(
            $this->invokeMethod($mock, 'request', ['POST', '/test2', ['a' => 1]]),
            'success'
        );
    }
}
