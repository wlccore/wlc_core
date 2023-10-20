<?php
namespace eGamings\WLC\Tests\Sms;

use eGamings\WLC\Sms\LinkMobilityProvider;
use eGamings\WLC\Tests\BaseCase;
use eGamings\WLC\Sms\AbstractProvider;
use eGamings\WLC\Tests\DbConnectionMock;
use eGamings\WLC\Tests\DbMock;

class  LinkMobilityProviderMock extends LinkMobilityProvider
{
    function __construct(array $config)
    {
        parent::__construct($config);
    }
}

class LinkMobilityProviderTest extends BaseCase
{
    public function testPrepareParams ()
    {
        $data = [
             'source' => 'TestSender',
             'destination' => '+79111111111',
             'userData' => 'Test content',
        ];
        $config = [
            'platformId'          => 'platformId',
            'platformPartnerId'   => 'platformPartnerId',
            'deliveryReportGates' => 'deliveryReportGates',
            'ignoreResponse'      => false,
            'useDeliveryReport'   => true,
            'sourceTON'           => 'ALPHANUMERIC',
            'destinationTON'      => 'MSISDN',
            'dcs'                 => 'TEXT',
        ];
        $provider = new LinkMobilityProvider($config);

        $reflection = new \ReflectionClass(get_class($provider));
        $method = $reflection->getMethod('prepareParams');
        $method->setAccessible(true);
        $res =  $method->invoke($provider, $data);
        $this->assertEquals($res, array_merge($config, $data));
    }

    public function testSendOne()
    {
        $mock = $this->getMockBuilder(LinkMobilityProvider::class)
            ->setMethods(['SendRequest'])
            ->disableOriginalConstructor()
            ->getMock();
        $mock->expects($this->exactly(2))->method('SendRequest')->willReturn(
            ['status' => false, 'result' => false],
            ['status'=> true, 'result' => ['messageId' => '12ewrw', 'resultCode'=>0]]
        );

        $conn = $this->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();
        DbMock::setConnection($conn);

        $queryResult = 1;
        $conn
            ->expects($this->exactly(1))
            ->method('query')
            ->willReturn($queryResult);

        $res = $mock->SendOne('9111111111', 'TestSender', 'Test content', '7');
        $this->assertEquals($res, ['status' => false, 'result' => false]);

        $res = $mock->SendOne('9111111111', 'TestSender', 'Test content', '7');
        $result = [];
        $result['status'] = true;
        $result['token'] = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJwaG9uZU51bWJlciI6IjkxMTExMTExMTEiLCJwaG9uZUNvZGUiOiI3IiwibXNnaWQiOiIxMmV3cnciLCJjb2RlIjpudWxsLCJ1aWQiOjF9.yKhHtPjEnM_pDmVmzkZsb9z0fCBWp70Fg1OUXY_7Tos';
        $this->assertEquals($res, $result);
    }

    public function testSendMultiple()
    {
        $provider = new LinkMobilityProvider([]);
        $res = $provider->SendMultiple('TestSender', [], '7');
        $this->assertEquals($res, ['status' => false, 'result' => 'Server returned invalid response']);
    }

    public function testGetSmsStatus()
    {
        $privateKey = 'this_is_secret_key';
        $provider = new LinkMobilityProvider([
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
        $fetchResult1->status = 1005;
        $fetchResult2 = new \stdClass();
        $fetchResult2->status = 1001;
        $fetchResult3 = new \stdClass();
        $fetchResult3->status = 1006;
        $fetchResult4 = new \stdClass();
        $fetchResult4->status = 1002;
        $fetchResult5 = new \stdClass();
        $fetchResult5->status = 1;
        $fetchResult6 = new \stdClass();
        $fetchResult6->status = 0;
        $fetchResult7 = new \stdClass();
        $fetchResult7->status = -1;

        $queryResult->fetch_object = function() use ($fetchResult1) {
            return $fetchResult1;
        };

        $conn
            ->expects($this->exactly(7))
            ->method('query')
            ->willReturn($queryResult);

        $queryResult
            ->expects($this->exactly(7))
            ->method('fetch_object')
            ->willReturn($fetchResult1, $fetchResult2, $fetchResult3, $fetchResult4, $fetchResult5, $fetchResult6,$fetchResult7);

        $queryResult
            ->expects($this->exactly(7))
            ->method('free')
            ->willReturn(true);

        $res = $provider->getSmsStatus($token);
        $this->assertEquals($res, AbstractProvider::$STATE_QUEUE);
        $res = $provider->getSmsStatus($token);
        $this->assertEquals($res, AbstractProvider::$STATE_DELIVERED);
        $res = $provider->getSmsStatus($token);
        $this->assertEquals($res, AbstractProvider::$STATE_UNDELIVERED);
        $res = $provider->getSmsStatus($token);
        $this->assertEquals($res, AbstractProvider::$STATE_FAILED);
        $res = $provider->getSmsStatus($token);
        $this->assertEquals($res, AbstractProvider::$STATE_Error);
        $res = $provider->getSmsStatus($token);
        $this->assertEquals($res, AbstractProvider::$STATE_UNKNOWN);
        $res = $provider->getSmsStatus($token);
        $this->assertEquals($res, AbstractProvider::$STATE_UNKNOWN);

    }

    public function testGetCodeDescription()
    {
        $object = new LinkMobilityProvider([]);
        $res = $this->invokeMethod($object, 'getCodeDescription', [0]);
        $this->assertEquals($res, 'Unknown error');
    }

    public function testParseResponse()
    {
        $object = new LinkMobilityProvider([]);
        $res = $this->invokeMethod($object, 'parseResponse', ['{}']);
        $this->assertEquals($res, ['status' => false, 'result' => 'Server returned invalid response']);

        $resp = '{"messageId":"Dcshuhod0PMAAAFQ+/PbnR3x","resultCode":1005,"description":"Queued"}';

        $res = $this->invokeMethod($object, 'parseResponse', [$resp, 0, null, 500]);
        $this->assertEquals($res, ['status' => false, 'result' => 'Queued']);

        $res = $this->invokeMethod($object, 'parseResponse', [$resp]);
        $answ = json_decode($resp, JSON_OBJECT_AS_ARRAY);
        $this->assertEquals($res, ['status' => true, 'result' => $answ]);

        $resp = '[{"messageId": "QC5BGwiuYk0AAAFiQ08nTFOS", "refId": "myRefId", "resultCode": 1005, "message": "Queued","smsCount": 1},{ "messageId": "QC5BHHuqylsAAAFiQ08nX2ph", "refId": "myRefId", "resultCode": 1005, "message": "Queued","smsCount": 1}]';
        $res = $this->invokeMethod($object, 'parseResponse', [$resp, 0, null, 500, true]);
        $answ = [
            'status' => false,
            'result' => [
                'QC5BGwiuYk0AAAFiQ08nTFOS' => 'Queued',
                'QC5BHHuqylsAAAFiQ08nX2ph' => 'Queued',
            ],
        ];
        $this->assertEquals($res, $answ);
    }

    public function testSendRequest()
    {
        $object = new LinkMobilityProvider([]);
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
        $res = LinkMobilityProvider::hanldeCallback(['id' => 1, 'resultCode' => '1001']);
        $this->assertNull($res);
        $res = LinkMobilityProvider::hanldeCallback(['id' => 1, 'resultCode' => '1001']);
        $this->assertNull($res);
    }

    public function testPrepareParamsBatch() {
        $data = [
            ['+79111111111', 'TestMsg1'],
            ['+79111111112', 'TestMsg2'],
        ];

        $config = [
            'platformId'          => 'platformId',
            'platformPartnerId'   => 'platformPartnerId',
            'deliveryReportGates' => 'deliveryReportGates',
        ];

        $resData = [
            'platformId'          => 'platformId',
            'platformPartnerId'   => 'platformPartnerId',
            'deliveryReportGates' => 'deliveryReportGates',
            'ignoreResponse'      => false,
            'useDeliveryReport'   => true,
            'sendRequestMessages' => [
                [
                    'source' => 'Test',
                    'sourceTON'   => 'ALPHANUMERIC',
                    'destination' => '+79111111111',
                    'userData'    => 'TestMsg1',
                ],
                [
                    'source' => 'Test',
                    'sourceTON'   => 'ALPHANUMERIC',
                    'destination' => '+79111111112',
                    'userData'    => 'TestMsg2',
                ],
            ],
        ];

        $provider = new LinkMobilityProvider($config);

        $reflection = new \ReflectionClass(get_class($provider));
        $method = $reflection->getMethod('prepareParamsBatch');
        $method->setAccessible(true);
        $res =  $method->invoke($provider, 'Test', $data);

        $this->assertEquals($res, $resData);
    }

}
