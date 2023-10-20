<?php

namespace eGamings\WLC\Tests\Cache;

use eGamings\WLC\Cache\RedisCache;

class RedisCacheTest extends AbstractCacheTest
{
    protected function getTestClassName()
    {
        return RedisCache::class;
    }

    protected function getConfig(array $extConfig = [])
    {
        return array_merge([
            'host' => (!empty($_ENV['REDIS_HOST'])) ? $_ENV['REDIS_HOST'] : '127.0.0.1',
            'port' => 6379,
            'timeout' => 5
        ], $extConfig);
    }

    public function testTtl() {
        $cache = $this->getNewInstance();
        $this->assertEquals($cache->ttl('key_does_not_exist'), -2);
    }
}
