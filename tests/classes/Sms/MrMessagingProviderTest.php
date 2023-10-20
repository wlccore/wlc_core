<?php
namespace eGamings\WLC\Tests\Sms;

use eGamings\WLC\Db;
use eGamings\WLC\Sms\MrMessagingProvider;
use eGamings\WLC\Tests\BaseCase;
use eGamings\WLC\Sms\AbstractProvider;
use eGamings\WLC\Tests\DbConnectionMock;
use eGamings\WLC\Tests\DbMock;

class  MrMessagingProviderMock extends MrMessagingProvider
{
    function __construct(array $config)
    {
        parent::__construct($config);
    }
}

class MrMessagingProviderTest extends BaseCase {

    public function testSetParams ()
    {
        $data = [
            'sender' => 'TestSender',
            'receiver' => '+79111111111',
            'message' => 'Test content',
        ];
        $config = [
            'username' => 'username',
            'password' => 'password',
            'srcton' => 1,
        ];
        $provider = new MrMessagingProvider($config);

        $reflection = new \ReflectionClass(get_class($provider));
        $method = $reflection->getMethod('setParams');
        $method->setAccessible(true);
        $res =  $method->invoke($provider, $data);
        $this->assertEquals($res, array_merge($config, $data));
    }

    public function testSendOne() {
        $mock = $this->getMockBuilder(MrMessagingProvider::class)
            ->setMethods(['SendRequest'])
            ->disableOriginalConstructor()
            ->getMock();
        $mock->expects($this->exactly(3))->method('SendRequest')->willReturn(
            ['status' => false, 'result' => false],
            ['status'=> true, 'result' => '12ewrw'],
            ['status'=> true, 'result' => '12ewrw']
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
        $result['token'] = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJwaG9uZU51bWJlciI6IjkxMTExMTExMTEiLCJwaG9uZUNvZGUiOiI3IiwibXNnaWQiOiIxMmV3cnciLCJjb2RlIjpudWxsLCJ1aWQiOjF9.yKhHtPjEnM_pDmVmzkZsb9z0fCBWp70Fg1OUXY_7Tos';
        $this->assertEquals($res, $result);

        $res = $mock->SendOne('9111111111', 'TestSender', 'Test content', '7', 1, 0);
        $result = [];
        $result['status'] = true;
        $result['token'] = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJwaG9uZU51bWJlciI6IjkxMTExMTExMTEiLCJwaG9uZUNvZGUiOiI3IiwibXNnaWQiOiIxMmV3cnciLCJjb2RlIjpudWxsLCJ1aWQiOjF9.yKhHtPjEnM_pDmVmzkZsb9z0fCBWp70Fg1OUXY_7Tos';
        $this->assertEquals($res, $result);
    }

    public function testSendMultiple()
    {
        $provider = new MrMessagingProvider([]);
        $res = $provider->SendMultiple('TestSender', [], '7');
        $this->assertFalse($res);
    }

    public function testGetSmsStatus()
    {
        $privateKey = 'this_is_secret_key';
        $provider = new MrMessagingProvider([
            'privateKey' => $privateKey
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
        $fetchResult1->status = 'DELIVRD';
        $fetchResult2 = new \stdClass();
        $fetchResult2->status = 'ACCEPTD';
        $fetchResult3 = new \stdClass();
        $fetchResult3->status = 'REJECTD';
        $fetchResult4 = new \stdClass();
        $fetchResult4->status = 'UNKNOWN';
        $fetchResult5 = new \stdClass();
        $fetchResult5->status = 'HZ';
        $fetchResult6 = new \stdClass();
        $fetchResult6->status = 'Queue';

        $queryResult->fetch_object = function() use ($fetchResult1) {
            return $fetchResult1;
        };

        $conn
            ->expects($this->exactly(6))
            ->method('query')
            ->willReturn($queryResult);

        $queryResult
            ->expects($this->exactly(6))
            ->method('fetch_object')
            ->willReturn($fetchResult1, $fetchResult2, $fetchResult3, $fetchResult4, $fetchResult5, $fetchResult6);

        $queryResult
            ->expects($this->exactly(6))
            ->method('free')
            ->willReturn(true);

        $res = $provider->getSmsStatus($token);
        $this->assertEquals($res, AbstractProvider::$STATE_DELIVERED);
        $res = $provider->getSmsStatus($token);
        $this->assertEquals($res, AbstractProvider::$STATE_BUFFERED);
        $res = $provider->getSmsStatus($token);
        $this->assertEquals($res, AbstractProvider::$STATE_FAILED);
        $res = $provider->getSmsStatus($token);
        $this->assertEquals($res, AbstractProvider::$STATE_UNKNOWN);
        $res = $provider->getSmsStatus($token);
        $this->assertEquals($res, AbstractProvider::$STATE_UNKNOWN);
        $res = $provider->getSmsStatus($token);
        $this->assertEquals($res, AbstractProvider::$STATE_QUEUE);

    }

    public function testParseResponse()
    {
        $object = new MrMessagingProvider([]);
        $res = $this->invokeMethod($object, 'parseResponse', ['']);
        $this->assertEquals($res, ['status' => false, 'result' => 'Server returned invalid response']);

        $res = $this->invokeMethod($object, 'parseResponse', ['Out of credits', 0, null, 402]);
        $this->assertEquals($res, ['status' => false, 'result' => 'Out of credits']);

        $res = $this->invokeMethod($object, 'parseResponse', ['e7d34dc7-9a3b-4c49-90b8-8c157f55fa6f']);
        $this->assertEquals($res, ['status' => true, 'result' => 'e7d34dc7-9a3b-4c49-90b8-8c157f55fa6f']);
    }

    public function testSendRequest()
    {
        $object = new MrMessagingProvider([]);
        $res = $this->invokeMethod($object, 'SendRequest', []);
        $this->assertEquals($res, ['status' => false, 'result' => 'Server returned invalid response']);
    }

    public function testHanldeCallback()
    {
        $conn = $this->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();
        DbMock::setConnection($conn);

        $queryResult = $this->getMockBuilder('stdClass')->setMethods(['fetch_object', 'free'])->getMock();
        $queryResult->num_rows = 1;

        $fetchResult = new \stdClass();
        $fetchResult->id = 1;

        $queryResult->fetch_object = function() use ($fetchResult) {
            return $fetchResult;
        };

        $conn
            ->expects($this->exactly(4))
            ->method('query')
            ->willReturn($queryResult);

        $queryResult
            ->expects($this->exactly(2))
            ->method('fetch_object')
            ->willReturn($fetchResult, null);

        $queryResult
            ->expects($this->exactly(2))
            ->method('free')
            ->willReturn(true);
        $res = MrMessagingProvider::hanldeCallback(['id' => 1, 'status' => 'DELIVRD']);
        $this->assertNull($res);
        $res = MrMessagingProvider::hanldeCallback(['id' => 1, 'status' => 'DELIVRD']);
        $this->assertNull($res);
    }

}
