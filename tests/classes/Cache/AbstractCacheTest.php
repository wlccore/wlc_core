<?php
namespace eGamings\WLC\Tests\Cache;

use eGamings\WLC\Tests\BaseCase;
use eGamings\WLC\Tests\RedisCacheMock;

abstract class AbstractCacheTest extends BaseCase
{
    abstract protected function getTestClassName();

    abstract protected function getConfig(array $extConfig = []);

    protected function getNewInstance($extConfig = []) {
        $className = $this->getTestClassName();
        return new $className($this->getConfig($extConfig));
    }

    public function testIsAvailable() {
        $this->assertTrue(
            call_user_func([$this->getTestClassName(), 'isAvailable'])
        );
    }

    public function testKey() {
        $language = _cfg('language');

        $cacheKey = 'key-for-test';
        $cacheArgs = ['arg1', 'arg2', 'arg3'];

        $actualKey = (!empty($language) ? $language : '') . ':' . $cacheKey;
        $actualKeyWithArgs = (!empty($language) ? $language : '') . ':' . $cacheKey . ':' . md5(serialize($cacheArgs));

        $cacheReflection = new \ReflectionClass($this->getTestClassName());
        $reflectionMethod = $cacheReflection->getMethod('key');
        $reflectionMethod->setAccessible(true);

        $cacheInstance = $cacheReflection->newInstance($this->getConfig());

        $this->assertEquals($reflectionMethod->invoke($cacheInstance, $cacheKey), $actualKey);
        $this->assertEquals($reflectionMethod->invoke($cacheInstance, $cacheKey, $cacheArgs), $actualKeyWithArgs);
    }

    public function testSetGet() {
        $cacheKey = 'test-set-get-key';
        $cacheValue = 'test-value';
        $cacheKey2 = 'test-set-get-key2';
        $cacheValue2 = ['item1' => 'value1', 'item2' => 'value2'];

        $cache = $this->getNewInstance();

        $this->assertTrue($cache->set($cacheKey, $cacheValue));
        $this->assertEquals($cache->get($cacheKey), $cacheValue);
        $this->assertTrue($cache->set($cacheKey2, $cacheValue2, 180));
        $this->assertCount(0, array_diff_assoc($cache->get($cacheKey2), $cacheValue2));
    }

    public function testDelete() {
        $cacheKey = 'delete-test-key';
        $cacheValue = 'delete-test-value';

        $cache = $this->getNewInstance();

        $this->assertTrue($cache->set($cacheKey, $cacheValue));
        $this->assertEquals($cache->get($cacheKey), $cacheValue);
        $this->assertTrue($cache->delete($cacheKey));
        $this->assertNull($cache->get($cacheKey));
    }

    public function testDropCacheKeys() {
        $cacheKeyPattern = 'drop-cache-keys';
        $cacheKeys = [$cacheKeyPattern . '-1', $cacheKeyPattern . '-2', $cacheKeyPattern. '-3'];
        $cacheValues = [123, 456, 789];

        $cache = $this->getNewInstance();

        foreach ($cacheKeys as $index => $cacheKey) {
            $this->assertTrue($cache->set($cacheKey, $cacheValues[$index]));
            $this->assertEquals($cache->get($cacheKey), $cacheValues[$index]);
        }

        $this->assertTrue($cache->dropCacheKeys($cacheKeyPattern));

        foreach ($cacheKeys as $cacheKey) {
            $this->assertNull($cache->get($cacheKey));
        }
    }

    public function testIncr() {
        $cacheKey = 'incr-test-key';

        $cache = $this->getNewInstance();
        $cache->delete($cacheKey);
        $this->assertEquals($cache->incr($cacheKey), 1);
        $this->assertEquals($cache->incr($cacheKey), 2);
        $this->assertEquals($cache->incr($cacheKey), 3);
        $cache->delete($cacheKey);
        $this->assertEquals($cache->incr($cacheKey), 1);
        $cache->delete($cacheKey);
    }
}
