<?php
namespace eGamings\WLC\Tests\Sms;

use eGamings\WLC\Tests\BaseCase;
use eGamings\WLC\Sms\TwilioProvider;
use eGamings\WLC\Core;
use eGamings\WLC\Tests\RedisCacheMock;
use eGamings\WLC\Tests\CoreMock;

class TwilioProviderMessagesMock {
    public function __call($method, $params) {
        throw new \Exception('TwilioProviderMessagesMock TestMockCall');
    }
}

class TwilioProviderMessagesMockWithCreate {
    public function __call($method, $params) {
        throw new \Exception('TwilioProviderMessagesMock TestMockCall');
    }

    public function create ($to, $options = []) {
        $msg = new \stdClass();
        $msg->sid = 1234;
        return $msg;
    }

}

class TwilioProviderMock extends TwilioProvider {
    function __construct(array $config) {
        $this->client = new \stdClass();
        $this->client->messages = new TwilioProviderMessagesMock();
    }
}

class TwilioProviderMockSuccess extends TwilioProvider {
    function __construct(array $config) {
        $this->client = new \stdClass();
        $this->client->messages = new TwilioProviderMessagesMockWithCreate();
        $this->codeTTL = 300;
        $this->userLimitPerHour =  5;
    }
}

class TwilioProviderTest extends BaseCase {

    public function setUp(): void
    {
        $coreMock = $this->getMockBuilder(CoreMock::class)
            ->disableOriginalConstructor()
            ->setMethods(['redisCache'])
            ->getMock();

        $coreMock->method('redisCache')->willReturn(new RedisCacheMock());

        $reflectionSystem = new \ReflectionClass(Core::class);
        $reflectionProperty = $reflectionSystem->getProperty('_instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($coreMock);
    }

    public function tearDown(): void
    {
        $reflectionSystem = new \ReflectionClass(Core::class);
        $reflectionProperty = $reflectionSystem->getProperty('_instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(null);
    }
    
    public function testSendOne() {
        $sms = new TwilioProviderMock([]);
        $result = $sms->SendOne("123123", "TestSender", "TestMessage", "+1");
        $this->assertTrue(is_array($result));
        $this->assertFalse($result['status']);
    }

    public function testSendOneMsg() {
        $sms = new TwilioProviderMockSuccess([]);
        $result = $sms->SendOne("123123", "TestSender", "TestMessage", "+1");
        $this->assertTrue(is_array($result));
        $this->assertTrue($result['status']);
    }

    public function testSendOneMsgRedisKeyExists() {

        $redismock = $this->getMockBuilder(RedisCacheMock::class)
            ->setMethods(['exists', 'get', 'ttl'])
            ->getMock();
        $redismock->method('exists')->willReturn(true);
        $redismock->method('get')->willReturn(1, 1, 5, 5);
        $redismock->method('ttl')->willReturn(3600);

        $coreMock = $this->getMockBuilder(CoreMock::class)
            ->disableOriginalConstructor()
            ->setMethods(['redisCache'])
            ->getMock();

        $coreMock->method('redisCache')->willReturn($redismock);

        $reflectionSystem = new \ReflectionClass(Core::class);
        $reflectionProperty = $reflectionSystem->getProperty('_instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($coreMock);

        $sms = new TwilioProviderMockSuccess([]);

        $result = $sms->SendOne("123123", "TestSender", "TestMessage", "+1");
        $this->assertTrue(is_array($result));
        $this->assertTrue($result['status']);

        $result = $sms->SendOne("123123", "TestSender", "TestMessage", "+1");
        $this->assertTrue(is_array($result));
        $this->assertFalse($result['status']);

    }

}
