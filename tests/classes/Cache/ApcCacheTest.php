<?php

namespace eGamings\WLC\Tests\Cache;

use eGamings\WLC\Cache\ApcCache;

class ApcCacheTest extends AbstractCacheTest
{
    protected function setUp(): void
    {
        if (ini_get('apc.enable_cli') != 1) {
            $this->markTestSkipped('apc disabled');
        }
    }

    protected function getTestClassName() {
        return ApcCache::class;
    }

    protected function getConfig(array $extConfig = [])
    {
        return array_merge([], $extConfig);
    }

    public function testApcEnableCli() {
        $this->assertEquals(ini_get('apc.enable_cli'), 1);
    }

    public function testKey() {
        $language = _cfg('language');

        $cachePrefix = 'IS_CACHE_PREFIX';
        $cacheKey = 'key-for-test';
        $cacheArgs = ['arg1', 'arg2', 'arg3'];

        $actualKey = (!empty($language) ? $language : '') . ':' . $cacheKey;
        $actualKeyWithArgs = (!empty($language) ? $language : '') . ':' . $cacheKey . ':' . md5(serialize($cacheArgs));
        $actualKeyWithPrefix = $cachePrefix . ':' . $actualKey;
        $actualKeyWithPrefixAndArgs = $cachePrefix . ':' . $actualKeyWithArgs;


        $apcReflection = new \ReflectionClass($this->getTestClassName());
        $reflectionMethod = $apcReflection->getMethod('key');
        $reflectionMethod->setAccessible(true);

        $apcWithPrefix = $apcReflection->newInstance($this->getConfig([
            'prefix' => $cachePrefix
        ]));
        $apcWithoutPrefix = $apcReflection->newInstance($this->getConfig());


        $this->assertEquals($reflectionMethod->invoke($apcWithoutPrefix, $cacheKey), $actualKey);
        $this->assertEquals($reflectionMethod->invoke($apcWithoutPrefix, $cacheKey, $cacheArgs), $actualKeyWithArgs);
        $this->assertEquals($reflectionMethod->invoke($apcWithPrefix, $cacheKey), $actualKeyWithPrefix);
        $this->assertEquals($reflectionMethod->invoke($apcWithPrefix, $cacheKey, $cacheArgs), $actualKeyWithPrefixAndArgs);
    }

    public function testTtl() {
        $cache = $this->getNewInstance();
        $this->assertFalse($cache->ttl('key_does_not_exist'));
    }
}
