<?php

namespace eGamings\WLC\Tests;

use eGamings\WLC\Core;
use eGamings\WLC\SmsQueue;
use eGamings\WLC\Sms\AbstractProviderTest;
use eGamings\WLC\Api;
use eGamings\WLC\Sms\MockProvider;
use eGamings\WLC\Sms;

class SmsTest extends BaseCase
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

    public function testSendSmsPasswordRestore() : void
    {
        Sms::unsetInstance();
        global $cfg;

        $conn = $this->getMockBuilder(DbConnectionMock::class)
            ->setMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();
        DbMock::setConnection($conn);

        $queryResult = $this->getMockBuilder('stdClass')->setMethods(['fetch_object', 'free'])->getMock();
        $queryResult->num_rows = 1;

        $fetchResult1 = new \stdClass();
        $fetchResult1->phone1 = '+371';
        $fetchResult1->phone2 = '22222222222';
        $fetchResult1->email = 'test@test.com';

        $fetchResult3 = null;
        $fetchResult5 = $fetchResult4 = $fetchResult2 = $fetchResult1;

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
            ->willReturn($fetchResult1, $fetchResult2, $fetchResult3, $fetchResult4, $fetchResult5);

        $queryResult
            ->expects($this->exactly(6))
            ->method('free')
            ->willReturn(true);


        RedisMock::setExistsReturn(false);
        RedisMock::setSetReturn(true);

        // error provider not found
        unset($cfg['smsConfig']);
        MockProvider::setThrowException(true);
        $result = Sms::sendSmsPasswordRestoreCode('22222222222');
        $this->assertEquals(
            '0;Sms provider not found',
            $result
        );

        $cfg['smsConfig'] = [
            'provider' => 'Mock',
            'sender' => 'MockSender'
    ];

        // success
        MockProvider::setThrowException(false);
        $result = Sms::sendSmsPasswordRestoreCode('22222222222');
        $this->assertEquals(
            '1;Sms sent, recovery code will be available for 30 minutes',
            $result
        );

        // user not found
        MockProvider::setThrowException(false);
        $result = Sms::sendSmsPasswordRestoreCode('123');
        $this->assertEquals(
            '0;Error, account with this phone does not exist.',
            $result
        );

        // error send
        MockProvider::setThrowException(true);
        $result = Sms::sendSmsPasswordRestoreCode('22222222222');
        $this->assertEquals(
            '0;0,UnitTest Swift Exception',
            $result
        );

        // error send recovery code
        RedisMock::setSetReturn(false);
        MockProvider::setThrowException(true);
        $result = Sms::sendSmsPasswordRestoreCode('22222222222');
        $this->assertEquals(
            '0;Error sending recovery code',
            $result
        );

        // hide phone existence
        $cfg['hidePhoneExistence'] = true;
        MockProvider::setThrowException(false);
        MockProvider::setThrowException(false);

        $result = Sms::sendSmsPasswordRestoreCode('123');
        $this->assertEquals(
            '1;Sms sent, recovery code will be available for 30 minutes',
            $result
        );
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
