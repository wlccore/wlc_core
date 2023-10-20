<?php
namespace eGamings\WLC\Tests\Sms;

use eGamings\WLC\Db;
use eGamings\WLC\Sms\Bulk360Provider;
use eGamings\WLC\Tests\BaseCase;
use eGamings\WLC\Sms\AbstractProvider;
use eGamings\WLC\Tests\DbConnectionMock;
use eGamings\WLC\Tests\DbMock;

class Bulk360ProviderMock extends Bulk360Provider
{
    function __construct(array $config)
    {
        parent::__construct($config);
    }
}

class Bulk360ProviderTest extends BaseCase {
    public function testSendOne() {
        $mock = $this->getMockBuilder(Bulk360Provider::class)
            ->setMethods(['SendRequest'])
            ->disableOriginalConstructor()
            ->getMock();
        $mock->expects($this->exactly(3))->method('SendRequest')->willReturn(
            ['status' => false, 'result' => false, 'code' => ''],
            ['status'=> true, 'result' => 'ASD', 'code' => '200'],
            ['status'=> true, 'result' => 'ASD', 'code' => '200']
        );

        $conn = $this->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();
        DbMock::setConnection($conn);

        $queryResult = 1;
        $conn
            ->expects($this->exactly(2))
            ->method('query')
            ->willReturn($queryResult);

        $res = $mock->SendOne('9111111111', 'TestSender', 'Test content', '7');
        $this->assertEquals($res, ['status' => false, 'result' => false]);

        $res = $mock->SendOne('9111111111', 'TestSender', 'Test content', '7');
        $result = [];
        $result['status'] = true;
        $result['token'] = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJwaG9uZU51bWJlciI6IjkxMTExMTExMTEiLCJwaG9uZUNvZGUiOiI3IiwibXNnaWQiOiJBU0QiLCJjb2RlIjpudWxsLCJ1aWQiOjF9.SmdWRDeioO5gXZysV24og7X5KDYhkacMsAkuzU6C_So';
        $this->assertEquals($res, $result);

        $res = $mock->SendOne('9111111111', 'TestSender', 'Test content', '7', 1, 0);
        $result = [];
        $result['status'] = true;
        $result['token'] = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJwaG9uZU51bWJlciI6IjkxMTExMTExMTEiLCJwaG9uZUNvZGUiOiI3IiwibXNnaWQiOiJBU0QiLCJjb2RlIjpudWxsLCJ1aWQiOjF9.SmdWRDeioO5gXZysV24og7X5KDYhkacMsAkuzU6C_So';
        $this->assertEquals($res, $result);
    }

    public function testSendMultiple()
    {
        $provider = new Bulk360Provider([]);
        $res = $provider->SendMultiple('TestSender', [], '7');
        $this->assertFalse($res);
    }

    public function testGetSmsStatus()
    {
        $provider = new Bulk360Provider([
            'privateKey' => 'this_is_secret_key'
        ]);

        $res = $provider->getSmsStatus('');
        $this->assertFalse($res);

        $tokenData = [
            "phoneNumber" => "9111111111",
            "phoneCode" => "7",
            "msgid" => "12ewrw",
            "code" => null,
        ];
        $token = $provider->encodeToken($tokenData);

        $res = $provider->getSmsStatus($token);
        $this->assertFalse($res);

        $conn = $this->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();

        DbMock::setConnection($conn);

        $queryResult = $this->getMockBuilder('stdClass')->setMethods(['fetch_object', 'free'])->getMock();
        $queryResult->num_rows = 1;

        $fetchResult1 = new \stdClass();
        $fetchResult1->status = Bulk360Provider::SUCCES_CODE;
        $fetchResult2 = new \stdClass();
        $fetchResult2->status = Bulk360Provider::MISSING_FIELDS_CODE;
        $fetchResult3 = new \stdClass();
        $fetchResult3->status = Bulk360Provider::WRONG_CONFIG_DATA_CODE;
        $fetchResult4 = new \stdClass();
        $fetchResult4->status = Bulk360Provider::SUSPENDED_ACCOUNT_CODE;
        $fetchResult5 = new \stdClass();
        $fetchResult5->status = Bulk360Provider::API_NOT_ENABLED_ERROR;

        $queryResult->fetch_object = function() use ($fetchResult1) {
            return $fetchResult1;
        };

        $conn
            ->expects($this->exactly(5))
            ->method('query')
            ->willReturn($queryResult);

        $queryResult
            ->expects($this->exactly(5))
            ->method('fetch_object')
            ->willReturn($fetchResult1, $fetchResult2, $fetchResult3, $fetchResult4,  $fetchResult5);

        $queryResult
            ->expects($this->exactly(5))
            ->method('free')
            ->willReturn(true);

        $res = $provider->getSmsStatus($token);
        $this->assertEquals($res, AbstractProvider::$STATE_DELIVERED);
        $res = $provider->getSmsStatus($token);
        $this->assertEquals($res, AbstractProvider::$STATE_Error);
        $res = $provider->getSmsStatus($token);
        $this->assertEquals($res, AbstractProvider::$STATE_Error);
        $res = $provider->getSmsStatus($token);
        $this->assertEquals($res, AbstractProvider::$STATE_FAILED);
        $res = $provider->getSmsStatus($token);
        $this->assertEquals($res, AbstractProvider::$STATE_FAILED);

    }

    public function testSendRequest()
    {
        $object = new Bulk360Provider([]);
        $res = $this->invokeMethod($object, 'SendRequest', []);
        $this->assertEquals($res, ['result' => false, 'code' => '']);
    }

    public function testParseResponse()
    {
        $object = new Bulk360Provider([]);
        $data = [
            'ref' => 'Test',
            'code' => '200'
        ];

        $result = $this->invokeMethod($object, 'parseResponse', [json_encode($data)]);
        $this->assertEquals($result, ['result' => 'Test', 'code' => '200']);
    }
   

}
