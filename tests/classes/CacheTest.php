<?php

namespace eGamings\WLC\Tests;

use eGamings\WLC\Cache;
use eGamings\WLC\Cache\ApcCache;
use eGamings\WLC\Cache\RedisCache;

class CacheTest extends BaseCase
{
    public function tearDown(): void {
        Cache::clearMiddleware();
    }

    public function testAddMiddleware() {
        $reflectionCache = new \ReflectionClass(Cache::class);
        $reflectionProperty = $reflectionCache->getProperty('middleware');
        $reflectionProperty->setAccessible(true);

        $mockApc = $this->getMockBuilder(ApcCache::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockRedis = $this->getMockBuilder(RedisCache::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertCount(0, $reflectionProperty->getValue());
        Cache::addMiddleware($mockApc);
        $this->assertCount(1, $reflectionProperty->getValue());
        Cache::addMiddleware($mockRedis);
        $this->assertCount(2, $reflectionProperty->getValue());

        $this->assertEquals($reflectionProperty->getValue(), [$mockApc, $mockRedis]);
    }

    public function testGet() {
        $cacheKey = 'test-get-key';
        $cacheArgs = ['test-get-arg1', 'test-get-arg2'];

        $mockApc = $this->getMockBuilder(ApcCache::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();

        $mockApc->method('get')->with($cacheKey, $cacheArgs)->will($this->onConsecutiveCalls(123, null, null));

        $mockRedis = $this->getMockBuilder(RedisCache::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();

        $mockRedis->method('get')->with($cacheKey, $cacheArgs)->will($this->onConsecutiveCalls(321, null));

        Cache::addMiddleware($mockApc, $mockRedis);

        $mockApc->expects($this->exactly(3))->method('get');
        $mockRedis->expects($this->exactly(2))->method('get');

        $this->assertEquals(Cache::get($cacheKey, $cacheArgs), 123);
        $this->assertEquals(Cache::get($cacheKey, $cacheArgs), 321);
        $this->assertNull(Cache::get($cacheKey, $cacheArgs), "Third call must return null");
    }

    public function testSet() {
        $cacheKey = 'test-cache-key';
        $cacheArgs = ['test-arg1', 'test-arg2', 'test-arg3'];
        $cacheTtl = 12345;
        $cacheValue = 'test-cache-value';

        $mockApc = $this->getMockBuilder(ApcCache::class)
            ->setMethods(['set'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockApc->method('set')->with($cacheKey, $cacheValue, $cacheTtl, $cacheArgs)->willReturn(true);

        $mockRedis = $this->getMockBuilder(RedisCache::class)
            ->setMethods(['set'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockRedis->method('set')->with($cacheKey, $cacheValue, $cacheTtl, $cacheArgs)->willReturn(true);

        Cache::addMiddleware($mockApc, $mockRedis);

        $mockApc->expects($this->once())->method('set');
        $mockRedis->expects($this->once())->method('set');

        $this->assertTrue(Cache::set($cacheKey, $cacheValue, $cacheTtl, $cacheArgs));
    }

    public function testDelete() {
        $mockApc = $this->getMockBuilder(ApcCache::class)
            ->setMethods(['delete'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockApc->method('delete')
                ->withConsecutive(
                    ['key 1', []],
                    ['key 2', [1, 2, 3]]
                )
                ->will($this->onConsecutiveCalls(true, true));

        $mockRedis = $this->getMockBuilder(RedisCache::class)
            ->setMethods(['delete'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockRedis->method('delete')
                  ->withConsecutive(
                      ['key 1', []],
                      ['key 2', [1, 2, 3]]
                  )
                  ->will($this->onConsecutiveCalls(true, true));

        Cache::addMiddleware($mockApc, $mockRedis);

        $mockApc->expects($this->exactly(2))->method('delete');
        $mockRedis->expects($this->exactly(2))->method('delete');

        $this->assertTrue(Cache::delete('key 1'));
        $this->assertTrue(Cache::delete('key 2', [1, 2, 3]));
    }

    public function testResult() {
        $func = function () {
            return 'result 3';
        };

        $mockApc = $this->getMockBuilder(ApcCache::class)
            ->setMethods(['get', 'set'])
            ->disableOriginalConstructor()
            ->getMock();

        $mockApc->method('get')->withConsecutive(
            ['key-1', []],
            ['key-2', ['arg1', 'arg2']],
            ['key-3', []]
        )->will(
            $this->onConsecutiveCalls('result 1', 'result 2', null)
        );

        $mockApc->method('set')->withConsecutive(
            ['key-3', 'result 3', 30, []]
        )->will(
            $this->onConsecutiveCalls(true)
        );

        Cache::addMiddleware($mockApc);

        $mockApc->expects($this->exactly(3))->method('get');
        $mockApc->expects($this->once())->method('set');

        $this->assertEquals(Cache::result('key-1', $func), 'result 1');
        $this->assertEquals(Cache::result('key-2', $func, 120, ['arg1', 'arg2']), 'result 2');
        $this->assertEquals(Cache::result('key-3', $func, 30), 'result 3');
    }

    public function testDropCacheKeys() {
        $mockApc = $this->getMockBuilder(ApcCache::class)
            ->setMethods(['dropCacheKeys'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockApc->method('dropCacheKeys')
            ->withConsecutive(
                ['key 1'],
                ['key 2']
            )
            ->will($this->onConsecutiveCalls(true, true));

        $mockRedis = $this->getMockBuilder(RedisCache::class)
            ->setMethods(['dropCacheKeys'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockRedis->method('dropCacheKeys')
            ->withConsecutive(
                ['key 1'],
                ['key 2']
            )
            ->will($this->onConsecutiveCalls(true, true));

        Cache::addMiddleware($mockApc, $mockRedis);

        $mockApc->expects($this->exactly(2))->method('dropCacheKeys');
        $mockRedis->expects($this->exactly(2))->method('dropCacheKeys');

        $this->assertNull(Cache::dropCacheKeys('key 1'));
        $this->assertNull(Cache::dropCacheKeys('key 2'));
    }
}
