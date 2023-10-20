<?php
namespace eGamings\WLC\Tests;

use eGamings\WLC\RateLimiter;
use eGamings\WLC\Cache\RedisCache;
use eGamings\WLC\Core;

class RateLimiterMock extends RateLimiter {
    private function __construct() {}

    public static function getInstance() {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
}

class RateLimiterTest extends BaseCase
{
    public function testLimit() {
        $mockRedis = $this->getMockBuilder(RedisCache::class)
            ->setMethods(['set', 'exists', 'incr', 'delete'])
            ->disableOriginalConstructor()
            ->getMock();

        $mockRedis
            ->expects($this->exactly(3))
            ->method('exists')
            ->withConsecutive(
                ['RATE_LIMIT_BLOCK:test:0.0.0.0', ['language' => '']],
                ['RATE_LIMIT_BLOCK:test:0.0.0.0', ['language' => '']],
                ['RATE_LIMIT_BLOCK:test:0.0.0.0', ['language' => '']]
            )
            ->will($this->onConsecutiveCalls(false, false, true));

        $mockRedis
            ->expects($this->exactly(2))
            ->method('incr')
            ->withConsecutive(
                ['RATE_LIMIT:test:0.0.0.0', 60, ['language' => '']],
                ['RATE_LIMIT:test:0.0.0.0', 60, ['language' => '']]
            )
            ->will($this->onConsecutiveCalls(1, 2));

        $mockRedis
            ->expects($this->exactly(1))
            ->method('set')
            ->withConsecutive(
                ['RATE_LIMIT_BLOCK:test:0.0.0.0', 1, 300, ['language' => '']]
            )
            ->will($this->onConsecutiveCalls(true));

        $mockRedis
            ->expects($this->once())
            ->method('delete')
            ->withConsecutive(
                ['RATE_LIMIT:test:0.0.0.0', ['language' => '']]
            )
            ->will($this->onConsecutiveCalls(true));

        $reflectionRateLimiter = new \ReflectionClass(RateLimiter::class);
        $reflectionProperty = $reflectionRateLimiter->getProperty('_instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(RateLimiterMock::getInstance());

        RateLimiter::getInstance()->setMiddleware($mockRedis);

        $this->assertTrue(RateLimiter::getInstance()->limit('test', '0.0.0.0', 2, 60, 300));
        $this->assertFalse(RateLimiter::getInstance()->limit('test', '0.0.0.0', 2, 60, 300));
        $this->assertFalse(RateLimiter::getInstance()->limit('test', '0.0.0.0', 2, 60, 300));

        $reflectionProperty->setValue(null);
    }

    public function testConstruct() {
        global $cfg;

        $mockRedis = $this->getMockBuilder(RedisCache::class)
            ->setMethods(['set', 'exists', 'incr', 'delete'])
            ->disableOriginalConstructor()
            ->getMock();

        $reflectionCore = new \ReflectionClass(Core::class);
        $reflectionProperty = $reflectionCore->getProperty('_instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(CoreMock::getInstance());

        $cfg['rateLimiterIPsWhiteList'] = ['0.0.0.0'];

        RateLimiter::getInstance()->setMiddleware($mockRedis);

        $this->assertFalse(RateLimiter::getInstance()->isBlocked('test', '0.0.0.0'), "Should be in the white list");

        $reflectionProperty->setValue(null);
    }

    public function testResetDepositsLimiter()
    {
        $mockRedis = $this->getMockBuilder(RedisCache::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockRedis
            ->expects($this->exactly(2))
            ->method('delete')
            ->withConsecutive(
                ['RATE_LIMIT:postapi/v1/deposits:0.0.0.0', ['language' => '']],
                ['RATE_LIMIT_BLOCK:postapi/v1/deposits:0.0.0.0', ['language' => '']]
            )
            ->will($this->onConsecutiveCalls(true));

        $reflectionRateLimiter = new \ReflectionClass(RateLimiter::class);
        $reflectionProperty = $reflectionRateLimiter->getProperty('_instance');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(RateLimiterMock::getInstance());

        RateLimiter::getInstance()->setMiddleware($mockRedis);

        $this->assertNull(RateLimiter::getInstance()::resetDepositsLimiter());
        $_POST['ip'] = '0.0.0.0';
        $this->assertNull(RateLimiter::getInstance()::resetDepositsLimiter());
    }
}
