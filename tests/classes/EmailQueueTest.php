<?php

namespace eGamings\WLC\Tests;

use eGamings\WLC\Core;
use eGamings\WLC\EmailQueue;

class EmailQueueTest extends BaseCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        DbMock::setConnection(null);
        DbMock::setConnClass(DbConnectionMock::class);
        DbConnectionMock::$hasConnectError = false;
    }

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

    public function testProcessWrongStatus()
    {
        $result = EmailQueue::process('wrongStatus');
        $this->assertNull($result, 'Result must be NULL');
    }

    public function testProcessAlreadyRun()
    {
        RedisMock::setExistsReturn(true);

        $result = EmailQueue::process('queue');
        $this->assertNull($result, 'Result must be NULL');
    }

    public function testProcessRedisError()
    {
        RedisMock::setExistsReturn(false);
        RedisMock::setSetReturn(false);

        $result = EmailQueue::process('queue');
        $this->assertNull($result, 'Result must be NULL');
    }

    public function testProcess()
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
                        // With external smtp
                        [
                            'host' => 'testhostname.com',
                            'username' => 'test_name',
                            'password' => 'test_password',
                            'email' => 'test@mail.er',
                            'subject' => 'test subject',
                            'message' => 'test message',
                            'id' => '1',
                        ],
                        // Without external smtp
                        [
                            'host' => '',
                            'username' => '',
                            'password' => '',
                            'email' => 'qwe@aas.rr',
                            'subject' => 'test subject',
                            'message' => 'test message',
                            'id' => '2',
                        ]
                    ];
                }
            }
        );

        DbMock::setConnection($connMock);

        // Emulate EmailQueue::send() === true
        try{
            $result = EmailQueueMock::process('queue');
        } catch(\Exception $e) {
            $result = true;
        }

        $this->assertTrue($result, 'Result must be TRUE');

        // Emulate EmailQueue::send() === false
        EmailQueueMock::setSendResult(false);

        try{
            $result = EmailQueueMock::process('queue');
        } catch(\Exception $e) {
            $result = true;
        }
        $this->assertTrue($result, 'Result must be TRUE');

        // Emulate throwing Swift exception
        EmailQueueMock::setThrowException(true);

        try{
            $result = EmailQueueMock::process('queue');
        } catch(\Exception $e) {
            $result = true;
        }

        $this->assertTrue($result, 'Result must be TRUE');

        DbMock::setConnection(null);
    }

    public function testMultiEnqueueWrongArgs()
    {
        $result = EmailQueue::multiEnqueue('wrongArgs');
        $this->assertFalse($result, 'Result must be FALSE');

        $result = EmailQueue::multiEnqueue([
            [
                'to' => 'testmail@mail.com',
            ]
        ]);

        $this->assertTrue($result, 'Result must be TRUE');
    }

    public function testMultiEnqueue()
    {
        $result = EmailQueue::multiEnqueue([
            [
                'to' => 'testmail+1@mail.com',
                'subject' => 'subject',
                'message' => 'message',
                'smtp_id' => 0,
            ],
            [
                'to' => 'testmail+2@mail.com',
                'subject' => 'subject',
                'message' => 'message',
                'smtp_id' => 1,
            ],
            [
                'to' => 'testmail+3@mail.com',
                'subject' => 'subject',
                'message' => 'message',
                'smtp_id' => 2,
            ]
        ]);

        $this->assertTrue($result, 'Result must be TRUE');
    }

    public function testAddSmtpConfigWrongArg()
    {
        $result = EmailQueue::addSmtpConfig(['wrong' => 'argument']);
        $this->assertSame(0, $result, 'Result must be INT');
    }

    public function testAddSmtpConfigAlreadyExists()
    {
        $connMock = $this->getDBConnMock(
            new class
            {
                public $num_rows = 1;

                public function fetch_assoc()
                {
                    return ['id' => 5];
                }
            }
        );

        DbMock::setConnection($connMock);

        $result = EmailQueue::addSmtpConfig(['host' => 'test', 'username' => 'test', 'password' => 'test']);
        $this->assertSame(5, $result, 'Result must be INT');

        DbMock::setConnection(null);
    }

    public function testAddSmtpConfig()
    {
        $connMock = $this->getDBConnMock(
            new class
            {
                public $num_rows = 0;
            }
        );

        DbMock::setConnection($connMock);

        $result = EmailQueue::addSmtpConfig(['host' => 'test', 'username' => 'test', 'password' => 'test']);

        /**
         * Must return 1 {@see} DbConnectionMock::$insert_id
         */
        $this->assertSame(1, $result, 'Result must be INT');

        DbMock::setConnection(null);
    }

    public function testUpdateStatusWrongStatus()
    {
        $result = EmailQueue::updateStatus(10, 'wrong');
        $this->assertFalse($result, 'Result must be FALSE');
    }

    public function testUpdateStatus()
    {
        $result = EmailQueue::updateStatus(10, 'queue');
        $this->assertTrue($result, 'Result must be TRUE');
    }

    public function testCleanError()
    {
        $result = EmailQueue::clean();
        $this->assertFalse($result, 'Result must be FALSE');
    }

    public function testClean()
    {
        $connMock = $this->getDBConnMock(true);

        DbMock::setConnection($connMock);

        $result = EmailQueue::clean();
        $this->assertTrue($result, 'Result must be TRUE');

        DbMock::setConnection(null);
    }

    public function testSend()
    {
        $reflectionSystem = new \ReflectionClass(EmailQueue::class);
        $reflectionMethod = $reflectionSystem->getMethod('send');
        $reflectionMethod->setAccessible(true);

        // EmailQueue::send() may throw Swift_SwiftException exception
        $this->expectException(\Swift_SwiftException::class);

        // On tests always result === false
        $this->assertFalse(
            $reflectionMethod->invokeArgs(null, ['user@mail.com', 'subject', 'message']),
            'Result must be FALSE'
        );

        // On tests always result === false
        $this->assertFalse(
            $reflectionMethod->invokeArgs(null, ['user@mail.com', 'subject', 'message', [
                'host' => 'hostname.com',
                'username' => 'username',
                'password' => 'password',
            ]]),
            'Result must be FALSE'
        );
    }

    private function getDBConnMock($result = null)
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
