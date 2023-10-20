<?php

namespace eGamings\WLC\Tests;

use eGamings\WLC\Core;
use eGamings\WLC\SmsQueue;
use eGamings\WLC\Sms\AbstractProviderTest;
use eGamings\WLC\Api;
use eGamings\WLC\Sms\MockProvider;

class SmsQueueTest extends BaseCase
{

    public static function setUpBeforeClass() : void
    {
        parent::setUpBeforeClass();

        DbMock::setConnection(null);
        DbMock::setConnClass(DbConnectionMock::class);
        DbConnectionMock::$hasConnectError = false;
    }

    public function setUp() : void
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

    public function tearDown() : void
    {
        $reflectionSystem = new \ReflectionClass(Core::class);
        $reflectionProperty = $reflectionSystem->getProperty('_instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(null);
    }

    public function testProcessWrongStatus() : void
    {
        $result = SmsQueue::process('wrongStatus');
        $this->assertNull($result, 'Result must be NULL');
    }

    public function testProcessAlreadyRun() : void
    {
        RedisMock::setExistsReturn(true);

        $result = SmsQueue::process('queue');
        $this->assertNull($result, 'Result must be NULL');
    }

    public function testProcessRedisError() : void
    {
        RedisMock::setExistsReturn(false);
        RedisMock::setSetReturn(false);

        $result = SmsQueue::process('queue');
        $this->assertNull($result, 'Result must be NULL');
    }

    public function testProcess() : void
    {
        RedisMock::setExistsReturn(false);
        RedisMock::setSetReturn(true);

        $connMock = $this->getDBConnMock(
            new class
            {
                public $num_rows = 1;

                public function fetch_all()
                {
                    return [
                        [
                            'phone' => '+371-23956733',
                            'message' => 'test message',
                            'id' => '1',
                        ],
                        [
                            'phone' => '+371-23956733',
                            'message' => 'Тестовое смс',
                            'id' => '1',
                        ],
                        [
                            'phone' => '-',
                            'message' => 'test message',
                            'id' => '1',
                        ],
                        [
                            'phone' => '',
                            'message' => 'test message',
                            'id' => '1',
                        ],
                    ];
                }
            }
        );
        global $cfg;
        unset($cfg['smsConfig']);
        DbMock::setConnection($connMock);

        // Emulate sms provider not found
        $result = SmsQueue::process('queue');

        $this->assertFalse($result, 'Result must be False');

        
        $cfg['smsConfig'] = [
            'provider' => 'Mock',
        ];

        // Emulate SmsQueue::process() === true
        try {
            $result = SmsQueue::process('queue');
        } catch(\Exception $e) {
            $result = true;
        }

        $this->assertTrue($result, 'Result must be TRUE');

        // emulate throw exception
        MockProvider::setThrowException(true);

        try {
            $result = SmsQueue::process('queue');
        } catch(\Exception $e) {
            $result = true;
        }

        $this->assertTrue($result, 'Result must be TRUE');

        DbMock::setConnection(null);
    }

    public function testMultiEnqueue() : void
    {
        $result = SmsQueue::multiEnqueue([
            [
                'to' => '+371-22233344',
                'message' => 'message',
            ],
            [
                'to' => '+371-22255566',
                'message' => 'message',
            ],
            [
                'to' => '+371-22277788',
                'message' => 'message',
            ]
        ]);

        $this->assertTrue($result, 'Result must be True');
    }

    public function testMultiEnqueueWrongArgs() : void
    {
        $result = SmsQueue::multiEnqueue('wrongArgs');
        $this->assertFalse($result, 'Result must be FALSE');

        $result = SmsQueue::multiEnqueue([
            [
                'to' => '+342-222222',
            ]
        ]);

        $this->assertTrue($result, 'Result must be TRUE');
    }

    public function testUpdateStatusWrongStatus() : void
    {
        $result = SmsQueue::updateStatus(10, 'wrong');
        $this->assertFalse($result, 'Result must be FALSE');
    }

    public function testUpdateStatus() : void
    {
        $result = SmsQueue::updateStatus(10, 'queue');
        $this->assertTrue($result, 'Result must be TRUE');
    }

    public function testCleanError() : void
    {
        $result = SmsQueue::clean();
        $this->assertFalse($result, 'Result must be FALSE');
    }

    public function testClean() : void
    {
        $connMock = $this->getDBConnMock(true);

        DbMock::setConnection($connMock);

        $result = SmsQueue::clean();
        $this->assertTrue($result, 'Result must be TRUE');

        DbMock::setConnection(null);
    }

    public function testCreateSmsQueueErrors() : void
    {
        // empty $_POST['params']
        unset($_POST['params']);
        $result = Api::createSmsQueue();
        $this->assertNull($result);
    }

    /**
     * @param mixed $result
     * @return object
     */
    private function getDBConnMock($result = null) : object
    {
        $mock = $this->getMockBuilder(DbConnectionMock::class)
            ->disableOriginalConstructor()
            ->setMethods(['query'])
            ->getMock();

        $mock->method('query')
            ->willReturn($result);

        return $mock;
    }
}
